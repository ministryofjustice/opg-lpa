<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DonorController;
use Application\Form\Lpa\DonorForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeSharedTest\DataModel\FixturesData;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

final class DonorControllerTest extends AbstractControllerTestCase
{
    private MockInterface|DonorForm $form;
    private array $postData = [
        'name' => [
            'title' => 'Miss',
            'first' => 'Unit',
            'last' => 'Test'
        ],
        'email' => ['address' => 'unit@test.com'],
        'dob' => ['day' => 1, 'month' => 2, 'year' => 1970],
        'canSign' => true
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(DonorForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DonorForm'])->andReturn($this->form);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DonorForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionNoDonor(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->lpa->document->donor = null;

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor/add', ['lpa-id' => $this->lpa->id]])->andReturn('lpa/donor/add')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/donor/add', $result->addUrl);
    }

    public function testIndexActionDonor(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->assertInstanceOf(Donor::class, $this->lpa->document->donor);

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor/add', ['lpa-id' => $this->lpa->id]])->andReturn('lpa/donor/add')->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor/edit', ['lpa-id' => $this->lpa->id]])->andReturn('lpa/donor/edit')->once();
        $this->setMatchedRouteName($controller, 'lpa/donor');
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/when-lpa-starts',
            ['lpa-id' => $this->lpa->id],
            $this->getExpectedRouteOptions('lpa/when-lpa-starts')
        ])->andReturn('lpa/when-lpa-starts')->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/donor/add', $result->addUrl);
        $this->assertEquals('lpa/donor/edit', $result->editUrl);
        $this->assertEquals('lpa/when-lpa-starts', $result->nextUrl);
    }

    public function testAddActionGetReuseDetails(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());
        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/donor/add');

        $result = $controller->addAction();

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString(
            'lpa/91333263035/reuse-details?',
            $result->getHeaderLine('Location')
        );
    }

    public function testAddActionGetDonorAlreadyProvided(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->assertNotNull($this->lpa->document->donor);

        $response = new Response();

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setRedirectToRoute('lpa/donor', $this->lpa, $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetNoDonor(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->lpa->document->donor = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    public function testAddActionPostInvalid(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->lpa->document->donor = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostInvalid($this->form, [], null, 2);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    public function testAddActionPostFailed(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->lpa->document->donor = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostValid($this->form, $this->postData, null, 2);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData);
        $this->lpaApplicationService->shouldReceive('setDonor')
            ->withArgs(function ($lpa, $donor): bool {
                return $lpa->id === $this->lpa->id
                    && $donor->name == new LongName($this->postData['name'])
                    && $donor->email == new EmailAddress($this->postData['email'])
                    && $donor->dob == new Dob($this->postData['dob']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to save LPA donor for id: 91333263035');

        $controller->addAction();
    }

    public function testAddActionPostSuccess(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->lpa->document->donor = null;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->setPostValid($this->form, $this->postData, null, 2);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/add');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData);
        $this->lpaApplicationService->shouldReceive('setDonor')
            ->withArgs(function ($lpa, $donor): bool {
                return $lpa->id === $this->lpa->id
                    && $donor->name == new LongName($this->postData['name'])
                    && $donor->email == new EmailAddress($this->postData['email'])
                    && $donor->dob == new Dob($this->postData['dob']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testEditActionGet(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->assertNotNull($this->lpa->document->donor);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();
        $this->setDonorBinding();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    public function testEditActionPostInvalid(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->assertNotNull($this->lpa->document->donor);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostInvalid($this->form, $this->postData);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/donor', ['lpa-id' => $this->lpa->id]])->andReturn("lpa/{$this->lpa->id}/donor")->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/donor/form.twig', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals("lpa/{$this->lpa->id}/donor", $result->cancelUrl);
    }

    public function testEditActionPostFailed(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->assertNotNull($this->lpa->document->donor);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->setPostValid($this->form, $this->postData);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData);
        $this->lpaApplicationService->shouldReceive('setDonor')->withArgs(function ($lpa, $donor): bool {
            return $lpa->id === $this->lpa->id
                && $donor->name == new LongName($this->postData['name'])
                && $donor->email == new EmailAddress($this->postData['email'])
                && $donor->dob == new Dob($this->postData['dob'])
                && $donor->canSign === true;
        })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update LPA donor for id: 91333263035');

        $controller->editAction();
    }

    public function testEditActionPostSuccess(): void
    {
        /** @var DonorController $controller */
        $controller = $this->getController(TestableDonorController::class);

        $this->assertNotNull($this->lpa->document->donor);

        $postData = $this->postData;
        $postData['canSign'] = false;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->setPostValid($this->form, $postData);
        $this->setFormAction($this->form, $this->lpa, 'lpa/donor/edit');
        $this->form->shouldReceive('setActorData')
            ->withArgs(['donor', $controller->testGetActorsList()])->once();
        $this->form->shouldReceive('getModelDataFromValidatedForm')->andReturn($postData);
        $this->lpaApplicationService->shouldReceive('setDonor')
            ->withArgs(function ($lpa, $donor): bool {
                return $lpa->id === $this->lpa->id
                    && $donor->name == new LongName($this->postData['name'])
                    && $donor->email == new EmailAddress($this->postData['email'])
                    && $donor->dob == new Dob($this->postData['dob'])
                    && $donor->canSign === false;
            })->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setCorrespondent')
            ->withArgs(function ($lpa, $correspondent): bool {
                return $lpa->id === $this->lpa->id
                    && $correspondent->name == new LongName($this->postData['name']); //Only changes name
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    private function setDonorBinding(): void
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
