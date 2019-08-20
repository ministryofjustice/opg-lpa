<?php

namespace ApplicationTest\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AbstractServiceTest extends MockeryTestCase
{
    /**
     * @var $authenticationService AuthenticationService|MockInterface
     */
    protected $authenticationService;

    public function setUp() : void
    {
        $this->authenticationService = Mockery::mock(AuthenticationService::class);
    }

    public function testConstructor() : void
    {
        $service = new TestableAbstractService(
            $this->authenticationService,
            ['test' => 'config']
        );

        $this->assertEquals($this->authenticationService, $service->getAuthenticationService());
        $this->assertEquals(['test' => 'config'], $service->getConfig());
    }
}
