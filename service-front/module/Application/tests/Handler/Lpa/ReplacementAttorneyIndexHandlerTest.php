<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\BlankMainFlowForm;
use Application\Handler\Lpa\ReplacementAttorneyIndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReplacementAttorneyIndexHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private MvcUrlHelper&MockObject $urlHelper;
    private Metadata&MockObject $metadata;
    private BlankMainFlowForm&MockObject $form;
    private ReplacementAttorneyIndexHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->form = $this->createMock(BlankMainFlowForm::class);

        $this->formElementManager->method('get')->willReturn($this->form);
        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) => '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new ReplacementAttorneyIndexHandler(
            $this->renderer,
            $this->formElementManager,
            $this->urlHelper,
            $this->metadata,
        );
    }

    private function createLpa(bool $withAttorneys = false): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        if (!$withAttorneys) {
            $lpa->document->replacementAttorneys = [];
        }
        return $lpa;
    }

    private function createRequest(string $method = 'GET', array $postData = [], ?Lpa $lpa = null): ServerRequest
    {
        $lpa = $lpa ?? $this->createLpa();
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/when-replacement-attorney-step-in');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/replacement-attorney');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetNoAttorneysRendersForm(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with(
                'application/authenticated/lpa/replacement-attorney/index.twig',
                $this->callback(fn(array $vars) => $vars['attorneys'] === [] && isset($vars['form']))
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', [], $this->createLpa(false)));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithAttorneysRendersFormWithAttorneyParams(): void
    {
        $lpa = $this->createLpa(true);

        $this->renderer->expects($this->once())->method('render')
            ->with(
                'application/authenticated/lpa/replacement-attorney/index.twig',
                $this->callback(function (array $vars) use ($lpa): bool {
                    return count($vars['attorneys']) === count($lpa->document->replacementAttorneys)
                        && isset($vars['attorneys'][0]['editRoute'])
                        && isset($vars['attorneys'][0]['confirmDeleteRoute'])
                        && isset($vars['attorneys'][0]['deleteRoute']);
                })
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', ['csrf' => 'token']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidRedirectsToNextRoute(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->metadata->expects($this->once())->method('setReplacementAttorneysConfirmed');

        $response = $this->handler->handle($this->createRequest('POST', ['csrf' => 'token']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('when-replacement-attorney-step-in', $response->getHeaderLine('Location'));
    }
}
