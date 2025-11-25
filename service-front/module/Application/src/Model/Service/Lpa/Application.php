<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Http\Client\Exception;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Common\LongName;
use DateTime;
use MakeShared\DataModel\Lpa\Formatter as LpaFormatter;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use ArrayObject;
use MakeShared\Logging\LoggerTrait;

class Application extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;
    use LoggerTrait;

    /**
     * Get an application by lpaId
     *
     * @param $lpaId
     */
    public function getApplication($lpaId, #[\SensitiveParameter] ?string $token = null): Lpa|false
    {
        if ($token) {
            $this->apiClient->updateToken($token);
        }

        $target = sprintf('/v2/user/%s/applications/%d', $this->getUserId(), $lpaId);

        try {
            $result = $this->apiClient->httpGet($target);
            return new Lpa($result);
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to fetch application', [
                'userId' => $this->getUserId(),
                'lpaId' => $lpaId,
                'error_code' => 'API_CLIENT_LPA_FETCH_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function getStatuses($ids)
    {
        $target = sprintf('/v2/user/%s/statuses/%s', $this->getUserId(), $ids);

        try {
            $result = $this->apiClient->httpGet($target);
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to fetch LPA statuses', [
                'userId' => $this->getUserId(),
                'ids' => $ids,
                'error_code' => 'API_CLIENT_LPA_STATUS_FETCH_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            $result = null;
        }

        // if an ApiException is returned, we set result to null and
        // return found => false for the ids
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
     * @return false|Lpa
     */
    public function createApplication()
    {
        try {
            return new Lpa(
                $this->apiClient->httpPost(sprintf('/v2/user/%s/applications', $this->getUserId()))
            );
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to create LPA Application', [
                'userId' => $this->getUserId(),
                'error_code' => 'API_CLIENT_LPA_CREATE_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    /**
     * Update application with the provided data
     *
     * @param $lpaId
     * @param array $data
     * @return false|Lpa
     */
    public function updateApplication($lpaId, array $data)
    {
        $target = sprintf('/v2/user/%s/applications/%d', $this->getUserId(), $lpaId);

        try {
            return new Lpa($this->apiClient->httpPatch($target, $data));
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to update application', [
                'userId' => $this->getUserId(),
                'lpaId' => $lpaId,
                'error_code' => 'API_CLIENT_LPA_FETCH_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

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

        $result = [
            'applications' => []
        ];

        //  Get the response and check its contents
        try {
            $response = $this->apiClient->httpGet(
                sprintf('/v2/user/%s/applications', $this->getUserId()),
                $queryParams
            );

            if (is_array($response)) {
                $result = $response;
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to fetch LPA Application summaries', [
                'userId' => $this->getUserId(),
                'queryParams' => $queryParams,
                'error_code' => 'API_CLIENT_LPA_SUMMARIES_FETCH_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        $trackFromDate = new DateTime($this->getConfig()['processing-status']['track-from-date']);
        $trackingEnabled = $trackFromDate <= new DateTime('now');

        $result['trackingEnabled'] = $trackingEnabled;

        //  Loop through the applications in the result, enhance the data and set it in an array object
        foreach ($result['applications'] as $applicationIdx => $applicationData) {
            $donorName = '';
            $lpaType = '';

            $lpa = new Lpa($applicationData);

            $metadata = $lpa->getMetadata();

            // Determine whether the LPA details are re-usable.
            // As per LPAL-64, this is when people to notify has been confirmed.
            // Note that we don't bother to check whether the LPA actually
            // has reusable pieces, like a donor, attorney or certificate provider:
            // that is dealt with by the pop-up which prompts the user
            // to select details to reuse when filling an LPA application.
            $isReusable = array_key_exists(Lpa::PEOPLE_TO_NOTIFY_CONFIRMED, $metadata);

            //  Get the Donor name
            if ($lpa->hasDonor() && $lpa->document->donor->name instanceof LongName) {
                $donorName = (string) $lpa->document->donor->name;
            }

            if (!is_null($lpa->document->type)) {
                $lpaType = $lpa->document->type;
            }

            //  Get the progress string
            $progress = 'Started';

            // If tracking is active update 'Completed' to 'Waiting for eligible
            // applications', and add tracking update id for any in 'Waiting'
            $refreshTracking = false;

            // If the application is processed, find the registration,
            // withdrawn, invalid and rejected dates; whichever is
            // set will be used for the eventual "processed" date in the UI
            $rejectedDate = null;

            if ($lpa->getCompletedAt() instanceof DateTime) {
                $progress = 'Completed';

                if ($trackingEnabled && $trackFromDate <= $lpa->getCompletedAt()) {
                    $progress = 'Waiting';

                    // If we already have a processing status use that instead of "Waiting" status
                    if (array_key_exists(Lpa::SIRIUS_PROCESSING_STATUS, $metadata)) {
                        $processingStatus = $metadata[Lpa::SIRIUS_PROCESSING_STATUS];

                        if ($processingStatus !== null) {
                            // Note this may set progress to "Completed" again
                            $progress = $metadata[Lpa::SIRIUS_PROCESSING_STATUS];
                        }

                        if ($processingStatus === 'Processed' && isset($metadata[Lpa::APPLICATION_REJECTED_DATE])) {
                            $rejectedDate = $metadata[Lpa::APPLICATION_REJECTED_DATE];
                        }
                    }

                    if ($progress !== 'Completed') {
                        $refreshTracking = true;
                    }
                }
            } elseif ($lpa->getCreatedAt() instanceof DateTime) {
                $progress = 'Created';
            }

            // Create a record for the returned LPA in an array object
            $result['applications'][$applicationIdx] = new ArrayObject([
                'id' => $lpa->getId(),
                'version' => 2,
                'donor' => $donorName,
                'isReusable' => $isReusable,
                'type' => $lpaType,
                'updatedAt' => $lpa->getUpdatedAt(),
                'progress' => $progress,
                'rejectedDate' => $rejectedDate,
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
        } catch (ApiException $ex) {
            $this->getLogger()->warning('Failed to fetch ID of seed LPA document', [
                'userId' => $this->getUserId(),
                'error_code' => 'API_CLIENT_LPA_SEED_DETAILS_FETCH_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

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
            return $this->apiClient->httpGet(
                sprintf('/v2/user/%s/applications/%s/pdfs/%s', $this->getUserId(), $lpaId, $pdfType),
            );
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to fetch PDF details', [
                'userId' => $this->getUserId(),
                'error_code' => 'API_CLIENT_LPA_PDF_FETCH_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
            return false;
        }

        return false;
    }

    /**
     * Returns the PDF contents as application/pdf mime type for the specified PDF type
     *
     * @param $lpaId
     * @param $pdfType
     * @return array|false|null|string
     * @throws ApiException
     */
    public function getPdfContents($lpaId, $pdfType)
    {
        try {
            $result = $this->apiClient->httpGet(
                sprintf('/v2/user/%s/applications/%s/pdfs/%s.pdf', $this->getUserId(), $lpaId, $pdfType),
                [], // $query,
                false, // $jsonResponse
                false, // $anonymous,
                ['Accept' => 'application/pdf'], // $additionalHeaders
            );
        } catch (ApiException $ex) {
            $this->getLogger()->warning('Failed to fetch PDF contents', [
                'userId' => $this->getUserId(),
                'error_code' => 'API_CLIENT_LPA_PDF_CONTENTS_FETCH_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
            $result = false;
        }

        return $result;
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
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to add a new primary attorney', [
                'userId' => $this->getUserId(),
                'error_code' => 'API_CLIENT_LPA_ADD_PRIMARY_ATTORNEY_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
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
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to add a new replacement attorney', [
                'userId' => $this->getUserId(),
                'error_code' => 'API_CLIENT_LPA_ADD_REPLACEMENT_ATTORNEY_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
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
        $target = sprintf('/v2/user/%s/applications/%s/notified-people', $this->getUserId(), $lpa->id);

        try {
            $result = $this->apiClient->httpPost($target, $notifiedPerson->toArray());

            if (is_array($result)) {
                //  Marshall the data into the required data object and set it in the LPA
                $lpa->document->peopleToNotify[] = new NotifiedPerson($result);

                return true;
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to add a new notified person', [
                'userId' => $this->getUserId(),
                'error_code' => 'API_CLIENT_LPA_ADD_NOTIFIED_PERSON_FAILED',
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/who-are-you', $this->getUserId(), $lpa->id),
            $whoAreYou->toArray()
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/donor', $this->getUserId(), $lpa->id),
            $donor->toArray()
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/primary-attorney-decisions', $this->getUserId(), $lpa->id),
            $primaryAttorneyDecisions->toArray()
        );

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
        $result = $this->executePut(
            sprintf(
                '/v2/user/%s/applications/%s/primary-attorneys/%s',
                $this->getUserId(),
                $lpa->id,
                $primaryAttorneyId
            ),
            $primaryAttorney->toArray()
        );

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
        $result = $this->executePut(
            sprintf(
                '/v2/user/%s/applications/%s/replacement-attorneys/%s',
                $this->getUserId(),
                $lpa->id,
                $replacementAttorneyId
            ),
            $replacementAttorney->toArray()
        );

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
    public function setReplacementAttorneyDecisions(
        Lpa $lpa,
        ReplacementAttorneyDecisions $replacementAttorneyDecisions
    ) {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/replacement-attorney-decisions', $this->getUserId(), $lpa->id),
            $replacementAttorneyDecisions->toArray()
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/certificate-provider', $this->getUserId(), $lpa->id),
            $certificateProvider->toArray()
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/notified-people/%s', $this->getUserId(), $lpa->id, $notifiedPersonId),
            $notifiedPerson->toArray()
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/who-is-registering', $this->getUserId(), $lpa->id),
            [
                'whoIsRegistering' => $whoIsRegistering,
            ]
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/correspondent', $this->getUserId(), $lpa->id),
            $correspondent->toArray()
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/repeat-case-number', $this->getUserId(), $lpa->id),
            [
                'repeatCaseNumber' => $repeatCaseNumber,
            ]
        );

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
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/payment', $this->getUserId(), $lpa->id),
            $payment->toArray()
        );

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
        $target = sprintf(
            '/v2/user/%s/applications/%s/primary-attorneys/%s',
            $this->getUserId(),
            $lpa->id,
            $primaryAttorneyId
        );

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
        $target = sprintf(
            '/v2/user/%s/applications/%s/replacement-attorneys/%s',
            $this->getUserId(),
            $lpa->id,
            $replacementAttorneyId
        );

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
        $target = sprintf(
            '/v2/user/%s/applications/%s/notified-people/%s',
            $this->getUserId(),
            $lpa->id,
            $notifiedPersonId
        );

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
            $result = $this->apiClient->httpPost(
                sprintf('/v2/user/%s/applications/%s/lock', $this->getUserId(), $lpa->id)
            );

            if (is_array($result)) {
                $lpa->locked = true;

                return true;
            }
        } catch (ApiException $ex) {
        }

        return false;
    }

    /**
     * Gathers an array on conditions where continuation sheet(s) would be generated in the PDF.
     *
     * @param Lpa $lpa
     * @return array
     */
    public function getContinuationNoteKeys(Lpa $lpa)
    {
        //Array of keys to know which extra notes to show in template for continuation sheets
        $continuationNoteKeys = [];
        $extraBlockPeople = null;
        $paCount = count($lpa->document->primaryAttorneys);
        $raCount = count($lpa->document->replacementAttorneys);
        $pnCount = count($lpa->document->peopleToNotify);

        if ($paCount > 4 && $raCount > 2 && $pnCount > 4) {
            $extraBlockPeople = 'ALL_PEOPLE_OVERFLOW';
        } elseif ($paCount > 4 && $raCount > 2) {
            $extraBlockPeople =  'ALL_ATTORNEY_OVERFLOW';
        } elseif ($paCount > 4 && $pnCount > 4) {
            $extraBlockPeople =  'PRIMARY_ATTORNEY_AND_NOTIFY_OVERFLOW';
        } elseif ($raCount > 2 &&  $pnCount > 4) {
            $extraBlockPeople =  'REPLACEMENT_ATTORNEY_AND_NOTIFY_OVERFLOW';
        } elseif ($paCount > 4) {
            $extraBlockPeople =  'PRIMARY_ATTORNEY_OVERFLOW';
        } elseif ($raCount > 2) {
            $extraBlockPeople =  'REPLACEMENT_ATTORNEY_OVERFLOW';
        } elseif ($pnCount > 4) {
            $extraBlockPeople =  'NOTIFY_OVERFLOW';
        }

        if ($extraBlockPeople != null) {
            array_push($continuationNoteKeys, $extraBlockPeople);
        }

        if ($paCount > 4 || $raCount > 2 || $pnCount > 4) {
            array_push($continuationNoteKeys, 'ANY_PEOPLE_OVERFLOW');
        }

        if (
            isset($lpa->document->primaryAttorneyDecisions->howDetails) ||
            isset($lpa->document->replacementAttorneyDecisions->howDetails) ||
            isset($lpa->document->replacementAttorneyDecisions->when)
        ) {
            array_push($continuationNoteKeys, 'HAS_ATTORNEY_DECISIONS');
        }

        if (isset($lpa->document->donor)) {
            if (!$lpa->document->donor->canSign) {
                array_push($continuationNoteKeys, 'CANT_SIGN');
            }
        }

        $someAttorneyIsTrustCorp = false;

        foreach ($lpa->document->primaryAttorneys as $attorney) {
            if (isset($attorney->number)) {
                $someAttorneyIsTrustCorp = true;
            }
        }

        foreach ($lpa->document->replacementAttorneys as $attorney) {
            if (isset($attorney->number)) {
                $someAttorneyIsTrustCorp = true;
            }
        }

        if ($someAttorneyIsTrustCorp) {
            array_push($continuationNoteKeys, 'HAS_TRUST_CORP');
        }

        // The following line is taken from the PDF service.
        $allowedChars = (LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_WIDTH + 2) *
          LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_COUNT;
        $lpaDocument = $lpa->getDocument();
        if (
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getPreference())) > $allowedChars ||
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getInstruction())) > $allowedChars
        ) {
            array_push($continuationNoteKeys, 'LONG_INSTRUCTIONS_OR_PREFERENCES');
        }

        return $continuationNoteKeys;
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
        } catch (ApiException $ex) {
        }

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
        } catch (ApiException $ex) {
        }

        return false;
    }
}
