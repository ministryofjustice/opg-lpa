<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ApiException;
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
use ArrayObject;
use RuntimeException;

class Application extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * Get an application by lpaId
     *
     * @param $lpaId
     * @param string|null $token
     * @return array|bool|null
     */
    public function getApplication($lpaId, string $token = null)
    {
        if ($token) {
            $this->apiClient->updateToken($token);
        }

        $target = sprintf('/v2/user/%s/applications/%d', $this->getUserId(), $lpaId);

        try {
            $result = $this->apiClient->httpGet($target);

            return new Lpa($result);
        } catch (ApiException $ex) {}

        return false;
    }

    /**
     * Create a new LPA application
     *
     * @return bool
     */
    public function createApplication()
    {
        return $this->executePost(sprintf('/v2/user/%s/applications', $this->getUserId()));
    }

    /**
     * Update application with the provided data
     *
     * @param $lpaId
     * @param array $data
     * @return bool
     */
    public function updateApplication($lpaId, array $data)
    {
        $target = sprintf('/v2/user/%s/applications/%d', $this->getUserId(), $lpaId);

        try {
            $result = $this->apiClient->httpPatch($target, $data);

            return new Lpa($result);
        } catch (ApiException $ex) {}

        return false;
    }

    /**
     * Deletes an LPA application
     *
     * @param $lpaId
     * @return bool
     */
    public function deleteApplication($lpaId)
    {
        return $this->executeDelete(sprintf('/v2/user/%s/applications/%d', $this->getUserId(), $lpaId));
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

        //  Get the response and check it's contents
        $result = $this->apiClient->httpGet(sprintf('/v2/user/%s/applications', $this->getUserId()), $queryParams);

        if (!isset($result['applications'])) {
            throw new RuntimeException('missing-fields');
        }

        //  Loop through the applications in the result, enhance the data and set it in an array object
        foreach ($result['applications'] as $applicationIdx => $applicationData) {
            $lpa = new Lpa($applicationData);

            //  Get the Donor name
            $donorName = '';
            $lpaType = '';

            if ($lpa->document->donor instanceof Donor && $lpa->document->donor->name instanceof LongName) {
                $donorName = (string) $lpa->document->donor->name;
            }

            if (!is_null($lpa->document->type)) {
                $lpaType = $lpa->document->type;
            }

            //  Get the progress string
            $progress = 'Started';

            if ($lpa->completedAt instanceof DateTime) {
                $progress = 'Completed';
            } elseif ($lpa->createdAt instanceof DateTime) {
                $progress = 'Created';
            }

            //  Create a record for the returned LPA in an array object
            $result['applications'][$applicationIdx] = new ArrayObject([
                'id'        => $lpa->id,
                'version'   => 2,
                'donor'     => $donorName,
                'type'      => $lpaType,
                'updatedAt' => $lpa->updatedAt,
                'progress'  => $progress,
            ]);
        }

        return $result;
    }

    /**
     * Returns the id of the seed LPA document and the list of actors
     *
     * @param $lpaId
     * @return mixed
     */
    public function getSeedDetails($lpaId)
    {
        return $this->executeGet(sprintf('/v2/user/%s/applications/%s/seed', $this->getUserId(), $lpaId));
    }

    /**
     * Returns the PDF details for the specified PDF type
     *
     * @param $lpaId
     * @param $pdfType
     * @return bool|mixed
     */
    public function getPdf($lpaId, $pdfType)
    {
        return $this->executeGet(sprintf('/v2/user/%s/applications/%s/pdfs/%s', $this->getUserId(), $lpaId, $pdfType));
    }

    /**
     * Returns the PDF contents as application/pdf mime type for the specified PDF type
     *
     * @param $lpaId
     * @param $pdfType
     * @return array|bool|null
     */
    public function getPdfContents($lpaId, $pdfType)
    {
        return $this->executeGet(sprintf('/v2/user/%s/applications/%s/pdfs/%s.pdf', $this->getUserId(), $lpaId, $pdfType));
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
        $responseData = $this->executePost(sprintf('/v2/user/%s/applications/%s/primary-attorneys', $this->getUserId(), $lpa->id), $primaryAttorney->toArray());

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
        $responseData = $this->executePost(sprintf('/v2/user/%s/applications/%s/replacement-attorneys', $this->getUserId(), $lpa->id), $replacementAttorney->toArray());

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
        $responseData = $this->executePost(sprintf('/v2/user/%s/applications/%s/notified-people', $this->getUserId(), $lpa->id), $notifiedPerson->toArray());

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
        $responseData = $this->executePost(sprintf('/v2/user/%s/applications/%s/who-are-you', $this->getUserId(), $lpa->id), $whoAreYou->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/type', $this->getUserId(), $lpa->id), [
            'type' => $lpaType,
        ]);

        if (is_array($responseData)) {
            $lpa->document->type = $responseData['type'];

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/donor', $this->getUserId(), $lpa->id), $donor->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/primary-attorney-decisions', $this->getUserId(), $lpa->id), $primaryAttorneyDecisions->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/primary-attorneys/%s', $this->getUserId(), $lpa->id, $primaryAttorneyId), $primaryAttorney->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/replacement-attorneys/%s', $this->getUserId(), $lpa->id, $replacementAttorneyId), $replacementAttorney->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/replacement-attorney-decisions', $this->getUserId(), $lpa->id), $replacementAttorneyDecisions->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/certificate-provider', $this->getUserId(), $lpa->id), $certificateProvider->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/notified-people/%s', $this->getUserId(), $lpa->id, $notifiedPersonId), $notifiedPerson->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/preference', $this->getUserId(), $lpa->id), [
            'preference' => $preferences,
        ]);

        if (is_array($responseData)) {
            $lpa->document->preference = $responseData['preference'];

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/instruction', $this->getUserId(), $lpa->id), [
            'instruction' => $instructions,
        ]);

        if (is_array($responseData)) {
            $lpa->document->instruction = $responseData['instruction'];

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/who-is-registering', $this->getUserId(), $lpa->id), [
            'whoIsRegistering' => $whoIsRegistering,
        ]);

        if (is_array($responseData)) {
            $lpa->document->whoIsRegistering = $responseData['whoIsRegistering'];

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/correspondent', $this->getUserId(), $lpa->id), $correspondent->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/repeat-case-number', $this->getUserId(), $lpa->id), [
            'repeatCaseNumber' => $repeatCaseNumber,
        ]);

        if (is_array($responseData)) {
            $lpa->repeatCaseNumber = $responseData['repeatCaseNumber'];

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/payment', $this->getUserId(), $lpa->id), $payment->toArray());

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
        $responseData = $this->executePut(sprintf('/v2/user/%s/applications/%s/seed', $this->getUserId(), $lpa->id), [
            'seed' => $seedId,
        ]);

        if (is_array($responseData)) {
            $lpa->seed = $responseData['seed'];

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
        $target = sprintf('/v2/user/%s/applications/%s/primary-attorneys/%s', $this->getUserId(), $lpa->id, $primaryAttorneyId);

        if ($this->executeDelete($target)) {
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
        $target = sprintf('/v2/user/%s/applications/%s/replacement-attorneys/%s', $this->getUserId(), $lpa->id, $replacementAttorneyId);

        if ($this->executeDelete($target)) {
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
        $target = sprintf('/v2/user/%s/applications/%s/certificate-provider', $this->getUserId(), $lpa->id);

        if ($this->executeDelete($target)) {
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
        $target = sprintf('/v2/user/%s/applications/%s/notified-people/%s', $this->getUserId(), $lpa->id, $notifiedPersonId);

        if ($this->executeDelete($target)) {
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
        $target = sprintf('/v2/user/%s/applications/%s/correspondent', $this->getUserId(), $lpa->id);

        if ($this->executeDelete($target)) {
            $lpa->document->correspondent = null;

            return true;
        }

        return false;
    }

    /**
     * Deletes the repeat case number
     *
     * @param Lpa $lpa
     * @return bool
     */
    public function deleteRepeatCaseNumber(Lpa $lpa)
    {
        $target = sprintf('/v2/user/%s/applications/%s/repeat-case-number', $this->getUserId(), $lpa->id);

        if ($this->executeDelete($target)) {
            $lpa->repeatCaseNumber = null;

            return true;
        }

        return false;
    }

    /**
     * Locks the LPA. Once locked the LPA becomes read-only. It can however still be deleted.
     *
     * @param Lpa $lpa
     * @return bool
     */
    public function lockLpa(Lpa $lpa)
    {
        $responseData = $this->executePost(sprintf('/v2/user/%s/applications/%s/lock', $this->getUserId(), $lpa->id));

        if (is_array($responseData)) {
            $lpa->locked = true;

            return true;
        }

        return false;
    }

    /**
     * @param $target
     * @return bool|mixed|null
     */
    private function executeGet($target)
    {
        try {
            return $this->apiClient->httpGet($target);
        } catch (ApiException $ex) {}

        return false;
    }

    /**
     * @param $target
     * @param $jsonBody
     * @return bool|mixed
     */
    private function executePost($target, $jsonBody = [])
    {
        try {
            $result = $this->apiClient->httpPost($target, $jsonBody);

            return new Lpa($result);
        } catch (ApiException $ex) {}

        return false;
    }

    /**
     * @param $target
     * @param $jsonBody
     * @return bool|mixed
     */
    private function executePut($target, $jsonBody)
    {
        try {
            return $this->apiClient->httpPut($target, $jsonBody);
        } catch (ApiException $ex) {}

        return false;
    }

    /**
     * @param $target
     * @return bool
     */
    private function executeDelete($target)
    {
        try {
            $this->apiClient->httpDelete($target);

            return true;
        } catch (ApiException $ex) {}

        return false;
    }
}
