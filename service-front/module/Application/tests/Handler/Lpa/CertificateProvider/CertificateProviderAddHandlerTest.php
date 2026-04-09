<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\CertificateProvider;

use Application\Form\Lpa\AbstractActorForm;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CertificateProviderAddHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private Metadata&MockObject $metadata;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private CertificateProviderAddHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);

        $this->handler = new CertificateProviderAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->metadata,
            $this->actorReuseDetailsService,
        );
    }

    private function createLpa(bool $withCp = false): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];

        if ($withCp) {
            $lpa->document->certificateProvider = new CertificateProvider();
        }

        return $lpa;
    }

    private function createUser(string $first = 'Test', string $last = 'User'): User
    {
        $user = new User();
        $user->name = new Name(['title' => 'Mr', 'first' => $first, 'last' => $last]);
        return $user;
    }

    private function createRequest(
        string $method = 'GET',
        ?Lpa $lpa = null,
        array $postData = [],
        ?User $user = null,
        array $queryParams = [],
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();
        $user = $user ?? $this->createUser();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/people-to-notify');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/certificate-provider/add')
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withQueryParams($queryParams);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    private function createForm(): AbstractActorForm&MockObject
    {
        $form = $this->createMock(AbstractActorForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('setActorData')->willReturnSelf();
        return $form;
    }

    public function testGetRedirectsWhenCpAlreadyExists(): void
    {
        $lpa = $this->createLpa(true);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/certificate-provider');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetWithOneReuseOptionShowsUseMyDetailsLink(): void
    {
        $form = $this->createForm();
        $this->formElementManager->method('get')->willReturn($form);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Test User (myself)', 'data' => ['name-first' => 'Test']],
        ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/certificate-provider/form.twig',
                $this->callback(function (array $params): bool {
                    $this->assertTrue($params['displayReuseSessionUserLink'] ?? false);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormSavesCertificateProvider(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Provider'],
            'address' => ['address1' => '1 Street', 'postcode' => 'AB1 2CD'],
        ]);

        $this->formElementManager->method('get')->willReturn($form);
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->lpaApplicationService->expects($this->once())
            ->method('setCertificateProvider')
            ->willReturn(true);
        $this->metadata->expects($this->once())
            ->method('removeMetadata');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify');

        $response = $this->handler->handle(
            $this->createRequest('POST', null, ['name-first' => 'Test'])
        );
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostWithValidFormPopupReturnsJson(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Provider'],
            'address' => ['address1' => '1 Street', 'postcode' => 'AB1 2CD'],
        ]);

        $this->formElementManager->method('get')->willReturn($form);
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->lpaApplicationService->method('setCertificateProvider')->willReturn(true);
        $this->metadata->method('removeMetadata');

        $request = $this->createRequest('POST', null, ['name-first' => 'Test'])
            ->withHeader('X-Requested-With', 'XMLHttpRequest');

        $response = $this->handler->handle($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(false);

        $this->formElementManager->method('get')->willReturn($form);
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', null, ['name-first' => ''])
        );
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithApiFailureThrowsException(): void
    {
        $form = $this->createForm();
        $form->method('setData')->willReturnSelf();
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Provider'],
            'address' => ['address1' => '1 Street', 'postcode' => 'AB1 2CD'],
        ]);

        $this->formElementManager->method('get')->willReturn($form);
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->lpaApplicationService->method('setCertificateProvider')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->handler->handle(
            $this->createRequest('POST', null, ['name-first' => 'Test'])
        );
    }

    public function testGetWithReuseDetailsIndexBindsDataToForm(): void
    {
        $form = $this->createForm();
        $form->expects($this->once())->method('bind');

        $this->formElementManager->method('get')->willReturn($form);
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Test User (myself)', 'data' => ['name-first' => 'Test']],
        ]);
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', null, [], null, ['reuseDetailsIndex' => '0'])
        );
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
