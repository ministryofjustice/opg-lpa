<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Form\Lpa\DonorForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Common\Dob;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class DonorControllerTest extends AbstractControllerTest
{
    /**
     * @var TestableDonorController
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
    private $postData = [
        'name' => [
            'title' => 'Miss',
            'first' => 'Unit',
            'last' => 'Test'
        ],
        'email' => ['address' => 'unit@test.com'],
        'dob' => ['day' => 1, 'month' => 2, 'year' => 1970],
        'canSign' => true
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(TestableDonorController::class);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->form = Mockery::mock(DonorForm::class);
        $this->lpa = FixturesData::getPfLpa();
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DonorForm'])->andReturn($this->form);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DonorForm', ['lpa' => $this->lpa]])->andReturn($this->form);
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
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor/add', ['lpa-id' => $this->lpa->id]])->andReturn('lpa/donor/add')->once();

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
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor/add', ['lpa-id' => $this->lpa->id]])->andReturn('lpa/donor/add')->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor/edit', ['lpa-id' => $this->lpa->id]])->andReturn('lpa/donor/edit')->once();
        $this->setMatchedRouteName($this->controller, 'lpa/donor');
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/when-lpa-starts',
            ['lpa-id' => $this->lpa->id],
            $this->getExpectedRouteOptions('lpa/when-lpa-starts')
        ])->andReturn('lpa/when-lpa-starts')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/donor/add', $result->addUrl);
        $this->assertEquals('lpa/donor/edit', $result->editUrl);
        $this->assertEquals('lpa/when-lpa-starts', $result->nextUrl);
    }

    public function testAddActionGetReuseDetails()
    {
        $response = new Response();

        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());
        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/donor/add', $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetDonorAlreadyProvided()
    {
        $this->assertNotNull($this->lpa->document->donor);

        $response = new Response();

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setRedirectToRoute('lpa/donor', $this->lpa, $response);

        $result = $this->controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetNoDonor()
    {
        $this->lpa->document->donor = null;

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    public function testAddActionPostInvalid()
    {
        $this->lpa->document->donor = null;

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostInvalid($this->form, [], null, 2);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();

        /** @var ViewModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to save LPA donor for id: 91333263035
     */
    public function testAddActionPostFailed()
    {
        $this->lpa->document->donor = null;

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostValid($this->form, $this->postData, null, 2);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData);
        $this->lpaApplicationService->shouldReceive('setDonor')
            ->withArgs(function ($lpaId, $donor) {
                return $lpaId === $this->lpa->id
                    && $donor->name == new LongName($this->postData['name'])
                    && $donor->email == new EmailAddress($this->postData['email'])
                    && $donor->dob == new Dob($this->postData['dob']);
            })->andReturn(false)->once();

        $this->controller->addAction();
    }

    public function testAddActionPostSuccess()
    {
        $this->lpa->document->donor = null;

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->setPostValid($this->form, $this->postData, null, 2);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData);
        $this->lpaApplicationService->shouldReceive('setDonor')
            ->withArgs(function ($lpaId, $donor) {
                return $lpaId === $this->lpa->id
                    && $donor->name == new LongName($this->postData['name'])
                    && $donor->email == new EmailAddress($this->postData['email'])
                    && $donor->dob == new Dob($this->postData['dob']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $this->controller->addAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionGet()
    {
        $this->assertNotNull($this->lpa->document->donor);

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();
        $this->setDonorBinding();

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    public function testEditActionPostInvalid()
    {
        $this->assertNotNull($this->lpa->document->donor);

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostInvalid($this->form, $this->postData);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();

        /** @var ViewModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update LPA donor for id: 91333263035
     */
    public function testEditActionPostFailed()
    {
        $this->assertNotNull($this->lpa->document->donor);

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostValid($this->form, $this->postData);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData);
        $this->lpaApplicationService->shouldReceive('setDonor')->withArgs(function ($lpaId, $donor) {
            return $lpaId === $this->lpa->id
                && $donor->name == new LongName($this->postData['name'])
                && $donor->email == new EmailAddress($this->postData['email'])
                && $donor->dob == new Dob($this->postData['dob'])
                && $donor->canSign === true;
        })->andReturn(false)->once();

        $this->controller->editAction();
    }

    public function testEditActionPostSuccess()
    {
        $this->assertNotNull($this->lpa->document->donor);

        $postData = $this->postData;
        $postData['canSign'] = false;

        $this->userDetailsSession->user = $this->user;
        $this->controller->setLpa($this->lpa);
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->setPostValid($this->form, $postData);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setExistingActorNamesData')
            ->withArgs([$this->controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData);
        $this->lpaApplicationService->shouldReceive('setDonor')
            ->withArgs(function ($lpaId, $donor) {
                return $lpaId === $this->lpa->id
                    && $donor->name == new LongName($this->postData['name'])
                    && $donor->email == new EmailAddress($this->postData['email'])
                    && $donor->dob == new Dob($this->postData['dob'])
                    && $donor->canSign === false;
            })->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpaId, $correspondent) {
                return $lpaId === $this->lpa->id && $correspondent->name == new LongName($this->postData['name']); //Only changes name
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $this->controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    private function setDonorBinding()
    {
        $donor = $this->lpa->document->donor->flatten();
        $dob = $this->lpa->document->donor->dob->date;

        $donor['dob-date'] = [
            'day'   => $dob->format('d'),
            'month' => $dob->format('m'),
            'year'  => $dob->format('Y'),
        ];

        $this->form->shouldReceive('bind')->withArgs([$donor])->once();
    }
}