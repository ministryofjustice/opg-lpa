<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use DateTime;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Router\Http\RouteMatch;
use Laminas\View\Model\JsonModel;
use MakeShared\DataModel\Lpa\Lpa;
use RuntimeException;

trait LpaLoaderTrait
{
    protected ?Metadata $metadata = null;
    protected ?ReplacementAttorneyCleanup $replacementAttorneyCleanup = null;

    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    protected function getMetadata(): Metadata
    {
        if ($this->metadata === null) {
            throw new RuntimeException('Metadata not set. Ensure controller is configured correctly.');
        }
        return $this->metadata;
    }

    public function setReplacementAttorneyCleanup(ReplacementAttorneyCleanup $replacementAttorneyCleanup): void
    {
        $this->replacementAttorneyCleanup = $replacementAttorneyCleanup;
    }

    protected function cleanUpReplacementAttorneyDecisions(): void
    {
        if ($this->replacementAttorneyCleanup === null) {
            throw new RuntimeException('ReplacementAttorneyCleanup not set. Ensure controller is configured correctly.');
        }
        $this->replacementAttorneyCleanup->cleanUp($this->getLpa());
    }

    protected function getLpa(): Lpa
    {
        return $this->getEvent()->getParam(LpaLoaderListener::ATTR_LPA);
    }

    protected function getFlowChecker(): FormFlowChecker
    {
        return $this->getEvent()->getParam(LpaLoaderListener::ATTR_FLOW_CHECKER);
    }

    protected function moveToNextRoute()
    {
        if ($this->isPopup()) {
            return new JsonModel(['success' => true]);
        }

        $routeMatch = $this->getEvent()->getRouteMatch();

        if (!$routeMatch instanceof RouteMatch) {
            throw new RuntimeException(
                'RouteMatch must be an instance of Laminas\Router\Http\RouteMatch for moveToNextRoute()'
            );
        }

        $lpa = $this->getLpa();
        $nextRoute = $this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName());

        return $this->redirect()->toRoute(
            $nextRoute,
            ['lpa-id' => $lpa->id],
            $this->getFlowChecker()->getRouteOptions($nextRoute)
        );
    }

    protected function isPopup(): bool
    {
        return $this->convertRequest()->isXmlHttpRequest();
    }

    protected function flattenData(array $modelData): array
    {
        $formData = [];

        foreach ($modelData as $l1 => $l2) {
            if (is_array($l2)) {
                foreach ($l2 as $name => $l3) {
                    if ($l1 === 'dob') {
                        $dob = new DateTime($l3);
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
}
