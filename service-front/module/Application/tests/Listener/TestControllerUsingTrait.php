<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\LpaLoaderTrait;
use Application\Model\FormFlowChecker;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use MakeShared\DataModel\Lpa\Lpa;
use PHPUnit\Framework\MockObject\MockObject;

class TestControllerUsingTrait
{
    use LpaLoaderTrait;

    private MvcEvent $event;
    private $redirectPlugin;
    private Request|MockObject $request;

    public function __construct(MvcEvent $event, $redirectPlugin, $request)
    {
        $this->event = $event;
        $this->redirectPlugin = $redirectPlugin;
        $this->request = $request;
    }

    public function getEvent(): MvcEvent
    {
        return $this->event;
    }

    public function redirect()
    {
        return $this->redirectPlugin;
    }

    public function convertRequest(): Request
    {
        return $this->request;
    }

    public function testGetLpa(): Lpa
    {
        return $this->getLpa();
    }

    public function testGetFlowChecker(): FormFlowChecker
    {
        return $this->getFlowChecker();
    }

    public function testMoveToNextRoute()
    {
        return $this->moveToNextRoute();
    }

    public function testIsPopup(): bool
    {
        return $this->isPopup();
    }

    public function testFlattenData(array $modelData): array
    {
        return $this->flattenData($modelData);
    }
}
