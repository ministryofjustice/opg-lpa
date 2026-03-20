<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\WhoAreYouForm;
use Application\Handler\Lpa\WhoAreYouHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class WhoAreYouHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    /** @var WhoAreYouForm&MockObject */
    private $form;
    /** @var Element&MockObject */
    private $whoElement;
    private WhoAreYouHandler $handler;

    private array $whoValueOptions = [
        'donor'                      => ['value' => 'donor'],
        'friendOrFamily'             => ['value' => 'friendOrFamily'],
        'financeProfessional'        => ['value' => 'financeProfessional'],
        'legalProfessional'          => ['value' => 'legalProfessional'],
        'estatePlanningProfessional' => ['value' => 'estatePlanningProfessional'],
        'digitalPartner'             => ['value' => 'digitalPartner'],
        'charity'                    => ['value' => 'charity'],
        'organisation'               => ['value' => 'organisation'],
        'other'                      => ['value' => 'other'],
        'notSaid'                    => ['value' => 'notSaid'],
    ];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->whoElement = $this->createMock(Element::class);
        $this->whoElement->method('getOptions')->willReturn(['value_options' => $this->whoValueOptions]);
        $this->whoElement->method('getValue')->willReturn('');

        $this->form = $this->createMock(WhoAreYouForm::class);
        $this->form->method('get')->with('who')->willReturn($this->whoElement);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new WhoAreYouHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(bool $whoAreYouAnswered = false): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->whoAreYouAnswered = $whoAreYouAnswered;

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        string $nextRoute = 'lpa/repeat-application'
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn($nextRoute);
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/who-are-you');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetWhoAreYouAlreadyAnsweredRendersAlreadyAnsweredView(): void
    {
        $lpa = $this->createLpa(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/repeat-application');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetRendersFormWithTenWhoOptions(): void
    {
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET'));

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
            $this->createRequest('POST', ['who' => ''])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidSavesAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn(['who' => 'donor']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setWhoAreYou')
            ->willReturn(true);

        $this->urlHelper
            ->method('generate')
            ->willReturn('/lpa/91333263035/repeat-application');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['who' => 'donor'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/repeat-application', $response->getHeaderLine('Location'));
    }

    public function testPostValidApiFailureThrowsException(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn(['who' => 'donor']);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setWhoAreYou')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set Who Are You for id: 91333263035');

        $this->handler->handle(
            $this->createRequest('POST', ['who' => 'donor'])
        );
    }
}
