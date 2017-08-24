<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PrimaryAttorneyController;
use Application\Form\Lpa\AttorneyForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class PrimaryAttorneyControllerTest extends AbstractControllerTest
{
    /**
     * @var PrimaryAttorneyController
     */
    private $controller;
    /**
     * @var MockInterface|AttorneyForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new PrimaryAttorneyController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AttorneyForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\AttorneyForm', ['lpa' => $this->lpa])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionNoPrimaryAttorneys()
    {
        $this->lpa->document->primaryAttorneys = [];
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')
            ->with('lpa/primary-attorney/add', ['lpa-id' => $this->lpa->id])
            ->andReturn('lpa/primary-attorney/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }
}