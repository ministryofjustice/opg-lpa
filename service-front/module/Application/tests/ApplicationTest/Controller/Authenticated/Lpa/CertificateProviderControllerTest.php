<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CertificateProviderController;
use Application\Form\Lpa\CertificateProviderForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\View\Model\ViewModel;

class CertificateProviderControllerTest extends AbstractControllerTest
{
    /**
     * @var CertificateProviderController
     */
    private $controller;
    /**
     * @var MockInterface|CertificateProviderForm
     */
    private $form;
    /**
     * @var Lpa
     */
    private $lpa;

    public function setUp()
    {
        $this->controller = new CertificateProviderController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(CertificateProviderForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\CertificateProviderForm', ['lpa' => $this->lpa])->andReturn($this->form);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A LPA has not been set
     */
    public function testIndexActionNoLpa()
    {
        $this->controller->indexAction();
    }

    public function testIndexActionNoCertificateProvider()
    {
        $this->lpa->document->certificateProvider = null;
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/certificate-provider/add', ['lpa-id' => $this->lpa->id])->andReturn('lpa/certificate-provider/add')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
        $this->assertEquals('lpa/certificate-provider/add', $result->addUrl);
    }

    public function testIndexActionCertificateProvider()
    {
        $this->assertInstanceOf(CertificateProvider::class, $this->lpa->document->certificateProvider);

        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')->with('lpa/certificate-provider/edit', ['lpa-id' => $this->lpa->id])->andReturn('lpa/certificate-provider/edit')->once();
        $this->url->shouldReceive('fromRoute')->with('lpa/certificate-provider/confirm-delete', ['lpa-id' => $this->lpa->id])->andReturn('lpa/certificate-provider/confirm-delete')->once();
        $this->setMatchedRouteName($this->controller, 'lpa/certificate-provider');
        $this->url->shouldReceive('fromRoute')->with('lpa/people-to-notify', ['lpa-id' => $this->lpa->id], ['fragment' => 'current'])->andReturn('lpa/certificate-provider/add')->once();
        $this->url->shouldReceive('fromRoute')->with('lpa/certificate-provider/add', ['lpa-id' => $this->lpa->id])->andReturn('lpa/certificate-provider/add')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
        $this->assertEquals('lpa/certificate-provider/edit', $result->editUrl);
        $this->assertEquals('lpa/certificate-provider/confirm-delete', $result->confirmDeleteUrl);
        $this->assertEquals('lpa/certificate-provider/add', $result->addUrl);
    }
}