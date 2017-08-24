<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CorrespondentController;
use Application\Form\Lpa\CorrespondentForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class CorrespondentControllerTest extends AbstractControllerTest
{
    /**
     * @var CorrespondentController
     */
    private $controller;
    /**
     * @var MockInterface|CorrespondentForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new CorrespondentController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(CorrespondentForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\CorrespondenceForm', ['lpa' => $this->lpa])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/correspondent?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->with([
            'contactInWelsh' => false,
            'correspondence' => [
                'contactByEmail' => true,
                'email-address'  => $this->lpa->document->donor->email->address,
                'contactByPhone' => true,
                'phone-number'   => $this->lpa->document->correspondent->phone->number,
                'contactByPost'  => false
            ]
        ])->once();
        $this->setMatchedRouteName($this->controller, 'lpa/correspondent');
        $this->url->shouldReceive('fromRoute')->with('lpa/correspondent/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/correspondent/edit')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals($this->lpa->document->correspondent->name, $result->getVariable('correspondentName'));
        $this->assertEquals($this->lpa->document->correspondent->address, $result->getVariable('correspondentAddress'));
        $this->assertEquals($this->lpa->document->correspondent->email, $result->getVariable('contactEmail'));
        $this->assertEquals($this->lpa->document->correspondent->phone->number, $result->getVariable('contactPhone'));
        $this->assertEquals('lpa/correspondent/edit', $result->getVariable('changeRoute'));
        $this->assertEquals(false, $result->getVariable('allowEditButton'));
    }
}