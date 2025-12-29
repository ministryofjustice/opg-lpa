<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Form\Lpa\ReuseDetailsForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;

final class ReuseDetailsControllerTest extends AbstractControllerTestCase
{
    private MockInterface|ReuseDetailsForm $form;
    private array $postData = [
        'reuse-details' => 1
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(ReuseDetailsForm::class);
    }

    protected function getController(string $controllerName)
    {
        /** @var ReuseDetailsController $controller */
        $controller = parent::getController($controllerName);

        $controller->setRouter($this->router);

        return $controller;
    }

    public function testIndexActionRequiredDataMissing(): void
    {
        $controller = $this->getController(TestableReuseDetailsController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromQuery')->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required data missing when attempting to load the reuse details screen');

        $controller->indexAction();
    }

    public function testIndexActionGetMissingParameters(): void
    {
        $controller = $this->getController(TestableReuseDetailsController::class);

        $queryParameters = [
            'calling-url' => '',
            'include-trusts' => null,
            'actor-name' => '',
        ];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required data missing when attempting to load the reuse details screen');

        $controller->indexAction();
    }

    public function testIndexActionGet(): void
    {
        $controller = $this->getController(TestableReuseDetailsController::class);

        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/donor/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('/lpa/' . $this->lpa->id . '/donor', $result->cancelUrl);
        $this->assertEquals($queryParameters['actor-name'], $result->actorName);
    }

    public function testIndexActionPostInvalid(): void
    {
        $controller = $this->getController(TestableReuseDetailsController::class);

        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/donor/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'user'])
            ->andReturn($this->user)
            ->byDefault();

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('/lpa/' . $this->lpa->id . '/donor', $result->cancelUrl);
        $this->assertEquals($queryParameters['actor-name'], $result->actorName);
    }

    public function testIndexActionPostInvalidRouteMatch(): void
    {
        $controller = $this->getController(TestableReuseDetailsController::class);

        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/donor/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'user'])
            ->andReturn($this->user)
            ->byDefault();

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData);
        $this->router->shouldReceive('match')->andReturn(null)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Calling controller or action could not be determined for processing reuse details request');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('/lpa/' . $this->lpa->id . '/donor', $result->cancelUrl);
        $this->assertEquals($queryParameters['actor-name'], $result->actorName);
    }

    public function testIndexActionPostPrimaryAttorneyAdd(): void
    {
        $controller = $this->getController(TestableReuseDetailsController::class);

        $response = new Response();

        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/primary-attorney/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'user'])
            ->andReturn($this->user)
            ->byDefault();

        $this->formElementManager->shouldReceive('get')->withArgs([
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $controller->testGetActorReuseDetails(false, false)]
        ])->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters]])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')
            ->withArgs(['action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id])->once();
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData);
        $routeMatch = Mockery::mock(RouteMatch::class);
        $this->router->shouldReceive('match')->andReturn($routeMatch)->once();
        $routeMatch->shouldReceive('getParam')
            ->withArgs(['controller'])->andReturn('Authenticated\Lpa\PrimaryAttorneyController')->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['action'])->andReturn('add')->once();
        $this->forward->shouldReceive('dispatch')->withArgs(['Authenticated\Lpa\PrimaryAttorneyController', [
            'action'            => 'add',
            'reuseDetailsIndex' => 1,
            'callingUrl'        => '/lpa/' . $this->lpa->id . '/primary-attorney/add',
        ]])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
