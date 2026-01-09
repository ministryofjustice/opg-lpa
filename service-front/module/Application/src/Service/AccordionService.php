<?php

namespace Application\Service;

use Application\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;

class AccordionService
{
    /** @param string[] $bars */
    public function __construct(private array $bars)
    {
    }

    /** @return array<int, array{routeName: string}> */
    public function top(Lpa $lpa, string $currentRouteName): array
    {
        $flowChecker = new FormFlowChecker($lpa);
        $includeUpToRoute = $flowChecker->backToForm();

        if ($this->indexOf($includeUpToRoute) < $this->indexOf($currentRouteName)) {
            $includeUpToRoute = $currentRouteName;
        }

        $items = [];

        foreach ($this->bars as $route) {
            if ($includeUpToRoute === $route || $currentRouteName === $route) {
                break;
            }

            if ($route === $flowChecker->getNearestAccessibleRoute($route)) {
                $items[] = ['routeName' => $route];
            }
        }

        if ($lpa->isStateCreated()) {
            $items[] = ['routeName' => 'review-link'];
        }

        return $items;
    }

    /** @return array<int, array{routeName: string}> */
    public function bottom(Lpa $lpa, string $currentRouteName): array
    {
        $flowChecker = new FormFlowChecker($lpa);
        $includeUpToRoute = $flowChecker->backToForm();

        $startAt = $this->indexOf($currentRouteName);
        $startAt = $startAt >= 0 ? $startAt : 0;
        $bars = array_values(array_slice($this->bars, $startAt + 1));

        $items = [];

        foreach ($bars as $key => $route) {
            if ($route === $flowChecker->getNearestAccessibleRoute($route)) {
                if (isset($bars[$key + 1])) {
                    foreach (array_slice($bars, $key + 1) as $futureRoute) {
                        if ($futureRoute === $flowChecker->getNearestAccessibleRoute($futureRoute)) {
                            $items[] = ['routeName' => $route];
                            break;
                        }
                    }
                } elseif ($route === 'lpa/fee-reduction') {
                    if ($lpa->payment instanceof Payment) {
                        $items[] = ['routeName' => $route];
                    }
                }
            }

            if ($includeUpToRoute === $route) {
                break;
            }
        }

        return $items;
    }

    private function indexOf(?string $routeName): int
    {
        $idx = array_search($routeName, $this->bars, true);
        return is_int($idx) ? $idx : -1;
    }
}
