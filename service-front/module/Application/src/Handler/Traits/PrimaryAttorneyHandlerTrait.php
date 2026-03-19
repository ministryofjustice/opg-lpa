<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Psr\Http\Message\ServerRequestInterface;

trait PrimaryAttorneyHandlerTrait
{
    private function getActorsList(Lpa $lpa, ?int $excludeIdx = null): array
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

        if ($lpaDocument->certificateProvider instanceof CertificateProvider && $lpaDocument->certificateProvider->name !== null) {
            $actorsList[] = [
                'firstname' => $lpaDocument->certificateProvider->name->first,
                'lastname' => $lpaDocument->certificateProvider->name->last,
                'type' => 'certificate provider',
            ];
        }

        foreach ($lpaDocument->primaryAttorneys as $idx => $attorney) {
            if ($excludeIdx !== null && $excludeIdx === $idx) {
                continue;
            }

            if ($attorney instanceof Human && $attorney->name !== null) {
                $actorsList[] = [
                    'firstname' => $attorney->name->first,
                    'lastname' => $attorney->name->last,
                    'type' => 'attorney',
                ];
            }
        }

        foreach ($lpaDocument->peopleToNotify as $notifiedPerson) {
            if ($notifiedPerson->name !== null) {
                $actorsList[] = [
                    'firstname' => $notifiedPerson->name->first,
                    'lastname' => $notifiedPerson->name->last,
                    'type' => 'person to notify',
                ];
            }
        }

        return $actorsList;
    }

    private function getAllActorsList(Lpa $lpa): array
    {
        $actorsList = [];
        $lpaDocument = $lpa->document;

        if ($lpaDocument->donor instanceof Donor && $lpaDocument->donor->name !== null) {
            $actorsList[] = [
                'firstname' => $lpaDocument->donor->name->first,
                'lastname' => $lpaDocument->donor->name->last,
            ];
        }

        if ($lpaDocument->certificateProvider instanceof CertificateProvider && $lpaDocument->certificateProvider->name !== null) {
            $actorsList[] = [
                'firstname' => $lpaDocument->certificateProvider->name->first,
                'lastname' => $lpaDocument->certificateProvider->name->last,
            ];
        }

        foreach ($lpaDocument->primaryAttorneys as $attorney) {
            if ($attorney instanceof Human && $attorney->name !== null) {
                $actorsList[] = [
                    'firstname' => $attorney->name->first,
                    'lastname' => $attorney->name->last,
                ];
            }
        }

        foreach ($lpaDocument->replacementAttorneys as $attorney) {
            if ($attorney instanceof Human && $attorney->name !== null) {
                $actorsList[] = [
                    'firstname' => $attorney->name->first,
                    'lastname' => $attorney->name->last,
                ];
            }
        }

        foreach ($lpaDocument->peopleToNotify as $notifiedPerson) {
            if ($notifiedPerson->name !== null) {
                $actorsList[] = [
                    'firstname' => $notifiedPerson->name->first,
                    'lastname' => $notifiedPerson->name->last,
                ];
            }
        }

        return $actorsList;
    }

    private function allowTrust(Lpa $lpa): bool
    {
        if ($lpa->document->type !== Document::LPA_TYPE_HW) {
            $attorneys = array_merge(
                $lpa->document->primaryAttorneys,
                $lpa->document->replacementAttorneys
            );

            foreach ($attorneys as $attorney) {
                if ($attorney instanceof TrustCorporation) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function attorneyIsCorrespondent(Lpa $lpa, AbstractAttorney $attorney): bool
    {
        $correspondent = $lpa->document->correspondent;

        if ($correspondent instanceof Correspondence && $correspondent->who === Correspondence::WHO_ATTORNEY) {
            $nameToCompare = ($attorney instanceof TrustCorporation)
                ? $correspondent->company
                : new Name($correspondent->name->flatten());

            return ($attorney->name == $nameToCompare && $correspondent->address == $attorney->address);
        }

        return false;
    }

    private function updateCorrespondentData(Lpa $lpa, AbstractAttorney $actor, bool $isDelete = false): void
    {
        $correspondent = $lpa->document->correspondent;

        if (!$correspondent instanceof Correspondence) {
            return;
        }

        $isAttorney = ($actor instanceof AbstractAttorney && $correspondent->who === Correspondence::WHO_ATTORNEY);

        if (!$isAttorney) {
            return;
        }

        if ($isDelete) {
            if (!$this->lpaApplicationService->deleteCorrespondent($lpa)) {
                throw new \RuntimeException(
                    'API client failed to delete correspondent for id: ' . $lpa->id
                );
            }
        } else {
            $isTrust = ($actor instanceof TrustCorporation);
            $nameToCompare = $isTrust ? $correspondent->name : $correspondent->company;

            if ($actor->name != $nameToCompare || $actor->address != $correspondent->address) {
                $correspondentData = $correspondent->toArray();
                unset($correspondentData['name']);
                $correspondent = new Correspondence($correspondentData);

                if ($isTrust) {
                    $correspondent->company = $actor->name;
                } else {
                    $correspondent->name = new \MakeShared\DataModel\Common\LongName($actor->name->flatten());
                }

                $correspondent->address = $actor->address;

                if (!$this->lpaApplicationService->setCorrespondent($lpa, $correspondent)) {
                    throw new \RuntimeException(
                        'API client failed to update correspondent for id: ' . $lpa->id
                    );
                }
            }
        }
    }

    private function isXmlHttpRequest(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
}
