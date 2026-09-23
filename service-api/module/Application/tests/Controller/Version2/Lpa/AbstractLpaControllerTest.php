<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Model\Service\AbstractService;
use Laminas\Http\Response;
use Mockery;
use Mockery\MockInterface;

class AbstractLpaControllerTest extends AbstractControllerTestCase
{
    /**
     * @var AbstractService|MockInterface
     */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = Mockery::mock(AbstractService::class);
    }

    public function testOnDispatchSuccess()
    {
        $result = $this->callOnDispatch(new TestableAbstractLpaController($this->authenticationService, $this->service));

        $this->assertNotNull($result);
        $this->assertInstanceOf(Response::class, $result);
    }
}
