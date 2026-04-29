<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\DonorForm;
use Application\Handler\Lpa\DonorAddHandler;
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
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DonorAddHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private DonorForm&MockObject $form;
    private DonorAddHandler $handler;

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
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->form = $this->createMock(DonorForm::class);
        $this->formElementManager->method('get')->willReturn($this->form);
        $this->handler = new DonorAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->actorReuseDetailsService,
        );
    }

    private function createLpa(?Donor $donor = null): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->donor = $donor;
        return $lpa;
    }

    private function createDonor(): Donor
    {
        $donor = new Donor();
        $donor->name = new LongName(['title' => 'Miss', 'first' => 'Unit', 'last' => 'Test']);
        $donor->dob = new Dob(['date' => '1970-02-01']);
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
        $user = $this->createMock(User::class);
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/donor/add')
            ->withAttribute(RequestAttribute::USER_DETAILS, $user);
        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }
        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }
        return $request;
    }

    public function testGetRedirectsWhenDonorAlreadyExists(): void
    {
        $lpa = $this->createLpa($this->createDonor());
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/donor');
        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    private function createHandlerWithReuseDetails(array $reuseDetails): DonorAddHandler
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn($reuseDetails);

        return new DonorAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $reuseService,
        );
    }

    public function testGetWithOneSessionUserReuseOptionShowsUseMyDetailsLink(): void
    {
        $handler = $this->createHandlerWithReuseDetails([['label' => 'Me', 'data' => []]]);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/donor/form.twig', $this->callback(
                fn(array $vars) => $vars['displayReuseSessionUserLink'] === true
            ))
            ->willReturn('html');

        $response = $handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithOneNonSessionReuseOptionDoesNotShowUseMyDetailsLink(): void
    {
        $handler = $this->createHandlerWithReuseDetails([['label' => 'Other Person', 'data' => []]]);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/donor/form.twig', $this->callback(
                fn(array $vars) => $vars['displayReuseSessionUserLink'] === false
            ))
            ->willReturn('html');

        $response = $handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithMultipleReuseOptionsRedirectsToReuseDetails(): void
    {
        $handler = $this->createHandlerWithReuseDetails([
            ['label' => 'Me', 'data' => []],
            ['label' => 'Other', 'data' => []],
        ]);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/reuse-details');

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('reuse-details', $response->getHeaderLine('Location'));
    }

    public function testGetNoDonorRendersForm(): void
    {
        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route) => match ($route) {
                'lpa/donor/add' => '/lpa/91333263035/donor/add',
                default => '/lpa/91333263035/donor',
            }
        );
        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/donor/form.twig', $this->callback(
                fn(array $vars) => isset($vars['form'])
                    && $vars['cancelUrl'] === '/lpa/91333263035/donor'
                    && !isset($vars['isPopup'])
            ))
            ->willReturn('html');
        $response = $this->handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetNoDonorPopupRendersFormWithIsPopup(): void
    {
        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route) => match ($route) {
                'lpa/donor/add' => '/lpa/91333263035/donor/add',
                default => '/lpa/91333263035/donor',
            }
        );
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

    public function testPostUseMyDetailsPrefillsFormAndRenders(): void
    {
        $handler = $this->createHandlerWithReuseDetails([
            ['label' => 'Me', 'data' => $this->postData],
        ]);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->form->expects($this->once())->method('setData')->with($this->postData);
        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $response = $handler->handle($this->createRequest('POST', ['reuse-details' => '0']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidApiFailureThrowsException(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postData);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->lpaApplicationService->method('setDonor')->willReturn(false);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to save LPA donor for id: 91333263035');
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
}
