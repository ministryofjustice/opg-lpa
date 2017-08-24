<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReuseDetailsController;
use Application\Form\Lpa\ReuseDetailsForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class ReuseDetailsControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableReuseDetailsController
     */
    private $controller;
    /**
     * @var MockInterface|ReuseDetailsForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new TestableReuseDetailsController();
        parent::controllerSetUp($this->controller);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->form = Mockery::mock(ReuseDetailsForm::class);
        $this->lpa = FixturesData::getPfLpa();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Required data missing when attempting to load the reuse details screen
     */
    public function testIndexActionRequiredDataMissing()
    {
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromQuery')->once();

        $this->controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Required data missing when attempting to load the reuse details screen
     */
    public function testIndexActionGetMissingParameters()
    {
        $queryParameters = [
            'calling-url' => '',
            'include-trusts' => null,
            'actor-name' => '',
        ];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionGet()
    {
        $queryParameters = [
            'calling-url' => '/lpa/' . $this->lpa->id . '/donor/add',
            'include-trusts' => '0',
            'actor-name' => 'Donor',
        ];
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->andReturn($queryParameters)->once();
        $this->userDetailsSession->user = $this->user;

        $this->formElementManager->shouldReceive('get')->with(
            'Application\Form\Lpa\ReuseDetailsForm',
            ['actorReuseDetails' => $this->controller->testGetActorReuseDetails(false, false)]
        )->andReturn($this->form);

        $this->url->shouldReceive('fromRoute')
            ->with('lpa/reuse-details', ['lpa-id' => $this->lpa->id], ['query' => $queryParameters])
            ->andReturn('lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();

        $this->form->shouldReceive('setAttribute')->with('action', 'lpa/reuse-details?lpa-id=' . $this->lpa->id)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('/lpa/' . $this->lpa->id . '/donor', $result->cancelUrl);
        $this->assertEquals($queryParameters['actor-name'], $result->actorName);
    }
}