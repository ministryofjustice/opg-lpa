<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Form\Lpa\TypeForm;
use Application\Handler\LpaTypeHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LpaTypeHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private FlashMessenger&MockObject $flashMessenger;
    private MvcUrlHelper&MockObject $urlHelper;
    private TypeForm&MockObject $form;
    private LpaTypeHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->form = $this->createMock(TypeForm::class);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\Lpa\TypeForm')
            ->willReturn($this->form);

        $this->handler = new LpaTypeHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->flashMessenger,
            $this->urlHelper,
        );
    }

    private function createRequest(string $method = 'GET', array $body = []): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute('secondsUntilSessionExpires', 3600);

        if ($method === 'POST') {
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    public function testGetRequestRendersForm(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/type/index.twig',
                $this->callback(
                    fn($p) => $p['form'] === $this->form
                        && $p['isChangeAllowed'] === true
                        && $p['currentRouteName'] === 'lpa-type-no-id'
                )
            )
            ->willReturn('<html lang="en">type form</html>');

        $response = $this->handler->handle($this->createRequest('GET'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostInvalidFormRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/authenticated/lpa/type/index.twig', $this->anything())
            ->willReturn('<html lang="en">form with errors</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['type' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidFormCreateApplicationFailureFlashesErrorAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['type' => Document::LPA_TYPE_HW]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('createApplication')
            ->willReturn(false);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('Error creating a new LPA. Please try again.');

        $response = $this->handler->handle($this->createRequest('POST', ['type' => Document::LPA_TYPE_HW]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testPostValidFormSetTypeFailureThrowsException(): void
    {
        $lpa = FixturesData::getHwLpa();

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['type' => Document::LPA_TYPE_HW]);

        $this->lpaApplicationService->expects($this->once())->method('createApplication')->willReturn($lpa);
        $this->lpaApplicationService->expects($this->once())->method('setType')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to set LPA type for id: ' . $lpa->id);

        $this->handler->handle($this->createRequest('POST', ['type' => Document::LPA_TYPE_HW]));
    }

    public function testPostValidFormSuccessfullyCreatesLpaAndRedirects(): void
    {
        $lpa = new Lpa(['id' => 123, 'document' => ['type' => Document::LPA_TYPE_PF]]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['type' => Document::LPA_TYPE_PF]);

        $this->lpaApplicationService->expects($this->once())->method('createApplication')->willReturn($lpa);
        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setType')
            ->with($lpa, Document::LPA_TYPE_PF)
            ->willReturn(true);

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('lpa/donor', ['lpa-id' => $lpa->id], [])
            ->willReturn('/lpa/123/donor');

        $response = $this->handler->handle($this->createRequest('POST', ['type' => Document::LPA_TYPE_PF]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/lpa/123/donor', $response->getHeaderLine('Location'));
    }
}
