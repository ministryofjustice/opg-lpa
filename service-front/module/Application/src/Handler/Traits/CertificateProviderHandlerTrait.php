<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Attorneys;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use RuntimeException;

/**
 * Shared logic for Certificate Provider handlers.
 *
 * @psalm-require-implements \Psr\Http\Server\RequestHandlerInterface
 */
trait CertificateProviderHandlerTrait
{
    /**
     * Build the actor list for duplicate detection on certificate provider forms.
     *
     * For certificate provider routes this includes: donor, primary attorneys,
     * replacement attorneys. It excludes: the certificate provider itself and
     * people to notify (matching the original controller filtering).
     *
     * @return array<int, array{firstname: string, lastname: string, type: string}>
     */
    private function getActorsList(Lpa $lpa): array
    {
        $actorsList = [];
        $lpaDocument = $lpa->document;

        if ($lpaDocument->donor instanceof Donor && $lpaDocument->donor->name !== null) {
            $actorsList[] = [
                'firstname' => $lpaDocument->donor->name->first,
                'lastname' => $lpaDocument->donor->name->last,
                'type' => 'donor',
            ];
        }

        foreach ($lpaDocument->primaryAttorneys as $attorney) {
            if ($attorney instanceof Attorneys\Human && $attorney->name !== null) {
                $actorsList[] = [
                    'firstname' => $attorney->name->first,
                    'lastname' => $attorney->name->last,
                    'type' => 'attorney',
                ];
            }
        }

        foreach ($lpaDocument->replacementAttorneys as $attorney) {
            if ($attorney instanceof Attorneys\Human && $attorney->name !== null) {
                $actorsList[] = [
                    'firstname' => $attorney->name->first,
                    'lastname' => $attorney->name->last,
                    'type' => 'replacement attorney',
                ];
            }
        }

        return $actorsList;
    }


    /**
     * Update or delete correspondent data when the certificate provider is also the correspondent.
     */
    private function updateCorrespondentData(Lpa $lpa, CertificateProvider $actor, bool $isDelete = false): void
    {
        $correspondent = $lpa->document->correspondent;

        if (!$correspondent instanceof Correspondence) {
            return;
        }

        if ($correspondent->who !== Correspondence::WHO_CERTIFICATE_PROVIDER) {
            return;
        }

        if ($isDelete) {
            if (!$this->lpaApplicationService->deleteCorrespondent($lpa)) {
                throw new RuntimeException(
                    'API client failed to delete correspondent for id: ' . $lpa->id
                );
            }
        } else {
            if ($actor->name != $correspondent->name || $actor->address != $correspondent->address) {
                $correspondentData = $correspondent->toArray();
                unset($correspondentData['name']);
                $updatedCorrespondent = new Correspondence($correspondentData);

                if ($actor->name !== null) {
                    $updatedCorrespondent->name = new LongName($actor->name->flatten());
                }

                $updatedCorrespondent->address = $actor->address;

                if (!$this->lpaApplicationService->setCorrespondent($lpa, $updatedCorrespondent)) {
                    throw new RuntimeException(
                        'API client failed to update correspondent for id: ' . $lpa->id
                    );
                }
            }
        }
    }
}
