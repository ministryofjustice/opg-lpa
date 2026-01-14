<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;

/**
 * Framework-agnostic service for generating accordion navigation data.
 *
 * Replaces the old Accordion view helper. Route name is passed as a parameter,
 * making it compatible with both Laminas MVC controllers and Mezzio handlers.
 */
class AccordionService
{
    /**
     * @var array<int, string>
     */
    private const BARS = [
        'lpa/form-type',
        'lpa/donor',
        'lpa/when-lpa-starts',
        'lpa/life-sustaining',
        'lpa/primary-attorney',
        'lpa/how-primary-attorneys-make-decision',
        'lpa/replacement-attorney',
        'lpa/when-replacement-attorney-step-in',
        'lpa/how-replacement-attorneys-make-decision',
        'lpa/certificate-provider',
        'lpa/people-to-notify',
        'lpa/instructions',
        'lpa/applicant',
        'lpa/correspondent',
        'lpa/who-are-you',
        'lpa/repeat-application',
        'lpa/fee-reduction',
    ];

    /**
     * @return array<int, array{routeName: string}>
     */
    public function getTopBars(?Lpa $lpa, string $currentRoute): array
    {
        $barsInPlay = [];

        if (!$lpa instanceof Lpa) {
            return $barsInPlay;
        }

        $flowChecker = new FormFlowChecker($lpa);
        $includeUpToRoute = $flowChecker->backToForm();

        $includeUpToIndex = $this->getRouteIndex($includeUpToRoute);
        $currentRouteIndex = $this->getRouteIndex($currentRoute);

        if ($includeUpToIndex < $currentRouteIndex) {
            $includeUpToRoute = $currentRoute;
        }

        foreach (self::BARS as $route) {
            if ($includeUpToRoute === $route) {
                break;
            }

            if ($currentRoute === $route) {
                break;
            }

            if ($route === $flowChecker->getNearestAccessibleRoute($route)) {
                $barsInPlay[] = ['routeName' => $route];
            }
        }

        if ($lpa->isStateCreated()) {
            $barsInPlay[] = ['routeName' => 'review-link'];
        }

        return $barsInPlay;
    }

    /**
     * @return array<int, array{routeName: string}>
     */
    public function getBottomBars(?Lpa $lpa, string $currentRoute): array
    {
        $barsInPlay = [];

        if (!$lpa instanceof Lpa) {
            return $barsInPlay;
        }

        $flowChecker = new FormFlowChecker($lpa);
        $includeUpToRoute = $flowChecker->backToForm();

        $startAt = $this->getRouteIndex($currentRoute);
        $bars = array_slice(self::BARS, $startAt + 1);

        foreach ($bars as $key => $route) {
            if ($route !== $flowChecker->getNearestAccessibleRoute($route)) {
                if ($includeUpToRoute === $route) {
                    break;
                }
                continue;
            }

            if (isset($bars[$key + 1])) {
                foreach (array_slice($bars, $key + 1) as $futureRoute) {
                    if ($futureRoute === $flowChecker->getNearestAccessibleRoute($futureRoute)) {
                        $barsInPlay[] = ['routeName' => $route];
                        break;
                    }
                }
            } elseif ($route === 'lpa/fee-reduction') {
                if ($lpa->payment instanceof Payment) {
                    $barsInPlay[] = ['routeName' => $route];
                }
            }

            if ($includeUpToRoute === $route) {
                break;
            }
        }

        return $barsInPlay;
    }

    private function getRouteIndex(string $route): int
    {
        $index = array_search($route, self::BARS, true);
        return is_int($index) ? $index : 0;
    }
}
