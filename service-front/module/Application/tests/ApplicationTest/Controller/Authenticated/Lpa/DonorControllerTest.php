<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DonorController;
use Application\Form\Lpa\DonorForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class DonorControllerTest extends AbstractControllerTest
{
    /**
     * @var DonorController
     */
    private $controller;
    /**
     * @var MockInterface|DonorForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new DonorController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(DonorForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\DonorForm', ['lpa' => $this->lpa])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionNoDonor()
    {
        $this->lpa->document->donor = null;
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/donor/add', ['lpa-id' => $this->lpa->id])->andReturn('lpa/donor/add')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/donor/add', $result->addUrl);
    }

    public function testIndexActionDonor()
    {
        $this->assertInstanceOf(Donor::class, $this->lpa->document->donor);

        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/donor/add', ['lpa-id' => $this->lpa->id])->andReturn('lpa/donor/add')->once();
        $this->url->shouldReceive('fromRoute')->with('lpa/donor/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/donor/edit')->once();
        $this->setMatchedRouteName($this->controller, 'lpa/donor');
        $this->url->shouldReceive('fromRoute')->with('lpa/when-lpa-starts', ['lpa-id' => $this->lpa->id], ['fragment' => 'current'])->andReturn('lpa/when-lpa-starts')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/donor/add', $result->addUrl);
        $this->assertEquals('lpa/donor/edit', $result->editUrl);
        $this->assertEquals('lpa/when-lpa-starts', $result->nextUrl);
    }
}