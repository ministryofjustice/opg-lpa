<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys;
use MakeShared\DataModel\Lpa\Lpa;

/**
 * Shared logic for People to Notify handlers.
 *
 * @psalm-require-implements \Psr\Http\Server\RequestHandlerInterface
 */
trait PeopleToNotifyHandlerTrait
{
    /**
     * Build the actor list for duplicate detection on people-to-notify forms.
     *
     * For people-to-notify routes this excludes: donor, certificate provider
     * (matching the original controller filtering for $isPeopleToModifyRoute).
     * It includes: primary attorneys, replacement attorneys, and other people to notify
     * (optionally excluding one by index).
     *
     * @return array<int, array{firstname: string, lastname: string, type: string}>
     */
    private function getActorsList(Lpa $lpa, ?int $actorIndexToExclude = null): array
    {
        $actorsList = [];
        $lpaDocument = $lpa->document;

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

        foreach ($lpaDocument->peopleToNotify as $idx => $notifiedPerson) {
            if ($actorIndexToExclude === $idx) {
                continue;
            }

            if (
                isset($notifiedPerson->name)
                && ($notifiedPerson->name instanceof Name || $notifiedPerson->name instanceof LongName)
            ) {
                $actorsList[] = [
                    'firstname' => $notifiedPerson->name->first,
                    'lastname' => $notifiedPerson->name->last,
                    'type' => 'person to notify',
                ];
            }
        }

        return $actorsList;
    }
}
