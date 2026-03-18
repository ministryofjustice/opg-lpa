<?php

declare(strict_types=1);

namespace Application\Model\Service\Lpa;

use Application\Model\Service\Session\SessionUtility;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Attorneys;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;

/**
 * Provides actor reuse details for LPA forms, extracted from AbstractLpaActorController
 * for use in Mezzio PSR-15 handlers.
 */
class ActorReuseDetailsService
{
    public function __construct(
        private readonly Application $lpaApplicationService,
        private readonly SessionUtility $sessionUtility,
    ) {
    }

    /**
     * Return reuse details for non-correspondent actor forms (donor, attorney, certificate provider, etc.)
     * Mirrors AbstractLpaActorController::getActorReuseDetails() for the non-correspondent case.
     */
    public function getActorReuseDetails(User $user, Lpa $lpa, bool $includeTrusts = true): array
    {
        $actorReuseDetails = [];

        $this->addCurrentUserDetailsForReuse($user, $lpa, $actorReuseDetails);

        $seedActorDetails = $this->getSeedLpaActorDetails($lpa);

        foreach ($seedActorDetails as $type => $actorData) {
            $actorType = ($actorData['who'] ?? 'other');

            switch ($type) {
                case 'donor':
                    $actorReuseDetails[] = $this->getReuseDetailsForActor(
                        $actorData,
                        'donor',
                        '(was the donor)'
                    );
                    break;

                case 'correspondent':
                    if ($actorType === 'other') {
                        $actorReuseDetails[] = $this->getReuseDetailsForActor(
                            $actorData,
                            $actorType,
                            '(was the correspondent)'
                        );
                    }
                    break;

                case 'certificateProvider':
                    $actorReuseDetails[] = $this->getReuseDetailsForActor(
                        $actorData,
                        $actorType,
                        '(was the certificate provider)'
                    );
                    break;

                case 'primaryAttorneys':
                case 'replacementAttorneys':
                    $suffixText = $type === 'replacementAttorneys'
                        ? '(was a replacement attorney)'
                        : '(was a primary attorney)';

                    foreach ($actorData as $singleActorData) {
                        $isTrust = ($singleActorData['type'] === 'trust');

                        if ($isTrust && (!$includeTrusts || !$this->allowTrust($lpa))) {
                            continue;
                        }

                        $detail = $this->getReuseDetailsForActor($singleActorData, 'attorney', $suffixText);

                        if ($isTrust) {
                            $actorReuseDetails['t'] = $detail;
                        } else {
                            $actorReuseDetails[] = $detail;
                        }
                    }
                    break;

                case 'peopleToNotify':
                    foreach ($actorData as $singleActorData) {
                        $actorReuseDetails[] = $this->getReuseDetailsForActor(
                            $singleActorData,
                            $actorType,
                            '(was a person to be notified)'
                        );
                    }
                    break;
            }
        }

        return $actorReuseDetails;
    }

    /**
     * Add current user to the reuse details array if they haven't already been used as an actor.
     */
    private function addCurrentUserDetailsForReuse(User $user, Lpa $lpa, array &$actorReuseDetails): void
    {
        $shouldAdd = true;

        foreach ($this->getActorsList($lpa) as $actorsListItem) {
            if (
                strtolower($user->name->first) === strtolower($actorsListItem['firstname'])
                && strtolower($user->name->last) === strtolower($actorsListItem['lastname'])
            ) {
                $shouldAdd = false;
                break;
            }
        }

        if ($shouldAdd) {
            $userDetails = $user->flatten();
            $userDetails['who'] = 'other';

            if (($dateOfBirth = $user->dob) instanceof Dob) {
                $userDetails['dob-date'] = [
                    'day'   => $dateOfBirth->date->format('d'),
                    'month' => $dateOfBirth->date->format('m'),
                    'year'  => $dateOfBirth->date->format('Y'),
                ];
            }

            $actorReuseDetails[] = [
                'label' => sprintf('%s %s (myself)', $user->name->first, $user->name->last),
                'data'  => $userDetails,
            ];
        }
    }

