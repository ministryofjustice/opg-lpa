<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Form\Lpa\DateCheckForm;
use Application\Handler\Lpa\DateCheckHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Service\DateCheckViewModelHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateCheckHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private MvcUrlHelper&MockObject $urlHelper;
    private DateCheckForm&MockObject $form;
    private DateCheckHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(DateCheckForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->handler = new DateCheckHandler(
            $this->renderer,
            $this->formElementManager,
            $this->urlHelper,
        );
    }

    private function createLpa(?string $completedAt = null): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->donor = new Donor();
        $lpa->document->donor->canSign = true;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->whoIsRegistering = null;

        if ($completedAt !== null) {
            $lpa->completedAt = new \DateTime($completedAt);
        }

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        string $currentRouteName = 'lpa/date-check',
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, $currentRouteName);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersForm(): void
    {
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/date-check');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetRendersFormWithFormAction(): void
    {
        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('lpa/date-check', ['lpa-id' => 91333263035])
            ->willReturn('/lpa/91333263035/date-check');

        $this->form
            ->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/lpa/91333263035/date-check');

        $this->renderer->method('render')->willReturn('rendered-html');

        $this->handler->handle($this->createRequest());
    }

    public function testGetFromCompleteRouteSetsReturnRoute(): void
    {
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/date-check/complete');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/date-check/index.twig',
                $this->callback(function (array $vars) {
                    return $vars['returnRoute'] === 'lpa/complete';
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], null, 'lpa/date-check/complete')
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetFromDateCheckRouteHasNullReturnRoute(): void
    {
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/date-check');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/date-check/index.twig',
                $this->callback(function (array $vars) {
                    return $vars['returnRoute'] === null;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/date-check');
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['sign-date-donor' => ['day' => '1', 'month' => '1', 'year' => '2024']])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidDatesRedirectsToValid(): void
    {
        $lpa = $this->createLpa();

        $formData = [
            'sign-date-donor' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-certificate-provider' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->urlHelper
            ->method('generate')
            ->willReturnCallback(function (string $route) {
                if ($route === 'lpa/date-check/valid') {
                    return '/lpa/91333263035/date-check/valid';
                }
                return '/lpa/91333263035/date-check';
            });

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/lpa/91333263035/date-check/valid', $response->getHeaderLine('Location'));
    }

    public function testPostValidDatesWithReturnRouteRedirectsWithQueryParam(): void
    {
        $lpa = $this->createLpa();

        $formData = [
            'return-route' => 'lpa/complete',
            'sign-date-donor' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-certificate-provider' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->urlHelper
            ->method('generate')
            ->willReturnCallback(function (string $route, array $params, array $options = []) {
                if ($route === 'lpa/date-check/valid') {
                    $this->assertArrayHasKey('query', $options);
                    $this->assertSame('lpa/complete', $options['query']['return-route']);
                    return '/lpa/91333263035/date-check/valid?return-route=lpa%2Fcomplete';
                }
                return '/lpa/91333263035/date-check';
            });

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostInvalidDatesRendersFormWithErrors(): void
    {
        $lpa = $this->createLpa();

        // Use dates where donor signs after certificate provider - invalid order
        $formData = [
            'sign-date-donor' => ['day' => '10', 'month' => '1', 'year' => '2024'],
            'sign-date-certificate-provider' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->form
            ->expects($this->once())
            ->method('setMessages');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/date-check');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostReturnRouteFromPostDataTakesPrecedence(): void
    {
        $lpa = $this->createLpa();

        $formData = [
            'return-route' => 'lpa/summary',
            'sign-date-donor' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-certificate-provider' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->urlHelper
            ->method('generate')
            ->willReturnCallback(function (string $route, array $params, array $options = []) {
                if ($route === 'lpa/date-check/valid') {
                    $this->assertSame('lpa/summary', $options['query']['return-route']);
                    return '/lpa/91333263035/date-check/valid?return-route=lpa%2Fsummary';
                }
                return '/lpa/91333263035/date-check/complete';
            });

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa, 'lpa/date-check/complete')
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetRendersTemplateVariablesFromDateCheckViewModelHelper(): void
    {
        $lpa = $this->createLpa();

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/date-check');

        $expectedHelperResult = DateCheckViewModelHelper::build($lpa);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/date-check/index.twig',
                $this->callback(function (array $vars) use ($expectedHelperResult) {
                    return $vars['applicants'] === $expectedHelperResult['applicants']
                        && $vars['continuationSheets'] === $expectedHelperResult['continuationSheets'];
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    public function testPostWithReplacementAttorneyDatesExtractedCorrectly(): void
    {
        $lpa = $this->createLpa();

        $formData = [
            'sign-date-donor' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-certificate-provider' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-replacement-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->urlHelper
            ->method('generate')
            ->willReturnCallback(function (string $route) {
                if ($route === 'lpa/date-check/valid') {
                    return '/lpa/91333263035/date-check/valid';
                }
                return '/lpa/91333263035/date-check';
            });

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostWithApplicantDatesForCompletedLpa(): void
    {
        $lpa = $this->createLpa('2024-01-01');
        $lpa->document->whoIsRegistering = 'donor';
        $lpa->document->donor->name = new \MakeShared\DataModel\Common\Name([
            'title' => 'Mr',
            'first' => 'John',
            'last' => 'Doe',
        ]);

        $formData = [
            'sign-date-donor' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-certificate-provider' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-applicant-0' => ['day' => '3', 'month' => '1', 'year' => '2024'],
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->urlHelper
            ->method('generate')
            ->willReturnCallback(function (string $route) {
                if ($route === 'lpa/date-check/valid') {
                    return '/lpa/91333263035/date-check/valid';
                }
                return '/lpa/91333263035/date-check';
            });

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostWithLifeSustainingDateForHwLpa(): void
    {
        $lpa = $this->createLpa();
        $lpa->document->type = Document::LPA_TYPE_HW;

        $formData = [
            'sign-date-donor' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-donor-life-sustaining' => ['day' => '1', 'month' => '1', 'year' => '2024'],
            'sign-date-certificate-provider' => ['day' => '2', 'month' => '1', 'year' => '2024'],
            'sign-date-attorney-0' => ['day' => '2', 'month' => '1', 'year' => '2024'],
        ];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($formData);

        $this->urlHelper
            ->method('generate')
            ->willReturnCallback(function (string $route) {
                if ($route === 'lpa/date-check/valid') {
                    return '/lpa/91333263035/date-check/valid';
                }
                return '/lpa/91333263035/date-check';
            });

        $response = $this->handler->handle(
            $this->createRequest('POST', $formData, $lpa)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
