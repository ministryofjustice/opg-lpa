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

    public function setUp()
    {
        $this->controller = new WhoAreYouController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(WhoAreYouForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\WhoAreYouForm')->andReturn($this->form);

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

    public function testIndexActionGetWhoAreYouAnsweredFalse()
    {
        $this->lpa->whoAreYouAnswered = false;
        $this->controller->setLpa($this->lpa);
        $this->setMatchedRouteName($this->controller, 'lpa/who-are-you');
        $this->url->shouldReceive('fromRoute')->with('lpa/who-are-you', ['lpa-id' => $this->lpa->id])->andReturn('lpa/who-are-you?lpa-id=' .$this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/who-are-you?lpa-id=' .$this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('get')->with('who')->andReturn($this->whoOptions)->once();
        $this->form->shouldReceive('get')->with('professional')->andReturn($this->professionalOptions)->once();
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
}