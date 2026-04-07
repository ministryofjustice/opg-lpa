<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\InstructionsAndPreferencesForm;
use Application\Handler\Lpa\InstructionsHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InstructionsHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Metadata&MockObject $metadata;
    private MvcUrlHelper&MockObject $urlHelper;
    /** @var InstructionsAndPreferencesForm&MockObject */
    private $form;
    private InstructionsHandler $handler;

    private array $postData = [
        'instruction' => 'Unit test instructions',
        'preference' => 'Unit test preferences',
    ];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(InstructionsAndPreferencesForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new InstructionsHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->metadata,
            $this->urlHelper,
        );
    }

    private function createLpa(
        ?string $instruction = null,
        ?string $preference = null,
        ?array $metadata = null,
    ): Lpa {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->instruction = $instruction;
        $lpa->document->preference = $preference;
        $lpa->metadata = $metadata ?? [];

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/applicant');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/instructions');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetBindsAndRendersForm(): void
    {
        $lpa = $this->createLpa('existing instruction', 'existing preference');

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', $this->postData)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInstructionsSetFailed(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($this->postData);

        $this->lpaApplicationService
            ->method('setInstructions')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA instructions for id: 91333263035');

        $this->handler->handle(
            $this->createRequest('POST', $this->postData)
        );
    }

    public function testPostPreferencesSetFailed(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($this->postData);

        $this->lpaApplicationService
            ->method('setInstructions')
            ->willReturn(true);

        $this->lpaApplicationService
            ->method('setPreferences')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA preferences for id: 91333263035');

        $this->handler->handle(
            $this->createRequest('POST', $this->postData)
        );
    }

    public function testPostValidSavesAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($this->postData);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setInstructions')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPreferences')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/applicant');

        $response = $this->handler->handle(
            $this->createRequest('POST', $this->postData)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString(
            '/lpa/91333263035/applicant',
            $response->getHeaderLine('Location')
        );
    }

    public function testPostValidUnchangedValuesSkipsSave(): void
    {
        $lpa = $this->createLpa('Unit test instructions', 'Unit test preferences');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($this->postData);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setInstructions');

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('setPreferences');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/applicant');

        $response = $this->handler->handle(
            $this->createRequest('POST', $this->postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @return array<string, array{?array}>
     */
    public static function metadataRequiringConfirmationProvider(): array
    {
        return [
            'metadata not set' => [null],
            'instruction-confirmed not set' => [[]],
            'instruction-confirmed is false' => [['instruction-confirmed' => false]],
        ];
    }

    #[DataProvider('metadataRequiringConfirmationProvider')]
    public function testPostValidSetsMetadataWhenNotConfirmed(?array $metadata): void
    {
        $lpa = $this->createLpa(null, null, $metadata);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($this->postData);

        $this->lpaApplicationService->method('setInstructions')->willReturn(true);
        $this->lpaApplicationService->method('setPreferences')->willReturn(true);

        $this->metadata
            ->expects($this->once())
            ->method('setInstructionConfirmed')
            ->with($lpa);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/applicant');

        $response = $this->handler->handle(
            $this->createRequest('POST', $this->postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidSkipsMetadataWhenAlreadyConfirmed(): void
    {
        $lpa = $this->createLpa(null, null, ['instruction-confirmed' => true]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($this->postData);

        $this->lpaApplicationService->method('setInstructions')->willReturn(true);
        $this->lpaApplicationService->method('setPreferences')->willReturn(true);

        $this->metadata
            ->expects($this->never())
            ->method('setInstructionConfirmed');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/applicant');

        $response = $this->handler->handle(
            $this->createRequest('POST', $this->postData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
