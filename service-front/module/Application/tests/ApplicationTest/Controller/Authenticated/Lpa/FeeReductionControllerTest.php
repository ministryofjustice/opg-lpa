<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\FeeReductionController;
use Application\Form\Lpa\FeeReductionForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Form\Element\Select;
use Zend\View\Model\ViewModel;

class FeeReductionControllerTest extends AbstractControllerTest
{
    /**
     * @var FeeReductionController
     */
    private $controller;
    /**
     * @var MockInterface|FeeReductionForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;
    private $options;
    /**
     * @var MockInterface|Select
     */
    private $reductionOptions;

    public function setUp()
    {
        $this->controller = new FeeReductionController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(FeeReductionForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\FeeReductionForm', ['lpa' => $this->lpa])->andReturn($this->form);

        $this->options = [
            'value_options' => [
                'reducedFeeReceivesBenefits' => ['value' => 'reducedFeeReceivesBenefits'],
                'reducedFeeUniversalCredit' => ['value' => 'reducedFeeUniversalCredit'],
                'reducedFeeLowIncome' => ['value' => 'reducedFeeLowIncome'],
                'notApply' => ['value' => 'notApply']
            ]
        ];

        $this->reductionOptions = Mockery::mock(Select::class);
        $this->reductionOptions->shouldReceive('getOptions')->andReturn($this->options);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGetNoPayment()
    {
        $this->lpa->payment = null;
        $this->controller->setLpa($this->lpa);
        $this->reductionOptions->shouldReceive('getValue')->andReturn('')->times(4);
        $this->form->shouldReceive('get')->with('reductionOptions')->andReturn($this->reductionOptions)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(4, count($result->getVariable('reductionOptions')));
    }
}