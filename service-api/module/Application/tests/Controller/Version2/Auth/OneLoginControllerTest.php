<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\OneLoginController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\OneLogin\Service as OneLoginService;
use Laminas\View\Model\JsonModel;
use Mockery;

class OneLoginControllerTest extends AbstractAuthControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Mockery::mock(OneLoginService::class);
    }

    private function getOneLoginController(): OneLoginController
    {
        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class);

        return $controller;
    }

    public function testStartActionReturnsBadRequestWhenRedirectUrlMissing(): void
    {
        $this->params->shouldReceive('fromQuery')
            ->with('redirect_url')
            ->andReturn(null)
            ->once();

        $controller = $this->getOneLoginController();
        $result     = $controller->startAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
        $this->assertStringContainsString('redirect_url', $result->detail);
    }

    public function testStartActionReturnsBadRequestWhenRedirectUrlBlank(): void
    {
        $this->params->shouldReceive('fromQuery')
            ->with('redirect_url')
            ->andReturn('')
            ->once();

        $controller = $this->getOneLoginController();
        $result     = $controller->startAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
    }

    public function testStartActionReturnsJsonModelWithServiceResult(): void
    {
        $redirectUrl   = 'https://example.com/auth/redirect';
        $serviceResult = [
            'state' => 'some-state',
            'nonce' => 'some-nonce',
            'url'   => 'https://auth.example.com/authorize?foo=bar',
        ];

        $this->params->shouldReceive('fromQuery')
            ->with('redirect_url')
            ->andReturn($redirectUrl)
            ->once();

        $this->service->shouldReceive('createAuthenticationRequest')
            ->with($redirectUrl)
            ->andReturn($serviceResult)
            ->once();

        $controller = $this->getOneLoginController();
        $result     = $controller->startAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($serviceResult, $result->getVariables());
    }
}
