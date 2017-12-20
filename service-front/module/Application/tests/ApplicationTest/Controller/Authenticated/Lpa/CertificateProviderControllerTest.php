<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CertificateProviderController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Form\Lpa\CertificateProviderForm;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Lpa\Metadata;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Uri\Uri;
use Zend\View\Model\ViewModel;

class CertificateProviderControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableCertificateProviderController
     */
    private $controller;
    /**
     * @var MockInterface|BlankMainFlowForm
     */
    private $blankMainFlowForm;
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
        $this->controller = new TestableCertificateProviderController();
        parent::controllerSetUp($this->controller);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->lpa = FixturesData::getPfLpa();

        $this->form = Mockery::mock(CertificateProviderForm::class);

        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CertificateProviderForm'])->andReturn($this->form);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\CertificateProviderForm', ['lpa' => $this->lpa]])->andReturn($this->form);

        $this->blankMainFlowForm = Mockery::mock(BlankMainFlowForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])->andReturn($this->blankMainFlowForm);
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

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setMatchedRouteName($this->controller, 'lpa/certificate-provider');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/people-to-notify', $result->nextRoute);
    }

    public function testIndexActionCertificateProvider()
    {
        $this->assertInstanceOf(CertificateProvider::class, $this->lpa->document->certificateProvider);

        $this->controller->setLpa($this->lpa);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setMatchedRouteName($this->controller, 'lpa/certificate-provider');

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());

        $this->assertEquals('lpa/people-to-notify', $result->nextRoute);
    }

    public function testAddActionGetCertificateProvider()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetReuseDetails()
    {
        $response = new Response();

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/certificate-provider/add', $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetCertificateProviderJs()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $this->setRedirectToRoute('lpa/certificate-provider', $this->lpa, $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetNoCertificateProvider()
    {
        $this->lpa->document->certificateProvider = null;

        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();

        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');

        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    public function testAddActionPostInvalid()
    {
        $this->lpa->document->certificateProvider = null;
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();
        $this->setPostInvalid($this->form, [], null, 2);

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to save certificate provider for id: 91333263035
     */
    public function testAddActionPostException()
    {
        $postData = [];

        $this->lpa->document->certificateProvider = null;
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->setPostValid($this->form, $postData, null, 2);

        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(false);

        $this->controller->addAction();
    }

    public function testAddActionPostSuccess()
    {
        $response = new Response();

        $postData = [];

        $this->lpa->document->certificateProvider = null;
        $this->controller->setLpa($this->lpa);
        $this->userDetailsSession->user = $this->user;
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add');
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->setPostValid($this->form, $postData, null, 2, 2);
        $this->metadata->shouldReceive('removeMetadata')->withArgs([$this->lpa, Metadata::CERTIFICATE_PROVIDER_SKIPPED])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/certificate-provider');
        $this->setRedirectToRoute('lpa/people-to-notify', $this->lpa, $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionPostReuseDetails()
    {
        $this->lpa->document->certificateProvider = null;
        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/add', 2);
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/certificate-provider');
        $routeMatch = $this->setReuseDetails($this->controller, $this->form, $this->user, 'attorney');
        $this->setMatchedRouteName($this->controller, 'lpa/certificate-provider/add', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/certificate-provider/add")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(
            "http://localhost/lpa/{$this->lpa->id}/lpa/certificate-provider/add",
            $result->backButtonUrl
        );
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionGet()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([$this->lpa->document->certificateProvider->flatten()]);
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    public function testEditActionPostInvalid()
    {
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->setPostInvalid($this->form);
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/certificate-provider/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/certificate-provider", $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update certificate provider for id: 91333263035
     */
    public function testEditActionPostException()
    {
        $postData = [];

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->setPostValid($this->form, $postData);


        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(false);

        $this->controller->editAction();
    }

    public function testEditActionPostSuccess()
    {
        $response = new Response();

        $postData = [];

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/certificate-provider/edit');
        $this->form->shouldReceive('setExistingActorNamesData')->once();
        $this->setPostValid($this->form, $postData);


        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData)->once();
        $this->lpaApplicationService->shouldReceive('setCertificateProvider')->andReturn(true);
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/certificate-provider');
        $this->setRedirectToRoute('lpa/people-to-notify', $this->lpa, $response);

        $result = $this->controller->editAction();

        $this->assertEquals($response, $result);
    }

    public function testConfirmDeleteAction()
    {
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/certificate-provider/delete', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/certificate-provider/delete")->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

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
        $this->controller->setLpa($this->lpa);
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/certificate-provider/delete', ['lpa-id' => $this->lpa->id]])
            ->andReturn("lpa/{$this->lpa->id}/certificate-provider/delete")->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/certificate-provider',
            ['lpa-id' => $this->lpa->id]
        ])->andReturn("lpa/{$this->lpa->id}/certificate-provider")->once();

        /** @var ViewModel $result */
        $result = $this->controller->confirmDeleteAction();

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
        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('deleteCertificateProvider')->andReturn(false);

        $this->controller->deleteAction();
    }

    public function testDeleteActionSuccess()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->lpaApplicationService->shouldReceive('deleteCertificateProvider')->andReturn(true);
        $this->redirect->shouldReceive('toRoute')
            ->withArgs(['lpa/certificate-provider', ['lpa-id' => $this->lpa->id]])->andReturn($response)->once();

        $result = $this->controller->deleteAction();

        $this->assertEquals($response, $result);
    }
}
