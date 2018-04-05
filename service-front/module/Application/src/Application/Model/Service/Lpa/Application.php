<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use Application\Model\Service\ApiClient\Response\Lpa as LpaResponse;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Common\LongName;
use DateTime;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;

class Application extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * Get an application by lpaId
     *
     * @param $lpaId
     * @return bool|static
     */
    public function getApplication($lpaId)
    {
        $target = sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId);

        $response = $this->apiClient->httpGet($target);

        if ($response->getStatusCode() == 200) {
            return LpaResponse::buildFromResponse($response);
        }

        return false;
    }

    /**
     * Create a new LPA application
     *
     * @return LpaResponse|ResponseException
     */
    public function createApplication()
    {
        $target = sprintf('/v2/users/%s/applications', $this->getUserId());

        try {
            $response = $this->apiClient->httpPost($target);

            if ($response->getStatusCode() == 201) {
                return LpaResponse::buildFromResponse($response);
            }
        } catch (ResponseException $e) {
            return $e;
        }

        return new ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Update application with the provided data
     *
     * @param $lpaId
     * @param array $data
     * @return ResponseException|\Exception|static
     */
    public function updateApplication($lpaId, array $data)
    {
        $target = sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId);

        try {
            $response = $this->apiClient->httpPatch($target, $data);

            if ($response->getStatusCode() == 200) {
                return LpaResponse::buildFromResponse($response);
            }
        } catch (ResponseException $e) {
            return $e;
        }

        return new ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Deletes an LPA application
     *
     * @param $lpaId
     * @return true|ResponseException
     */
    public function deleteApplication($lpaId)
    {
        $target = sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId);

        $response = $this->apiClient->httpDelete($target);

        if ($response->getStatusCode() == 204) {
            return true;
        }

        return new ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Get a summary of LPAs from the API utilising the search string if one was provided
     * If no page number if provided then get all summaries
     *
     * @param string $search
     * @param int $page
     * @param int $itemsPerPage
     * @return array
     */
    public function getLpaSummaries($search = null, $page = null, $itemsPerPage = null)
    {
        //  Construct the query params
        $queryParams = [
            'search' => $search,
        ];

        //  If valid page parameters are provided then add them to the API query
        if (is_numeric($page) && $page > 0 && is_numeric($itemsPerPage) && $itemsPerPage > 0) {
            $queryParams = array_merge($queryParams, [
                'page'    => $page,
                'perPage' => $itemsPerPage,
            ]);
        }

        $applicationsSummary = $this->getApplicationList($queryParams);

        //  If there are LPAs returned, change them into standard class objects for use
        $lpas = [];

        if (isset($applicationsSummary['applications']) && is_array($applicationsSummary['applications'])) {
            foreach ($applicationsSummary['applications'] as $application) {
                //  Get the Donor name
                $donorName = '';

                if ($application->document->donor instanceof Donor && $application->document->donor->name instanceof LongName) {
                    $donorName = (string) $application->document->donor->name;
                }

                //  Get the progress string
                $progress = 'Started';

                if ($application->completedAt instanceof DateTime) {
                    $progress = 'Completed';
                } elseif ($application->createdAt instanceof DateTime) {
                    $progress = 'Created';
                }

                //  Create a record for the returned LPA
                $lpa = new \stdClass();

                $lpa->id = $application->id;
                $lpa->version = 2;
                $lpa->donor = $donorName;
                $lpa->type = $application->document->type;
                $lpa->updatedAt = $application->updatedAt;
                $lpa->progress = $progress;

                $lpas[] = $lpa;
            }

            //  Swap the stdClass LPAs in
            $applicationsSummary['applications'] = $lpas;
        }

        return $applicationsSummary;
    }

    /**
     * Returns all LPAs for the user
     *
     * TODO - Fold this logic into the getLpaSummaries function above as it's the only place it used
     *
     * @param array $query
     * @return ResponseException|array
     */
    private function getApplicationList(array $query = [])
    {
        $applicationList = [];

        $response = $this->apiClient->httpGet(sprintf('/v2/users/%s/applications', $this->getUserId()), $query);

        if ($response->getStatusCode() != 200) {
            return new ResponseException('unknown-error', $response->getStatusCode(), $response);
        }

        $body = json_decode($response->getBody(), true);

        if (!isset($body['applications']) || !isset($body['total'])) {
            return new ResponseException('missing-fields', $response->getStatusCode(), $response);
        }

        //  If there are applications present then process them
        foreach ($body['applications'] as $application) {
            $applicationList[] = new Lpa($application);
        }

        //  Return a summary of the application list
        return [
            'applications' => $applicationList,
            'total'        => $body['total'],
        ];
    }

    /**
     * Returns the id of the seed LPA document and the list of actors
     *
     * @param $lpaId
     * @return mixed
     */
    public function getSeedDetails($lpaId)
    {
        return $this->getResource($lpaId, 'seed');
    }

    /**
     * Returns the PDF details for the specified PDF type
     *
     * @param $lpaId
     * @param $pdfName
     * @return mixed
     */
    public function getPdfDetails($lpaId, $pdfName)
    {
        return $this->getResource($lpaId, 'pdfs/' . $pdfName);
    }

    /**
     * Adds a new primary attorney
     *
     * @param Lpa $lpa
     * @param AbstractAttorney $primaryAttorney
     * @return bool
     */
    public function addPrimaryAttorney(Lpa $lpa, AbstractAttorney $primaryAttorney)
    {
        $responseData = $this->addResource($lpa->id, 'primary-attorneys', $primaryAttorney->toArray());

        if (is_array($responseData)) {
            //  Marshall the data into the required data object and set it in the LPA
            if ($primaryAttorney instanceof Human) {
                $lpa->document->primaryAttorneys[] = new Human($responseData);
            } else {
                $lpa->document->primaryAttorneys[] = new TrustCorporation($responseData);
            }

            return true;
        }

        return false;
    }

    /**
     * Adds a new replacement attorney
     *
     * @param Lpa $lpa
     * @param AbstractAttorney $replacementAttorney
     * @return bool
     */
    public function addReplacementAttorney(Lpa $lpa, AbstractAttorney $replacementAttorney)
    {
        $responseData = $this->addResource($lpa->id, 'replacement-attorneys', $replacementAttorney->toArray());

        if (is_array($responseData)) {
            //  Marshall the data into the required data object and set it in the LPA
            if ($replacementAttorney instanceof Human) {
                $lpa->document->replacementAttorneys[] = new Human($responseData);
            } else {
                $lpa->document->replacementAttorneys[] = new TrustCorporation($responseData);
            }

            return true;
        }

        return false;
    }

    /**
     * Adds a new notified person
     *
     * @param Lpa $lpa
     * @param NotifiedPerson $notifiedPerson
     * @return bool
     */
    public function addNotifiedPerson(Lpa $lpa, NotifiedPerson $notifiedPerson)
    {
        $responseData = $this->addResource($lpa->id, 'notified-people', $notifiedPerson->toArray());

        if (is_array($responseData)) {
            //  Marshall the data into the required data object and set it in the LPA
            $lpa->document->peopleToNotify[] = new NotifiedPerson($responseData);

            return true;
        }

        return false;
    }

    /**
     * Sets the person/organisation of who completed the application
     *
     * @param Lpa $lpa
     * @param WhoAreYou $whoAreYou
     * @return bool
     */
    public function setWhoAreYou(Lpa $lpa, WhoAreYou $whoAreYou)
    {
        $responseData = $this->addResource($lpa->id, 'who-are-you', $whoAreYou->toArray());

        if (is_array($responseData)) {
            $lpa->whoAreYouAnswered = true;

            return true;
        }

        return false;
    }

    /**
     * Set the LPA type
     *
     * @param Lpa $lpa
     * @param $lpaType
     * @return bool
     */
    public function setType(Lpa $lpa, $lpaType)
    {
        $responseData = $this->setResource($lpa->id, 'type', [
            'type' => $lpaType,
        ]);

        if (is_array($responseData)) {
            $lpa->document->type = $lpaType;

            return true;
        }

        return false;
    }

    /**
     * Set the donor
     *
     * @param Lpa $lpa
     * @param Donor $donor
     * @return bool
     */
    public function setDonor(Lpa $lpa, Donor $donor)
    {
        $responseData = $this->setResource($lpa->id, 'donor', $donor->toArray());

        if (is_array($responseData)) {
            $lpa->document->donor = new Donor($responseData);

            return true;
        }

        return false;
    }

    /**
     * Set the primary attorney decisions
     *
     * @param Lpa $lpa
     * @param PrimaryAttorneyDecisions $primaryAttorneyDecisions
     * @return bool
     */
    public function setPrimaryAttorneyDecisions(Lpa $lpa, PrimaryAttorneyDecisions $primaryAttorneyDecisions)
    {
        $responseData = $this->setResource($lpa->id, 'primary-attorney-decisions', $primaryAttorneyDecisions->toArray());

        if (is_array($responseData)) {
            $lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions($responseData);

            return true;
        }

        return false;
    }

    /**
     * Sets the primary attorney for the given primary attorney id
     *
     * @param Lpa $lpa
     * @param AbstractAttorney $primaryAttorney
     * @param $primaryAttorneyId
     * @return bool
     */
    public function setPrimaryAttorney(Lpa $lpa, AbstractAttorney $primaryAttorney, $primaryAttorneyId)
    {
        $responseData = $this->setResource($lpa->id, 'primary-attorneys', $primaryAttorney->toArray(), $primaryAttorneyId);

        if (is_array($responseData)) {
            //  Marshall the data into the required data object and set it in the LPA

            //  Insert the updated attorney at the correct ID
            foreach ($lpa->document->primaryAttorneys as $idx => $primaryAttorney) {
                if ($primaryAttorney->id == $primaryAttorneyId) {
                    if ($primaryAttorney instanceof Human) {
                        $lpa->document->primaryAttorneys[$idx] = new Human($responseData);
                    } else {
                        $lpa->document->primaryAttorneys[$idx] = new TrustCorporation($responseData);
                    }

                    break;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Sets the replacement attorney for the given replacement attorney id
     *
     * @param Lpa $lpa
     * @param AbstractAttorney $replacementAttorney
     * @param $replacementAttorneyId
     * @return bool
     */
    public function setReplacementAttorney(Lpa $lpa, AbstractAttorney $replacementAttorney, $replacementAttorneyId)
    {
        $responseData = $this->setResource($lpa->id, 'replacement-attorneys', $replacementAttorney->toArray(), $replacementAttorneyId);

        if (is_array($responseData)) {
            //  Marshall the data into the required data object and set it in the LPA

            //  Insert the updated attorney at the correct ID
            foreach ($lpa->document->replacementAttorneys as $idx => $replacementAttorney) {
                if ($replacementAttorney->id == $replacementAttorneyId) {
                    if ($replacementAttorney instanceof Human) {
                        $lpa->document->replacementAttorneys[$idx] = new Human($responseData);
                    } else {
                        $lpa->document->replacementAttorneys[$idx] = new TrustCorporation($responseData);
                    }

                    break;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Set the replacement attorney decisions
     *
     * @param Lpa $lpa
     * @param ReplacementAttorneyDecisions $replacementAttorneyDecisions
     * @return bool
     */
    public function setReplacementAttorneyDecisions(Lpa $lpa, ReplacementAttorneyDecisions $replacementAttorneyDecisions)
    {
        $responseData = $this->setResource($lpa->id, 'replacement-attorney-decisions', $replacementAttorneyDecisions->toArray());

        if (is_array($responseData)) {
            $lpa->document->replacementAttorneyDecisions = new ReplacementAttorneyDecisions($responseData);

            return true;
        }

        return false;
    }

    /**
     * Set the certificate provider
     *
     * @param Lpa $lpa
     * @param $certificateProvider
     * @return bool
     */
    public function setCertificateProvider(Lpa $lpa, CertificateProvider $certificateProvider)
    {
        $responseData = $this->setResource($lpa->id, 'certificate-provider', $certificateProvider->toArray());

        if (is_array($responseData)) {
            $lpa->document->certificateProvider = new CertificateProvider($responseData);

            return true;
        }

        return false;
    }

    /**
     * Sets the notified person for the given notified person id
     *
     * @param Lpa $lpa
     * @param NotifiedPerson $notifiedPerson
     * @param $notifiedPersonId
     * @return bool
     */
    public function setNotifiedPerson(Lpa $lpa, NotifiedPerson $notifiedPerson, $notifiedPersonId)
    {
        $responseData = $this->setResource($lpa->id, 'notified-people', $notifiedPerson->toArray(), $notifiedPersonId);

        if (is_array($responseData)) {
            //  Marshall the data into the required data object and set it in the LPA

            //  Insert the updated attorney at the correct ID
            foreach ($lpa->document->peopleToNotify as $idx => $personToNotify) {
                if ($personToNotify->id == $notifiedPersonId) {
                    $lpa->document->peopleToNotify[$idx] = new NotifiedPerson($responseData);

                    break;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Set the preferences
     *
     * @param Lpa $lpa
     * @param $preferences
     * @return bool
     */
    public function setPreferences(Lpa $lpa, $preferences)
    {
        $responseData = $this->setResource($lpa->id, 'preference', [
            'preference' => $preferences,
        ]);

        if (is_array($responseData)) {
            $lpa->document->preference = $preferences;

            return true;
        }

        return false;
    }

    /**
     * Set the instructions
     *
     * @param Lpa $lpa
     * @param $instructions
     * @return bool|mixed
     */
    public function setInstructions(Lpa $lpa, $instructions)
    {
        $responseData = $this->setResource($lpa->id, 'instruction', [
            'instruction' => $instructions,
        ]);

        if (is_array($responseData)) {
            $lpa->document->instruction = $instructions;

            return true;
        }

        return false;
    }

    /**
     * Set Who Is Registering
     *
     * @param Lpa $lpa
     * @param $whoIsRegistering
     * @return bool
     */
    public function setWhoIsRegistering(Lpa $lpa, $whoIsRegistering)
    {
        $responseData = $this->setResource($lpa->id, 'who-is-registering', [
            'whoIsRegistering' => $whoIsRegistering,
        ]);

        if (is_array($responseData)) {
            $lpa->document->whoIsRegistering = $whoIsRegistering;

            return true;
        }

        return false;
    }

    /**
     * Set the correspondent
     *
     * @param Lpa $lpa
     * @param Correspondence $correspondent
     * @return bool
     */
    public function setCorrespondent(Lpa $lpa, Correspondence $correspondent)
    {
        $responseData = $this->setResource($lpa->id, 'correspondent', $correspondent->toArray());

        if (is_array($responseData)) {
            $lpa->document->correspondent = new Correspondence($responseData);

            return true;
        }

        return false;
    }

    /**
     * Set the LPA type
     *
     * @param Lpa $lpa
     * @param $repeatCaseNumber
     * @return bool|mixed
     */
    public function setRepeatCaseNumber(Lpa $lpa, $repeatCaseNumber)
    {
        $responseData = $this->setResource($lpa->id, 'repeat-case-number', [
            'repeatCaseNumber' => $repeatCaseNumber,
        ]);

        if (is_array($responseData)) {
            $lpa->repeatCaseNumber = $repeatCaseNumber;

            return true;
        }

        return false;
    }

    /**
     * Set the payment information
     *
     * @param Lpa $lpa
     * @param Payment $payment
     * @return bool
     */
    public function setPayment(Lpa $lpa, Payment $payment)
    {
        $responseData = $this->setResource($lpa->id, 'payment', $payment->toArray());

        if (is_array($responseData)) {
            $lpa->payment = new Payment($responseData);

            return true;
        }

        return false;
    }

    /**
     * Sets the id of the seed LPA
     *
     * @param Lpa $lpa
     * @param $seedId
     * @return bool
     */
    public function setSeed(Lpa $lpa, $seedId)
    {
        $responseData = $this->setResource($lpa->id, 'seed', [
            'seed' => $seedId,
        ]);

        if (is_array($responseData)) {
            $lpa->seed = $seedId;

            return true;
        }

        return false;
    }

    /**
     * Deletes a primary attorney for the given ID
     *
     * @param Lpa $lpa
     * @param $primaryAttorneyId
     * @return bool
     */
    public function deletePrimaryAttorney(Lpa $lpa, $primaryAttorneyId)
    {
        if ($this->deleteResource($lpa->id, 'primary-attorneys', $primaryAttorneyId)) {
            //  Remove the deleted attorney
            foreach ($lpa->document->primaryAttorneys as $idx => $primaryAttorney) {
                if ($primaryAttorney->id == $primaryAttorneyId) {
                    unset($lpa->document->primaryAttorneys[$idx]);
                    break;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Deletes a replacement attorney for the given ID
     *
     * @param Lpa $lpa
     * @param $replacementAttorneyId
     * @return bool
     */
    public function deleteReplacementAttorney(Lpa $lpa, $replacementAttorneyId)
    {
        if ($this->deleteResource($lpa->id, 'replacement-attorneys', $replacementAttorneyId)) {
            //  Remove the deleted attorney
            foreach ($lpa->document->replacementAttorneys as $idx => $replacementAttorney) {
                if ($replacementAttorney->id == $replacementAttorneyId) {
                    unset($lpa->document->replacementAttorneys[$idx]);
                    break;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Deletes the certificate provider
     *
     * @param Lpa $lpa
     * @return bool
     */
    public function deleteCertificateProvider(Lpa $lpa)
    {
        if ($this->deleteResource($lpa->id, 'certificate-provider')) {
            $lpa->document->certificateProvider = null;

            return true;
        }

        return false;
    }

    /**
     * Deletes a person to notify for the given ID
     *
     * @param Lpa $lpa
     * @param string $notifiedPersonId
     * @return boolean
     */
    public function deleteNotifiedPerson(Lpa $lpa, $notifiedPersonId)
    {
        if ($this->deleteResource($lpa->id, 'notified-people', $notifiedPersonId)) {
            //  Remove the deleted person to notify
            foreach ($lpa->document->peopleToNotify as $idx => $personToNotify) {
                if ($personToNotify->id == $notifiedPersonId) {
                    unset($lpa->document->peopleToNotify[$idx]);
                    break;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Deletes the correspondent
     *
     * @param Lpa $lpa
     * @return bool
     */
    public function deleteCorrespondent(Lpa $lpa)
    {
        if ($this->deleteResource($lpa->id, 'correspondent')) {
            $lpa->document->correspondent = null;

            return true;
        }

        return false;
    }

    /**
     * Deletes the repeat case number
     *
     * @param $userId
     * @param $lpaId
     * @return bool
     */
    public function deleteRepeatCaseNumber($userId, $lpaId)
    {
        return $this->deleteResource($userId, $lpaId, 'repeat-case-number');
    }

    /**
     * Locks the LPA. Once locked the LPA becomes read-only. It can however still be deleted.
     *
     * @param Lpa $lpa
     * @return bool
     */
    public function lockLpa(Lpa $lpa)
    {
        try {
            $response = $this->apiClient->httpPost(sprintf('/v1/users/%s/applications/%s/%s', $this->getUserId(), $lpa->id, 'lock'));

            if ($response->getStatusCode() == 201) {
                $responseData = json_decode($response->getBody(), true);

                if (isset($responseData['locked'])) {
                    return $responseData['locked'];
                }
            }
        } catch (ResponseException $ignore) {}

        return false;
    }

    /**
     * Returns the PDF body for the specified PDF type
     *
     * @param Lpa $lpa
     * @param $pdfName
     * @return bool|mixed
     */
    public function getPdf(Lpa $lpa, $pdfName)
    {
        //  Make the resource type equal to the type of PDF we want to return
        $resourceType = 'pdfs/' . $pdfName . '.pdf';

        $response = $this->apiClient->httpPost(sprintf('/v1/users/%s/applications/%s/%s', $this->getUserId(), $lpa->id, $resourceType));

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody(), true);
        }

        return false;
    }

    /**
     * Return the API response for getting the resource of the given type
     *
     * If property not yet set, return null
     * If error, return false
     *
     * @param $lpaId
     * @param $resourceType
     * @return bool|mixed|null
     */
    private function getResource($lpaId, $resourceType)
    {
        $response = $this->apiClient->httpGet(sprintf('/v1/users/%s/applications/%s/%s', $this->getUserId(), $lpaId, $resourceType));

        if ($response->getStatusCode() == 204) {
            return null;
        }

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody(), true);
        }

        return false;
    }

    /**
     * Add data for the given resource with a post
     *
     * @param $lpaId
     * @param $resourceType
     * @param $jsonBody
     * @return bool
     */
    private function addResource($lpaId, $resourceType, $jsonBody)
    {
        try {
            $response = $this->apiClient->httpPost(sprintf('/v1/users/%s/applications/%s/%s', $this->getUserId(), $lpaId, $resourceType), $jsonBody);

            if ($response->getStatusCode() == 201) {
                return json_decode($response->getBody(), true);
            }
        } catch (ResponseException $ignore) {}

        return false;
    }

    /**
     * Set the data for the given resource. i.e. PUT
     *
     * @param $lpaId
     * @param $resourceType
     * @param $jsonBody
     * @param null $index
     * @return bool|mixed
     */
    private function setResource($lpaId, $resourceType, $jsonBody, $index = null)
    {
        try {
            $target = sprintf('/v1/users/%s/applications/%s/%s', $this->getUserId(), $lpaId, $resourceType);

            if (!is_null($index)) {
                $target .= '/' . $index;
            }

            $response = $this->apiClient->httpPut($target, $jsonBody);

            if (in_array($response->getStatusCode(), [200, 201])) {
                return json_decode($response->getBody(), true);
            }
        } catch (ResponseException $ignore) {}

        return false;
    }

    /**
     * Delete the resource type from the LPA. i.e. DELETE
     *
     * @param $lpaId
     * @param $resourceType
     * @param null $index
     * @return bool
     */
    private function deleteResource($lpaId, $resourceType, $index = null)
    {
        try {
            $target = sprintf('/v1/users/%s/applications/%s/%s', $this->getUserId(), $lpaId, $resourceType);

            if (!is_null($index)) {
                $target .= '/' . $index;
            }

            $response = $this->apiClient->httpDelete($target);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (ResponseException $ignore) {}

        return false;
    }
}
