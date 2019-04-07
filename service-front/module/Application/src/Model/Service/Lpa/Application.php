<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Http\Client\Exception;
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
use Opg\Lpa\Logger\LoggerTrait;
use RuntimeException;

class Application extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;
    use LoggerTrait;

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

    public function getStatuses($ids)
    {
        $target = sprintf('/v2/user/%s/statuses/%s', $this->getUserId(), $ids);

        try {
            $result = $this->apiClient->httpGet($target);
        } catch (ApiException $ex) {
            $this->getLogger()->err($ex->getMessage());

            $result = null;
        }

        // if an ApiException is returned, we set result to null and return found false for the id's
        if ($result == null) {
            $result = [];

            $exploded_ids = explode(',', $ids);

            foreach ($exploded_ids as $id) {
                $result[$id] = ['found' => false];
            }

            return $result;
        }

        return $result;
    }

    /**
     * Create a new LPA application
     *
     * @return bool
     */
    public function createApplication()
    {
        try {
            $result = $this->apiClient->httpPost(sprintf('/v2/user/%s/applications', $this->getUserId()));

            return new Lpa($result);
        } catch (ApiException $ex) {}

        return false;
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
     * @throws Exception
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
        try {
            $result = $this->apiClient->httpGet(
                sprintf('/v2/user/%s/applications', $this->getUserId()),
                $queryParams
            );
        } catch (ApiException $ex) {
            throw new RuntimeException('missing-fields');
        }

        if (!isset($result['applications'])) {
            throw new RuntimeException('missing-fields');
        }

        $trackFromDate = new DateTime($this->getConfig()['processing-status']['track-from-date']);

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


            // If tracking is active update 'Completed' to 'Waiting for eligible applications, and add tracking update
            // id for any in 'Waiting',
            $refreshTracking = false;

            if ($lpa->getCompletedAt() instanceof DateTime) {
                $progress = 'Completed';

                if ($trackFromDate <= new DateTime('now') && $trackFromDate <= $lpa->getCompletedAt()) {
                    $progress = 'Waiting';

                    // If we already have a processing status use that instead of "Waiting" status
                    $metadata = $lpa->getMetadata();

                    if ($metadata != null && array_key_exists(Lpa::SIRIUS_PROCESSING_STATUS, $metadata)) {
                        $progress = $metadata[Lpa::SIRIUS_PROCESSING_STATUS];
                    }

                    // Only refresh tracking if the application is past completed and not at the final status
                    if ($progress != 'Returned') {
                        $refreshTracking = true;
                    }
                }
            } elseif ($lpa->getCreatedAt() instanceof DateTime) {
                $progress = 'Created';
            }

            //  Create a record for the returned LPA in an array object
            $result['applications'][$applicationIdx] = new ArrayObject([
                'id'         => $lpa->getId(),
                'version'    => 2,
                'donor'      => $donorName,
                'type'       => $lpaType,
                'updatedAt'  => $lpa->getUpdatedAt(),
                'progress'   => $progress,
                'refreshId' => $refreshTracking ? $lpa->getId() : null
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
        try {
            return $this->apiClient->httpGet(sprintf('/v2/user/%s/applications/%s/seed', $this->getUserId(), $lpaId));
        } catch (ApiException $ex) {}

        return false;
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
        try {
            return $this->apiClient->httpGet(sprintf('/v2/user/%s/applications/%s/pdfs/%s', $this->getUserId(), $lpaId, $pdfType));
        } catch (ApiException $ex) {}

        return false;
    }

    /**
     * Returns the PDF contents as application/pdf mime type for the specified PDF type
     *
     * @param $lpaId
     * @param $pdfType
     * @return array|bool|null
     * @throws ApiException
     */
    public function getPdfContents($lpaId, $pdfType)
    {
        $target = sprintf('/v2/user/%s/applications/%s/pdfs/%s.pdf', $this->getUserId(), $lpaId, $pdfType);

        try {
            return $this->apiClient->httpGet($target, [], false);
        } catch (ApiException $ex) {}

        return false;
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
        $target = sprintf('/v2/user/%s/applications/%s/primary-attorneys', $this->getUserId(), $lpa->id);

        try {
            $result = $this->apiClient->httpPost($target, $primaryAttorney->toArray());

            if (is_array($result)) {
                //  Marshall the data into the required data object and set it in the LPA
                if ($primaryAttorney instanceof Human) {
                    $lpa->document->primaryAttorneys[] = new Human($result);
                } else {
                    $lpa->document->primaryAttorneys[] = new TrustCorporation($result);
                }

                return true;
            }
        } catch (ApiException $ex) {}

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
        $target = sprintf('/v2/user/%s/applications/%s/replacement-attorneys', $this->getUserId(), $lpa->id);

        try {
            $result = $this->apiClient->httpPost($target, $replacementAttorney->toArray());

            if (is_array($result)) {
                //  Marshall the data into the required data object and set it in the LPA
                if ($replacementAttorney instanceof Human) {
                    $lpa->document->replacementAttorneys[] = new Human($result);
                } else {
                    $lpa->document->replacementAttorneys[] = new TrustCorporation($result);
                }

                return true;
            }
        } catch (ApiException $ex) {}

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
        $target = sprintf('/v2/user/%s/applications/%s/notified-people', $this->getUserId(), $lpa->id);

        try {
            $result = $this->apiClient->httpPost($target, $notifiedPerson->toArray());

            if (is_array($result)) {
                //  Marshall the data into the required data object and set it in the LPA
                $lpa->document->peopleToNotify[] = new NotifiedPerson($result);

                return true;
            }
        } catch (ApiException $ex) {}

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/who-are-you', $this->getUserId(), $lpa->id), $whoAreYou->toArray());

        if (is_array($result)) {
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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/type', $this->getUserId(), $lpa->id), [
            'type' => $lpaType,
        ]);

        if (is_array($result)) {
            $lpa->document->type = $result['type'];

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/donor', $this->getUserId(), $lpa->id), $donor->toArray());

        if (is_array($result)) {
            $lpa->document->donor = new Donor($result);

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/primary-attorney-decisions', $this->getUserId(), $lpa->id), $primaryAttorneyDecisions->toArray());

        if (is_array($result)) {
            $lpa->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions($result);

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/primary-attorneys/%s', $this->getUserId(), $lpa->id, $primaryAttorneyId), $primaryAttorney->toArray());

        if (is_array($result)) {
            //  Marshall the data into the required data object and set it in the LPA

            //  Insert the updated attorney at the correct ID
            foreach ($lpa->document->primaryAttorneys as $idx => $primaryAttorney) {
                if ($primaryAttorney->id == $primaryAttorneyId) {
                    if ($primaryAttorney instanceof Human) {
                        $lpa->document->primaryAttorneys[$idx] = new Human($result);
                    } else {
                        $lpa->document->primaryAttorneys[$idx] = new TrustCorporation($result);
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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/replacement-attorneys/%s', $this->getUserId(), $lpa->id, $replacementAttorneyId), $replacementAttorney->toArray());

        if (is_array($result)) {
            //  Marshall the data into the required data object and set it in the LPA

            //  Insert the updated attorney at the correct ID
            foreach ($lpa->document->replacementAttorneys as $idx => $replacementAttorney) {
                if ($replacementAttorney->id == $replacementAttorneyId) {
                    if ($replacementAttorney instanceof Human) {
                        $lpa->document->replacementAttorneys[$idx] = new Human($result);
                    } else {
                        $lpa->document->replacementAttorneys[$idx] = new TrustCorporation($result);
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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/replacement-attorney-decisions', $this->getUserId(), $lpa->id), $replacementAttorneyDecisions->toArray());

        if (is_array($result)) {
            $lpa->document->replacementAttorneyDecisions = new ReplacementAttorneyDecisions($result);

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/certificate-provider', $this->getUserId(), $lpa->id), $certificateProvider->toArray());

        if (is_array($result)) {
            $lpa->document->certificateProvider = new CertificateProvider($result);

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/notified-people/%s', $this->getUserId(), $lpa->id, $notifiedPersonId), $notifiedPerson->toArray());

        if (is_array($result)) {
            //  Marshall the data into the required data object and set it in the LPA

            //  Insert the updated attorney at the correct ID
            foreach ($lpa->document->peopleToNotify as $idx => $personToNotify) {
                if ($personToNotify->id == $notifiedPersonId) {
                    $lpa->document->peopleToNotify[$idx] = new NotifiedPerson($result);

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/preference', $this->getUserId(), $lpa->id), [
            'preference' => $preferences,
        ]);

        if (is_array($result)) {
            $lpa->document->preference = $result['preference'];

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/instruction', $this->getUserId(), $lpa->id), [
            'instruction' => $instructions,
        ]);

        if (is_array($result)) {
            $lpa->document->instruction = $result['instruction'];

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/who-is-registering', $this->getUserId(), $lpa->id), [
            'whoIsRegistering' => $whoIsRegistering,
        ]);

        if (is_array($result)) {
            $lpa->document->whoIsRegistering = $result['whoIsRegistering'];

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/correspondent', $this->getUserId(), $lpa->id), $correspondent->toArray());

        if (is_array($result)) {
            $lpa->document->correspondent = new Correspondence($result);

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/repeat-case-number', $this->getUserId(), $lpa->id), [
            'repeatCaseNumber' => $repeatCaseNumber,
        ]);

        if (is_array($result)) {
            $lpa->repeatCaseNumber = $result['repeatCaseNumber'];

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/payment', $this->getUserId(), $lpa->id), $payment->toArray());

        if (is_array($result)) {
            $lpa->payment = new Payment($result);

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
        $result = $this->executePut(sprintf('/v2/user/%s/applications/%s/seed', $this->getUserId(), $lpa->id), [
            'seed' => $seedId,
        ]);

        if (is_array($result)) {
            $lpa->seed = $result['seed'];

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
        try {
            $result = $this->apiClient->httpPost(sprintf('/v2/user/%s/applications/%s/lock', $this->getUserId(), $lpa->id));

            if (is_array($result)) {
                $lpa->locked = true;

                return true;
            }
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
