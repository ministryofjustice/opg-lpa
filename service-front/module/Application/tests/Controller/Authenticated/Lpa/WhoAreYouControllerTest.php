<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhoAreYouController;
use Application\Form\Lpa\WhoAreYouForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Exception;
use Laminas\Form\Element\Select;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\MockInterface;

final class WhoAreYouControllerTest extends AbstractControllerTestCase
{
    private MockInterface|WhoAreYouForm $form;
    private array $who;
    private MockInterface|Select $whoOptions;
    private array $postData = [
        'who' => 'donor'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(WhoAreYouForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\WhoAreYouForm'])->andReturn($this->form);

        $this->who = [
            'value_options' => [
                'donor' => ['value' => 'donor'],
                'friendOrFamily' => ['value' => 'friendOrFamily'],
                'financeProfessional' => ['value' => 'financeProfessional'],
                'legalProfessional' => ['value' => 'legalProfessional'],
                'estatePlanningProfessional' => ['value' => 'estatePlanningProfessional'],
                'digitalPartner' => ['value' => 'digitalPartner'],
                'charity' => ['value' => 'charity'],
                'organisation' => ['value' => 'organisation'],
                'other' => ['value' => 'other'],
                'notSaid' => ['value' => 'notSaid']
            ]
        ];

        $this->whoOptions = Mockery::mock(Select::class);
        $this->whoOptions->shouldReceive('getOptions')->andReturn($this->who);
    }

    public function testIndexActionGetWhoAreYouAnsweredTrue(): void
    {
        $this->lpa->whoAreYouAnswered = true;

        /** @var WhoAreYouController $controller */
        $controller = $this->getController(WhoAreYouController::class);

        $this->setMatchedRouteName($controller, 'lpa/who-are-you');
        $nextUrl = $this->setUrlFromRoute($this->lpa, 'lpa/repeat-application');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($nextUrl, $result->nextUrl);
    }

    public function testIndexActionGetWhoAreYouAnsweredFalse(): void
    {
        $this->lpa->whoAreYouAnswered = false;

        /** @var WhoAreYouController $controller */
        $controller = $this->getController(WhoAreYouController::class);

        $this->setMatchedRouteName($controller, 'lpa/who-are-you');
        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('get')->withArgs(['who'])->andReturn($this->whoOptions)->once();
        $this->whoOptions->shouldReceive('getValue')->andReturn('')->times(10);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(10, count($result->getVariable('whoOptions')));
    }

    public function testIndexActionPostInvalid(): void
    {
        $this->lpa->whoAreYouAnswered = false;

        /** @var WhoAreYouController $controller */
        $controller = $this->getController(WhoAreYouController::class);

        $this->setMatchedRouteName($controller, 'lpa/who-are-you');
        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->setPostInvalid($this->form);
        $this->form->shouldReceive('get')->withArgs(['who'])->andReturn($this->whoOptions)->once();
        $this->whoOptions->shouldReceive('getValue')->andReturn('')->times(10);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(10, count($result->getVariable('whoOptions')));
    }

    public function testIndexActionPostFailed(): void
    {
        $this->lpa->whoAreYouAnswered = false;

        /** @var WhoAreYouController $controller */
        $controller = $this->getController(WhoAreYouController::class);

        $this->setMatchedRouteName($controller, 'lpa/who-are-you');
        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoAreYou')->andReturn(false)->once();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API client failed to set Who Are You for id: 91333263035');

        $controller->indexAction();
    }

    public function testIndexActionPostSuccess(): void
    {
        $this->lpa->whoAreYouAnswered = false;

        /** @var WhoAreYouController $controller */
        $controller = $this->getController(WhoAreYouController::class);

        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoAreYou')->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/who-are-you', 2);

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/repeat-application', $result->getHeaders()->get('Location')->getUri());
    }
}
