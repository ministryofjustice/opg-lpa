<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\DonorForm;
use Application\Handler\Lpa\DonorEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DonorEditHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private DonorForm&MockObject $form;
    private DonorEditHandler $handler;

    private array $postData = [
        'name' => ['title' => 'Miss', 'first' => 'Unit', 'last' => 'Test'],
        'email' => ['address' => 'unit@test.com'],
        'dob' => ['day' => 1, 'month' => 2, 'year' => 1970],
        'canSign' => true,
    ];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);
        $this->actorReuseDetailsService->method('getActorsList')->willReturn([]);
        $this->form = $this->createMock(DonorForm::class);
        $this->formElementManager->method('get')->willReturn($this->form);
        $this->handler = new DonorEditHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->actorReuseDetailsService,
        );
    }

    private function createLpa(?Donor $donor = null, ?Correspondence $correspondent = null): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->donor = $donor ?? $this->createDonor();
        $lpa->document->correspondent = $correspondent;
        return $lpa;
    }

    private function createDonor(): Donor
    {
        $donor = new Donor();
        $donor->name = new LongName(['title' => 'Miss', 'first' => 'Unit', 'last' => 'Test']);
        $donor->dob = new Dob(['date' => '1970-02-01']);
        $donor->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);
        $donor->canSign = true;
        return $donor;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        bool $isXhr = false,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/when-lpa-starts');
        $flowChecker->method('getRouteOptions')->willReturn([]);
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/donor/edit');
        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }
        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }
        return $request;
    }

    public function testGetRendersFormWithBoundDonorData(): void
    {
        $this->form->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/donor/form.twig', $this->callback(
                fn(array $vars) => isset($vars['form'])
                    && isset($vars['cancelUrl'])
                    && !isset($vars['isPopup'])
            ))
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetPopupRendersFormWithIsPopup(): void
    {
        $this->form->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/donor/form.twig', $this->callback(
                fn(array $vars) => isset($vars['form'])
                    && $vars['isPopup'] === true
            ))
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', [], null, true));
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', $this->postData));
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidApiFailureThrowsException(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postData);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->lpaApplicationService->method('setDonor')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update LPA donor for id: 91333263035');

        $this->handler->handle($this->createRequest('POST', $this->postData));
    }

    public function testPostValidSuccessRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postData);
        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route) => match ($route) {
                'lpa/when-lpa-starts' => '/lpa/91333263035/when-lpa-starts',
                default => '/url',
            }
        );
        $this->lpaApplicationService->expects($this->once())->method('setDonor')->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $this->postData));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidXhrReturnsJsonSuccess(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postData);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->lpaApplicationService->expects($this->once())->method('setDonor')->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $this->postData, null, true));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode($response->getBody()->__toString(), true);
        $this->assertTrue($body['success']);
    }

    public function testPostValidUpdatesCorrespondentWhenDonorIsCorrespondent(): void
    {
        $donor = $this->createDonor();
        $correspondent = new Correspondence();
        $correspondent->who = Correspondence::WHO_DONOR;
        $correspondent->name = new LongName(['title' => 'Mr', 'first' => 'Old', 'last' => 'Name']);
        $correspondent->address = new Address(['address1' => '99 Old Street', 'postcode' => 'ZZ9 9ZZ']);

        $lpa = $this->createLpa($donor, $correspondent);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postData);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->lpaApplicationService->expects($this->once())->method('setDonor')->willReturn(true);
        $this->lpaApplicationService->expects($this->once())->method('setCorrespondent')->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $this->postData, $lpa));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidCorrespondentUpdateFailureThrowsException(): void
    {
        $donor = $this->createDonor();
        $correspondent = new Correspondence();
        $correspondent->who = Correspondence::WHO_DONOR;
        $correspondent->name = new LongName(['title' => 'Mr', 'first' => 'Old', 'last' => 'Name']);
        $correspondent->address = new Address(['address1' => '99 Old Street', 'postcode' => 'ZZ9 9ZZ']);

        $lpa = $this->createLpa($donor, $correspondent);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postData);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->lpaApplicationService->method('setDonor')->willReturn(true);
        $this->lpaApplicationService->method('setCorrespondent')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update correspondent for id: 91333263035');

        $this->handler->handle($this->createRequest('POST', $this->postData, $lpa));
    }
}
