<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Authentication\AuthenticationService;
use App\Service\ApiClient\ApiClientAwareInterface;
use App\Service\ApiClient\ApiClientTrait;
use App\Service\ApiClient\Exception\ApiException;
use ArrayObject;
use DateTime;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Formatter as LpaFormatter;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class Application implements ApiClientAwareInterface, LoggerAwareInterface
{
    use ApiClientTrait;
    use LoggerTrait;

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly array $config,
    ) {
    }

    public function getAuthenticationService(): AuthenticationService
    {
        return $this->authenticationService;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    protected function getUserId(): ?string
    {
        return $this->authenticationService->getIdentity()?->id();
    }

    // -------------------------------------------------------------------------
    // Application CRUD
    // -------------------------------------------------------------------------

    public function getApplication(int|string $lpaId, #[\SensitiveParameter] ?string $token = null): Lpa|false
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
                'lpaId'     => $lpaId,
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    /**
     * @return (false[]|mixed)[]|string
     *
     * @psalm-return array<array{found: false}|mixed>|string
     */
    public function getStatuses(string $ids): array|string
    {
        $target = sprintf('/v2/user/%s/statuses/%s', $this->getUserId(), $ids);

        try {
            $result = $this->apiClient->httpGet($target);
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to fetch LPA statuses', [
                'ids'       => $ids,
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
            $result = null;
        }

        if ($result === null) {
            $result = [];
            foreach (explode(',', $ids) as $id) {
                $result[$id] = ['found' => false];
            }
            return $result;
        }

        return $result;
    }

    public function createApplication(): Lpa|false
    {
        try {
            return new Lpa(
                $this->apiClient->httpPost(sprintf('/v2/user/%s/applications', $this->getUserId()))
            );
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to create LPA Application', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function updateApplication(int|string $lpaId, array $data): Lpa|false
    {
        $target = sprintf('/v2/user/%s/applications/%d', $this->getUserId(), $lpaId);

        try {
            return new Lpa($this->apiClient->httpPatch($target, $data));
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to update application', [
                'lpaId'            => $lpaId,
                'status'           => $ex->getStatusCode(),
                'validationErrors' => $ex->getBody('validation'),
                'dataKeys'         => array_keys($data),
                'exception'        => $ex,
            ]);
        }

        return false;
    }

    public function deleteApplication(int|string $lpaId): bool
    {
        return $this->executeDelete(sprintf('/v2/user/%s/applications/%d', $this->getUserId(), $lpaId));
    }

    public function getLpaSummaries(?string $search = null, ?int $page = null, ?int $itemsPerPage = null): array
    {
        $queryParams = ['search' => $search];

        if ($page > 0 && $itemsPerPage > 0) {
            $queryParams = array_merge($queryParams, [
                'page'    => $page,
                'perPage' => $itemsPerPage,
            ]);
        }

        $result = ['applications' => []];

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
                'queryParams' => $queryParams,
                'status'      => $ex->getStatusCode(),
                'exception'   => $ex,
            ]);
        }

        $trackFromDate   = new DateTime($this->config['processing-status']['track-from-date']);
        $trackingEnabled = $trackFromDate <= new DateTime('now');

        $result['trackingEnabled'] = $trackingEnabled;

        foreach ($result['applications'] as $applicationIdx => $applicationData) {
            $donorName = '';
            $lpaType   = '';
            $lpa       = new Lpa($applicationData);
            $metadata  = $lpa->getMetadata();

            $isReusable = array_key_exists(Lpa::PEOPLE_TO_NOTIFY_CONFIRMED, $metadata);

            if ($lpa->hasDonor() && $lpa->document->donor->name instanceof LongName) {
                $donorName = (string) $lpa->document->donor->name;
            }

            if (!is_null($lpa->document->type)) {
                $lpaType = $lpa->document->type;
            }

            $progress        = 'Started';
            $refreshTracking = false;
            $rejectedDate    = null;

            if ($lpa->getCompletedAt() instanceof DateTime) {
                $progress = 'Completed';

                if ($trackingEnabled && $trackFromDate <= $lpa->getCompletedAt()) {
                    $progress = 'Waiting';

                    if (array_key_exists(Lpa::SIRIUS_PROCESSING_STATUS, $metadata)) {
                        $processingStatus = $metadata[Lpa::SIRIUS_PROCESSING_STATUS];

                        if ($processingStatus !== null) {
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

            $result['applications'][$applicationIdx] = new ArrayObject([
                'id'          => $lpa->getId(),
                'version'     => 2,
                'donor'       => $donorName,
                'isReusable'  => $isReusable,
                'type'        => $lpaType,
                'updatedAt'   => $lpa->getUpdatedAt(),
                'progress'    => $progress,
                'rejectedDate' => $rejectedDate,
                'refreshId'   => $refreshTracking ? $lpa->getId() : null,
            ]);
        }

        return $result;
    }

    public function getSeedDetails(int|string $lpaId): mixed
    {
        try {
            return $this->apiClient->httpGet(
                sprintf('/v2/user/%s/applications/%s/seed', $this->getUserId(), $lpaId)
            );
        } catch (ApiException $ex) {
            $this->getLogger()->warning('Failed to fetch ID of seed LPA document', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function getPdf(int|string $lpaId, string $pdfType): mixed
    {
        try {
            return $this->apiClient->httpGet(
                sprintf('/v2/user/%s/applications/%s/pdfs/%s', $this->getUserId(), $lpaId, $pdfType),
            );
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to fetch PDF details', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function getPdfContents(int|string $lpaId, string $pdfType): array|false|null|string
    {
        try {
            return $this->apiClient->httpGet(
                sprintf('/v2/user/%s/applications/%s/pdfs/%s.pdf', $this->getUserId(), $lpaId, $pdfType),
                [],
                false,
                false,
                ['Accept' => 'application/pdf'],
            );
        } catch (ApiException $ex) {
            $this->getLogger()->warning('Failed to fetch PDF contents', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Attorneys
    // -------------------------------------------------------------------------

    public function addPrimaryAttorney(Lpa $lpa, AbstractAttorney $primaryAttorney): bool
    {
        $target = sprintf('/v2/user/%s/applications/%s/primary-attorneys', $this->getUserId(), $lpa->id);

        try {
            $result = $this->apiClient->httpPost($target, $primaryAttorney->toArray());

            if (is_array($result)) {
                $lpa->document->primaryAttorneys[] = $primaryAttorney instanceof Human
                    ? new Human($result)
                    : new TrustCorporation($result);

                return true;
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to add a new primary attorney', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function addReplacementAttorney(Lpa $lpa, AbstractAttorney $replacementAttorney): bool
    {
        $target = sprintf('/v2/user/%s/applications/%s/replacement-attorneys', $this->getUserId(), $lpa->id);

        try {
            $result = $this->apiClient->httpPost($target, $replacementAttorney->toArray());

            if (is_array($result)) {
                $lpa->document->replacementAttorneys[] = $replacementAttorney instanceof Human
                    ? new Human($result)
                    : new TrustCorporation($result);

                return true;
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to add a new replacement attorney', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function addNotifiedPerson(Lpa $lpa, NotifiedPerson $notifiedPerson): bool
    {
        $target = sprintf('/v2/user/%s/applications/%s/notified-people', $this->getUserId(), $lpa->id);

        try {
            $result = $this->apiClient->httpPost($target, $notifiedPerson->toArray());

            if (is_array($result)) {
                $lpa->document->peopleToNotify[] = new NotifiedPerson($result);
                return true;
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to add a new notified person', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function setPrimaryAttorney(Lpa $lpa, AbstractAttorney $primaryAttorney, int|string $primaryAttorneyId): bool
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/primary-attorneys/%s', $this->getUserId(), $lpa->id, $primaryAttorneyId),
            $primaryAttorney->toArray()
        );

        if (is_array($result)) {
            foreach ($lpa->document->primaryAttorneys as $idx => $attorney) {
                if ($attorney->id == $primaryAttorneyId) {
                    $lpa->document->primaryAttorneys[$idx] = $attorney instanceof Human
                        ? new Human($result)
                        : new TrustCorporation($result);
                    break;
                }
            }
            return true;
        }

        return false;
    }

    public function setReplacementAttorney(Lpa $lpa, AbstractAttorney $replacementAttorney, int|string $replacementAttorneyId): bool
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/replacement-attorneys/%s', $this->getUserId(), $lpa->id, $replacementAttorneyId),
            $replacementAttorney->toArray()
        );

        if (is_array($result)) {
            foreach ($lpa->document->replacementAttorneys as $idx => $attorney) {
                if ($attorney->id == $replacementAttorneyId) {
                    $lpa->document->replacementAttorneys[$idx] = $attorney instanceof Human
                        ? new Human($result)
                        : new TrustCorporation($result);
                    break;
                }
            }
            return true;
        }

        return false;
    }

    public function deletePrimaryAttorney(Lpa $lpa, int|string $primaryAttorneyId): bool
    {
        $target = sprintf('/v2/user/%s/applications/%s/primary-attorneys/%s', $this->getUserId(), $lpa->id, $primaryAttorneyId);

        if ($this->executeDelete($target)) {
            foreach ($lpa->document->primaryAttorneys as $idx => $attorney) {
                if ($attorney->id == $primaryAttorneyId) {
                    unset($lpa->document->primaryAttorneys[$idx]);
                    break;
                }
            }
            return true;
        }

        return false;
    }

    public function deleteReplacementAttorney(Lpa $lpa, int|string $replacementAttorneyId): bool
    {
        $target = sprintf('/v2/user/%s/applications/%s/replacement-attorneys/%s', $this->getUserId(), $lpa->id, $replacementAttorneyId);

        if ($this->executeDelete($target)) {
            foreach ($lpa->document->replacementAttorneys as $idx => $attorney) {
                if ($attorney->id == $replacementAttorneyId) {
                    unset($lpa->document->replacementAttorneys[$idx]);
                    break;
                }
            }
            return true;
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Document properties
    // -------------------------------------------------------------------------

    public function setWhoAreYou(Lpa $lpa, WhoAreYou $whoAreYou): bool
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

    public function setType(Lpa $lpa, string $lpaType): bool
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/type', $this->getUserId(), $lpa->id),
            ['type' => $lpaType]
        );

        if (is_array($result)) {
            $lpa->document->type = $result['type'];
            return true;
        }

        return false;
    }

    public function setDonor(Lpa $lpa, Donor $donor): bool
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

    public function setPrimaryAttorneyDecisions(Lpa $lpa, PrimaryAttorneyDecisions $primaryAttorneyDecisions): bool
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

    public function setReplacementAttorneyDecisions(Lpa $lpa, ReplacementAttorneyDecisions $replacementAttorneyDecisions): bool
    {
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

    public function setCertificateProvider(Lpa $lpa, CertificateProvider $certificateProvider): bool
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

    public function setNotifiedPerson(Lpa $lpa, NotifiedPerson $notifiedPerson, int|string $notifiedPersonId): bool
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/notified-people/%s', $this->getUserId(), $lpa->id, $notifiedPersonId),
            $notifiedPerson->toArray()
        );

        if (is_array($result)) {
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

    public function setPreferences(Lpa $lpa, mixed $preferences): bool
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/preference', $this->getUserId(), $lpa->id),
            ['preference' => $preferences]
        );

        if (is_array($result)) {
            $lpa->document->preference = $result['preference'];
            return true;
        }

        return false;
    }

    public function setInstructions(Lpa $lpa, mixed $instructions): mixed
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/instruction', $this->getUserId(), $lpa->id),
            ['instruction' => $instructions]
        );

        if (is_array($result)) {
            $lpa->document->instruction = $result['instruction'];
            return true;
        }

        return false;
    }

    public function setWhoIsRegistering(Lpa $lpa, array|string|null $whoIsRegistering): bool
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/who-is-registering', $this->getUserId(), $lpa->id),
            ['whoIsRegistering' => $whoIsRegistering]
        );

        if (is_array($result)) {
            $lpa->document->whoIsRegistering = $result['whoIsRegistering'] ?? null;
            return true;
        }

        return false;
    }

    public function setCorrespondent(Lpa $lpa, Correspondence $correspondent): bool
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

    public function setRepeatCaseNumber(Lpa $lpa, mixed $repeatCaseNumber): mixed
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/repeat-case-number', $this->getUserId(), $lpa->id),
            ['repeatCaseNumber' => $repeatCaseNumber]
        );

        if (is_array($result)) {
            $lpa->repeatCaseNumber = $result['repeatCaseNumber'];
            return true;
        }

        return false;
    }

    public function setPayment(Lpa $lpa, Payment $payment): bool
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

    public function setSeed(Lpa $lpa, string $seedId): bool
    {
        $result = $this->executePut(
            sprintf('/v2/user/%s/applications/%s/seed', $this->getUserId(), $lpa->id),
            ['seed' => $seedId]
        );

        if (is_array($result)) {
            $lpa->seed = $result['seed'];
            return true;
        }

        return false;
    }

    public function deleteCertificateProvider(Lpa $lpa): bool
    {
        if ($this->executeDelete(sprintf('/v2/user/%s/applications/%s/certificate-provider', $this->getUserId(), $lpa->id))) {
            $lpa->document->certificateProvider = null;
            return true;
        }

        return false;
    }

    public function deleteNotifiedPerson(Lpa $lpa, int|string $notifiedPersonId): bool
    {
        $target = sprintf('/v2/user/%s/applications/%s/notified-people/%s', $this->getUserId(), $lpa->id, $notifiedPersonId);

        if ($this->executeDelete($target)) {
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

    public function deleteCorrespondent(Lpa $lpa): bool
    {
        if ($this->executeDelete(sprintf('/v2/user/%s/applications/%s/correspondent', $this->getUserId(), $lpa->id))) {
            $lpa->document->correspondent = null;
            return true;
        }

        return false;
    }

    public function deleteRepeatCaseNumber(Lpa $lpa): bool
    {
        if ($this->executeDelete(sprintf('/v2/user/%s/applications/%s/repeat-case-number', $this->getUserId(), $lpa->id))) {
            $lpa->repeatCaseNumber = null;
            return true;
        }

        return false;
    }

    public function lockLpa(Lpa $lpa): bool
    {
        try {
            $result = $this->apiClient->httpPost(
                sprintf('/v2/user/%s/applications/%s/lock', $this->getUserId(), $lpa->id)
            );

            if (is_array($result)) {
                $lpa->locked = true;
                return true;
            }
        } catch (ApiException) {
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Continuation notes
    // -------------------------------------------------------------------------

    public function getContinuationNoteKeys(Lpa $lpa): array
    {
        $continuationNoteKeys = [];
        $paCount = count($lpa->document->primaryAttorneys);
        $raCount = count($lpa->document->replacementAttorneys);
        $pnCount = count($lpa->document->peopleToNotify);

        $extraBlockPeople = match (true) {
            $paCount > 4 && $raCount > 2 && $pnCount > 4 => 'ALL_PEOPLE_OVERFLOW',
            $paCount > 4 && $raCount > 2                  => 'ALL_ATTORNEY_OVERFLOW',
            $paCount > 4 && $pnCount > 4                  => 'PRIMARY_ATTORNEY_AND_NOTIFY_OVERFLOW',
            $raCount > 2 && $pnCount > 4                  => 'REPLACEMENT_ATTORNEY_AND_NOTIFY_OVERFLOW',
            $paCount > 4                                   => 'PRIMARY_ATTORNEY_OVERFLOW',
            $raCount > 2                                   => 'REPLACEMENT_ATTORNEY_OVERFLOW',
            $pnCount > 4                                   => 'NOTIFY_OVERFLOW',
            default                                        => null,
        };

        if ($extraBlockPeople !== null) {
            $continuationNoteKeys[] = $extraBlockPeople;
        }

        if ($paCount > 4 || $raCount > 2 || $pnCount > 4) {
            $continuationNoteKeys[] = 'ANY_PEOPLE_OVERFLOW';
        }

        if (
            isset($lpa->document->primaryAttorneyDecisions->howDetails) ||
            isset($lpa->document->replacementAttorneyDecisions->howDetails) ||
            isset($lpa->document->replacementAttorneyDecisions->when)
        ) {
            $continuationNoteKeys[] = 'HAS_ATTORNEY_DECISIONS';
        }

        if (isset($lpa->document->donor) && !$lpa->document->donor->canSign) {
            $continuationNoteKeys[] = 'CANT_SIGN';
        }

        $someAttorneyIsTrustCorp = false;
        foreach ([...$lpa->document->primaryAttorneys, ...$lpa->document->replacementAttorneys] as $attorney) {
            if (isset($attorney->number)) {
                $someAttorneyIsTrustCorp = true;
                break;
            }
        }

        if ($someAttorneyIsTrustCorp) {
            $continuationNoteKeys[] = 'HAS_TRUST_CORP';
        }

        $allowedChars = (LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_WIDTH + 2) * LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_COUNT;
        $lpaDocument  = $lpa->getDocument();

        if (
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getPreference())) > $allowedChars ||
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getInstruction())) > $allowedChars
        ) {
            $continuationNoteKeys[] = 'LONG_INSTRUCTIONS_OR_PREFERENCES';
        }

        return $continuationNoteKeys;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function executePut(string $target, array $jsonBody): mixed
    {
        try {
            return $this->apiClient->httpPut($target, $jsonBody);
        } catch (ApiException) {
        }

        return false;
    }

    private function executeDelete(string $target): bool
    {
        try {
            $this->apiClient->httpDelete($target);
            return true;
        } catch (ApiException) {
        }

        return false;
    }
}
