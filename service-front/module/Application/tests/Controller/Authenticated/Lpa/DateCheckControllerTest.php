<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DateCheckController;
use Application\Form\Lpa\DateCheckForm;
use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Service\DateCheckViewModelHelper;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\Http\Response;
use Mockery;
use Laminas\View\Model\ViewModel;
use Mockery\MockInterface;

final class DateCheckControllerTest extends AbstractControllerTestCase
{
    private MockInterface|DateCheckForm $form;
    private array $postData = [
        'sign-date-donor'                 => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-donor-life-sustaining' => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-attorney-0'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-attorney-1'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-replacement-attorney-0'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-replacement-attorney-1'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-certificate-provider'  => ['day' => 1, 'month' => 2, 'year' => 2016]
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(DateCheckForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DateCheckForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGet(): void
    {
        /** @var DateCheckController $controller */
        $controller = $this->getController(DateCheckController::class);
        $controller->setDateCheckViewModelHelper(
            new DateCheckViewModelHelper(new ContinuationSheets())
        );

        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/complete', $result->getVariable('returnRoute'));
    }

    public function testIndexActionPostInvalid(): void
    {
        /** @var DateCheckController $controller */
        $controller = $this->getController(DateCheckController::class);
        $controller->setDateCheckViewModelHelper(
            new DateCheckViewModelHelper(new ContinuationSheets())
        );

        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/complete', $result->getVariable('returnRoute'));
    }

    public function testIndexActionPostInvalidDates(): void
    {
        /** @var DateCheckController $controller */
        $controller = $this->getController(DateCheckController::class);
        $controller->setDateCheckViewModelHelper(
            new DateCheckViewModelHelper(new ContinuationSheets())
        );

        //Donor must be the first to sign
        $postData = $this->postData;
        $postData['sign-date-donor']['year'] = 2017;

        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->form->shouldReceive('setMessages')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/complete', $result->getVariable('returnRoute'));
        $this->assertEquals(null, $result->dateError);
    }

    public function testIndexActionPostValidDates(): void
    {
        /** @var DateCheckController $controller */
        $controller = $this->getController(DateCheckController::class);
        $controller->setDateCheckViewModelHelper(
            new DateCheckViewModelHelper(new ContinuationSheets())
        );

        $postData = $this->postData;

        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/date-check/valid',
            ['lpa-id' => $this->lpa->id],
            ['query' => ['return-route' => 'lpa/complete']]
        ])->andReturn("lpa/{$this->lpa->id}/date-check/valid")->once();

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals("lpa/{$this->lpa->id}/date-check/valid", $result->getHeaders()->get('Location')->getUri());
    }

    public function testValidActionNoReturnRoute(): void
    {
        /** @var DateCheckController $controller */
        $controller = $this->getController(DateCheckController::class);

        $this->params->shouldReceive('fromQuery')->withArgs(['return-route', null])->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $controller->validAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('user/dashboard', $result->returnRoute);
    }

    public function testValidActionReturnRoute(): void
    {
        /** @var DateCheckController $controller */
        $controller = $this->getController(DateCheckController::class);

        $this->params->shouldReceive('fromQuery')->withArgs(['return-route', null])->andReturn('lpa/complete')->once();

        /** @var ViewModel $result */
        $result = $controller->validAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/complete', $result->returnRoute);
    }
}
