<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\ReuseDetailsForm;
use Application\Handler\Lpa\ReuseDetailsHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReuseDetailsHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private MvcUrlHelper&MockObject $urlHelper;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private ReuseDetailsForm&MockObject $form;
    private ReuseDetailsHandler $handler;

    private array $reuseDetails = [
        0 => ['label' => 'Alice Smith (myself)', 'data' => ['name-first' => 'Alice', 'name-last' => 'Smith']],
        1 => ['label' => 'Bob Jones (was the donor)', 'data' => ['name-first' => 'Bob', 'name-last' => 'Jones']],
    ];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);
        $this->form = $this->createMock(ReuseDetailsForm::class);

        $this->formElementManager->method('get')->willReturn($this->form);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/reuse-details');

        $this->handler = new ReuseDetailsHandler(
            $this->renderer,
            $this->formElementManager,
            $this->urlHelper,
            $this->actorReuseDetailsService,
        );
    }

    private function createLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 123;
        $lpa->document = new Document();
        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $queryParams = [],
        array $postData = [],
    ): ServerRequest {
        $lpa = $this->createLpa();
        $user = $this->createMock(User::class);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/lpa/123/reuse-details'))
            ->withQueryParams($queryParams)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $user);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    private function defaultQueryParams(): array
    {
        return [
            'calling-url'    => '/lpa/123/donor/add',
            'include-trusts' => '0',
            'actor-name'     => 'Donor',
        ];
    }

    public function testGetThrowsExceptionWhenRequiredParamsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required data missing when attempting to load the reuse details screen');

        $this->handler->handle($this->createRequest('GET', []));
    }

    public function testGetRendersFormForNonCorrespondent(): void
    {
        $this->actorReuseDetailsService
            ->expects($this->once())
            ->method('getActorReuseDetails')
            ->willReturn($this->reuseDetails);

        $this->actorReuseDetailsService
            ->expects($this->never())
            ->method('getCorrespondentReuseDetails');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/reuse-details/index.twig',
                $this->callback(fn(array $vars) =>
                    isset($vars['form']) && $vars['actorName'] === 'Donor' && $vars['cancelUrl'] === '/lpa/123/donor')
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('GET', $this->defaultQueryParams()));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetRendersFormForCorrespondent(): void
    {
        $this->actorReuseDetailsService
            ->expects($this->once())
            ->method('getCorrespondentReuseDetails')
            ->willReturn($this->reuseDetails);

        $this->actorReuseDetailsService
            ->expects($this->never())
            ->method('getActorReuseDetails');

        $this->renderer->method('render')->willReturn('<html>form</html>');

        $queryParams = [
            'calling-url'    => '/lpa/123/correspondent/edit',
            'include-trusts' => '0',
            'actor-name'     => 'Correspondent',
        ];

        $response = $this->handler->handle($this->createRequest('GET', $queryParams));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetSetsPopupFlagWhenXhrRequest(): void
    {
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn($this->reuseDetails);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/reuse-details/index.twig',
                $this->callback(fn(array $vars) => isset($vars['isPopup']) && $vars['isPopup'] === true)
            )
            ->willReturn('<html>popup</html>');

        $lpa = $this->createLpa();
        $user = $this->createMock(User::class);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withUri(new Uri('/lpa/123/reuse-details'))
            ->withQueryParams($this->defaultQueryParams())
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $user);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn($this->reuseDetails);
        $this->form->method('isValid')->willReturn(false);
        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', $this->defaultQueryParams(), ['reuse-details' => ''])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormRedirectsWithReuseDetailsIndex(): void
    {
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn($this->reuseDetails);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['reuse-details' => '1']);

        $response = $this->handler->handle(
            $this->createRequest('POST', $this->defaultQueryParams(), ['reuse-details' => '1'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('/lpa/123/donor/add', $location);
        $this->assertStringContainsString('reuseDetailsIndex=1', $location);
        $this->assertStringContainsString('callingUrl=', $location);
    }

    public function testPostWithTrustSelectionAppendsTrustToUrl(): void
    {
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn($this->reuseDetails);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['reuse-details' => 't']);

        $queryParams = [
            'calling-url'    => '/lpa/123/primary-attorney/add',
            'include-trusts' => '1',
            'actor-name'     => 'Attorney',
        ];

        $response = $this->handler->handle(
            $this->createRequest('POST', $queryParams, ['reuse-details' => 't'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('/lpa/123/primary-attorney/add-trust', $location);
        $this->assertStringContainsString('reuseDetailsIndex=t', $location);
    }
}
