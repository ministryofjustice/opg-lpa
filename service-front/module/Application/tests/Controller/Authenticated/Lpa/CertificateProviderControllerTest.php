<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CertificateProviderController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Form\Lpa\CertificateProviderForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class CertificateProviderControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|BlankMainFlowForm
     */
    private $blankMainFlowForm;
    /**
     * @var MockInterface|CertificateProviderForm
     */
    private $form;

    public function setUp()
    {
        parent::setUp();

        $this->form = Mockery::mock(CertificateProviderForm::class);

        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CertificateProviderForm'])->andReturn($this->form);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CertificateProviderForm', ['lpa' => $this->lpa]])->andReturn($this->form);

        $this->blankMainFlowForm = Mockery::mock(BlankMainFlowForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])->andReturn($this->blankMainFlowForm);
    }

    public function testIndexActionNoCertificateProvider()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->lpa->document->certificateProvider = null;

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setMatchedRouteName($controller, 'lpa/certificate-provider');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/people-to-notify', $result->nextRoute);
    }

    public function testIndexActionCertificateProvider()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->assertInstanceOf(CertificateProvider::class, $this->lpa->document->certificateProvider);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setMatchedRouteName($controller, 'lpa/certificate-provider');

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());

        $this->assertEquals('lpa/people-to-notify', $result->nextRoute);
    }

    public function testAddActionGetCertificateProvider()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $response = new Response();

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetReuseDetails()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $response = new Response();

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/certificate-provider/add', $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetCertificateProviderJs()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $response = new Response();

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetNoCertificateProvider()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->lpa->document->certificateProvider = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();

        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');

        $this->form->shouldReceive('setActorData')->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    public function testAddActionPostInvalid()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->lpa->document->certificateProvider = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');
        $this->form->shouldReceive('setActorData')->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();
        $this->setPostInvalid($this->form, [], null, 2);

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to save certificate provider for id: 91333263035
     */
    public function testAddActionPostException()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $postData = [];

        $this->lpa->document->certificateProvider = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');
        $this->form->shouldReceive('setActorData')->once();
        $this->setPostValid($this->form, $postData, null, 2);

        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(false);

        $controller->addAction();
    }

    public function testAddActionPostSuccess()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $response = new Response();

        $postData = [];

        $this->lpa->document->certificateProvider = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');
        $this->form->shouldReceive('setActorData')->once();
        $this->setPostValid($this->form, $postData, null, 2, 2);
        $this->metadata->shouldReceive('removeMetadata')->withArgs([$this->lpa, Lpa::CERTIFICATE_PROVIDER_SKIPPED])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/certificate-provider');
        $this->setRedirectToRoute('lpa/people-to-notify', $this->lpa, $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionPostReuseDetails()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->lpa->document->certificateProvider = null;
        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add', 2);
        $this->form->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/certificate-provider');
        $routeMatch = $this->setReuseDetails($controller, $this->form, $this->user, 'attorney');
        $this->setMatchedRouteName($controller, 'lpa/certificate-provider/add', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/certificate-provider/add")->once();

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(
            "http://localhost/lpa/{$this->lpa->id}/lpa/certificate-provider/add",
            $result->backButtonUrl
        );
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionGet()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setActorData')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->certificateProvider->flatten()]);
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    public function testEditActionPostInvalid()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setActorData')->once();
        $this->setPostInvalid($this->form);
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update certificate provider for id: 91333263035
     */
    public function testEditActionPostException()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $postData = [];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setActorData')->once();
        $this->setPostValid($this->form, $postData);


        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(false);

        $controller->editAction();
    }

    public function testEditActionPostSuccess()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $response = new Response();

        $postData = [];

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setActorData')->once();
        $this->setPostValid($this->form, $postData);


        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(true);
        $this->setMatchedRouteNameHttp($controller, 'lpa/certificate-provider');
        $this->setRedirectToRoute('lpa/people-to-notify', $this->lpa, $response);

        $result = $controller->editAction();

        $this->assertEquals($response, $result);
    }

    public function testConfirmDeleteAction()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/certificate-provider/delete', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/certificate-provider/delete")->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider/delete", $result->getVariable('deleteRoute'));
        $certificateProvider = $this->lpa->document->certificateProvider;
        $this->assertEquals($certificateProvider->name, $result->getVariable('certificateProviderName'));
        $this->assertEquals($certificateProvider->address, $result->getVariable('certificateProviderAddress'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
        $this->assertEquals(false, $result->terminate());
        $this->assertEquals(false, $result->isPopup);
    }

    public function testConfirmDeleteActionJs()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/certificate-provider/delete', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/certificate-provider/delete")->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider/delete", $result->getVariable('deleteRoute'));
        $certificateProvider = $this->lpa->document->certificateProvider;
        $this->assertEquals($certificateProvider->name, $result->getVariable('certificateProviderName'));
        $this->assertEquals($certificateProvider->address, $result->getVariable('certificateProviderAddress'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
        $this->assertEquals(true, $result->terminate());
        $this->assertEquals(true, $result->isPopup);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to delete certificate provider for id: 91333263035
     */
    public function testDeleteActionException()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $this->lpaApplicationService->shouldReceive('deleteCertificateProvider')->andReturn(false);

        $controller->deleteAction();
    }

    public function testDeleteActionSuccess()
    {
        /** @var CertificateProviderController $controller */
        $controller = $this->getController(TestableCertificateProviderController::class);

        $response = new Response();

        $this->lpaApplicationService->shouldReceive('deleteCertificateProvider')->andReturn(true);
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/certificate-provider', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $controller->deleteAction();

        $this->assertEquals($response, $result);
    }
}