    /**
     * Return a flat list of actors already on the LPA (excluding the donor role, since the donor
     * handler is adding/editing the donor and should not see themselves in the duplicate-check list).
     */
    public function getActorsList(Lpa $lpa, bool $excludeDonor = true): array
    {
        $actorsList = [];
        $lpaDocument = $lpa->document;

        if (!$excludeDonor && $lpaDocument->donor instanceof Donor) {
            $actorsList[] = $this->getActorDetails($lpaDocument->donor, 'donor');
        }

        if ($lpaDocument->certificateProvider instanceof CertificateProvider) {
            $actorsList[] = $this->getActorDetails($lpaDocument->certificateProvider, 'certificate provider');
        }

        foreach ($lpaDocument->primaryAttorneys as $attorney) {
            if ($attorney instanceof Attorneys\Human) {
                $actorsList[] = $this->getActorDetails($attorney, 'attorney');
            }
        }

        foreach ($lpaDocument->replacementAttorneys as $attorney) {
            if ($attorney instanceof Attorneys\Human) {
                $actorsList[] = $this->getActorDetails($attorney, 'replacement attorney');
            }
        }

        foreach ($lpaDocument->peopleToNotify as $person) {
            $actorsList[] = $this->getActorDetails($person, 'person to notify');
        }

        return $actorsList;
    }

    private function getActorDetails(mixed $actorData, string $actorType): array
    {
        if (
            isset($actorData->name)
            && ($actorData->name instanceof Name || $actorData->name instanceof LongName)
        ) {
            return [
                'firstname' => $actorData->name->first,
                'lastname'  => $actorData->name->last,
                'type'      => $actorType,
            ];
        }

        return [];
    }

    /**
     * Fetch seed LPA actor details, using the session as a cache.
     */
    private function getSeedLpaActorDetails(Lpa $lpa): array
    {
        $seedId = (string) $lpa->seed;

        if (empty($seedId)) {
            return [];
        }

        $sessionSeedData = $this->sessionUtility->getFromMvc('clone', $seedId);

        if ($sessionSeedData === null) {
            $seedDetails = $this->lpaApplicationService->getSeedDetails($lpa->id);
            $this->sessionUtility->setInMvc('clone', $seedId, $seedDetails);
            return is_array($seedDetails) ? $seedDetails : [];
        }

        return is_array($sessionSeedData) ? $sessionSeedData : [];
    }

    private function getReuseDetailsForActor(array $actorData, string $actorType, string $suffixText = ''): array
    {
        $actorData['who'] = $actorType;

        $label = $actorData['name'];

        if (isset($actorData['type']) && $actorData['type'] === 'trust') {
            $actorData['company'] = $label;
        } elseif (is_array($actorData['name'])) {
            $label = $actorData['name']['first'] . ' ' . $actorData['name']['last'];
        }

        $allowedKeys = ['name', 'number', 'otherNames', 'address', 'dob', 'email', 'case', 'phone',
            'who', 'company', 'type', 'canSign'];

        foreach (array_keys($actorData) as $key) {
            if (!in_array($key, $allowedKeys)) {
                unset($actorData[$key]);
            }
        }

        return [
            'label' => trim($label . ' ' . $suffixText),
            'data'  => $this->flattenData($actorData),
        ];
    }

    /**
     * Flatten nested actor data arrays into a single-level array.
     * Mirrors LpaLoaderTrait::flattenData().
     */
    private function flattenData(array $modelData): array
    {
        $formData = [];

        foreach ($modelData as $l1 => $l2) {
            if (is_array($l2)) {
                foreach ($l2 as $name => $l3) {
                    if ($l1 === 'dob') {
                        $dob = new \DateTime($l3);
                        $formData['dob-date'] = [
                            'day'   => $dob->format('d'),
                            'month' => $dob->format('m'),
                            'year'  => $dob->format('Y'),
                        ];
                    } else {
                        $formData[$l1 . '-' . $name] = $l3;
                    }
                }
            } else {
                $formData[$l1] = $l2;
            }
        }

        return $formData;
    }

    private function allowTrust(Lpa $lpa): bool
    {
        if ($lpa->document->type === Document::LPA_TYPE_HW) {
            return false;
        }

        $attorneys = array_merge(
            $lpa->document->primaryAttorneys,
            $lpa->document->replacementAttorneys
        );

        foreach ($attorneys as $attorney) {
            if ($attorney instanceof Attorneys\TrustCorporation) {
                return false;
            }
        }

        return true;
    }
}
