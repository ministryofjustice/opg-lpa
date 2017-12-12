<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\WhoAreYouController;
use Application\Form\Lpa\WhoAreYouForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Form\Element\Select;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class WhoAreYouControllerTest extends AbstractControllerTest
{
    /**
     * @var WhoAreYouController
     */
    private $controller;
    /**
     * @var MockInterface|WhoAreYouForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $who;
    /**
     * @var MockInterface|Select
     */
    private $whoOptions;
    private $professional;
    /**
     * @var MockInterface|Select
     */
    private $professionalOptions;
    private $postData = [
        'who' => 'donor'
    ];

    public function setUp()
    {
        $this->controller = new WhoAreYouController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(WhoAreYouForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\WhoAreYouForm'])->andReturn($this->form);

        $this->who = [
            'value_options' => [
                'donor' => ['value' => 'donor'],
                'friendOrFamily' => ['value' => 'friendOrFamily'],
                'professional' => ['value' => 'professional'],
                'digitalPartner' => ['value' => 'digitalPartner'],
                'organisation' => ['value' => 'organisation'],
                'notSaid' => ['value' => 'notSaid']
            ]
        ];

        $this->whoOptions = Mockery::mock(Select::class);
        $this->whoOptions->shouldReceive('getOptions')->andReturn($this->who);

        $this->professional = [
            'value_options' => [
                'solicitor' => ['value' => 'solicitor'],
                'will-writer' => ['value' => 'will-writer'],
                'other' => ['value' => 'other']
            ]
        ];

        $this->professionalOptions = Mockery::mock(Select::class);
        $this->professionalOptions->shouldReceive('getOptions')->andReturn($this->professional);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGetWhoAreYouAnsweredTrue()
    {
        $this->lpa->whoAreYouAnswered = true;
        $this->controller->setLpa($this->lpa);
        $this->setMatchedRouteName($this->controller, 'lpa/who-are-you');
        $nextUrl = $this->setUrlFromRoute($this->lpa, 'lpa/repeat-application');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($nextUrl, $result->nextUrl);
    }

    public function testIndexActionGetWhoAreYouAnsweredFalse()
    {
        $this->lpa->whoAreYouAnswered = false;
        $this->controller->setLpa($this->lpa);
        $this->setMatchedRouteName($this->controller, 'lpa/who-are-you');
        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('get')->withArgs(['who'])->andReturn($this->whoOptions)->once();
        $this->form->shouldReceive('get')->withArgs(['professional'])->andReturn($this->professionalOptions)->once();
        $this->whoOptions->shouldReceive('getValue')->andReturn('')->times(6);
        $this->professionalOptions->shouldReceive('getValue')->andReturn('')->times(3);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(6, count($result->getVariable('whoOptions')));
        $this->assertEquals(3, count($result->getVariable('professionalOptions')));
    }

    public function testIndexActionPostInvalid()
    {
        $this->lpa->whoAreYouAnswered = false;
        $this->controller->setLpa($this->lpa);
        $this->setMatchedRouteName($this->controller, 'lpa/who-are-you');
        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->setPostInvalid($this->form);
        $this->form->shouldReceive('get')->withArgs(['who'])->andReturn($this->whoOptions)->once();
        $this->form->shouldReceive('get')->withArgs(['professional'])->andReturn($this->professionalOptions)->once();
        $this->whoOptions->shouldReceive('getValue')->andReturn('')->times(6);
        $this->professionalOptions->shouldReceive('getValue')->andReturn('')->times(3);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(6, count($result->getVariable('whoOptions')));
        $this->assertEquals(3, count($result->getVariable('professionalOptions')));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set Who Are You for id: 91333263035
     */
    public function testIndexActionPostFailed()
    {
        $this->lpa->whoAreYouAnswered = false;
        $this->controller->setLpa($this->lpa);
        $this->setMatchedRouteName($this->controller, 'lpa/who-are-you');
        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoAreYou')
            ->withArgs(function ($lpaId, $whoAreYou) {
                return $lpaId === $this->lpa->id
                    && $whoAreYou->who === $this->postData['who'];
            })->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostSuccess()
    {
        $response = new Response();

        $this->lpa->whoAreYouAnswered = false;
        $this->controller->setLpa($this->lpa);
        $this->setFormAction($this->form, $this->lpa, 'lpa/who-are-you');
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setWhoAreYou')
            ->withArgs(function ($lpaId, $whoAreYou) {
                return $lpaId === $this->lpa->id
                    && $whoAreYou->who === $this->postData['who'];
            })->andReturn(true)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/who-are-you', 2);
        $this->setRedirectToRoute('lpa/repeat-application', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
