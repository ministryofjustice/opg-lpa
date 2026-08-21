<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\CheckoutIndexHandler;
use App\Middleware\RequestAttribute;
use App\Service\Payment\CardPayments;
use App\Service\Payment\Helper\CheckoutHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element\Submit;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutIndexHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private UrlHelper&MockObject $urlHelper;
    private CheckoutHelper&MockObject $checkoutHelper;
    private CardPayments&MockObject $cardPayments;
    private CheckoutIndexHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->checkoutHelper = $this->createMock(CheckoutHelper::class);
        $this->cardPayments = $this->createMock(CardPayments::class);

        $this->handler = new CheckoutIndexHandler(
            $this->renderer,
            $this->formElementManager,
            $this->urlHelper,
            $this->cardPayments,
            $this->checkoutHelper,
        );
    }

    private function createCompleteLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setPayment(new Payment());
        Calculator::calculate($lpa);
        return $lpa;
    }

    private function createRepeatApplicationLpa(): Lpa
    {
        $lpa = $this->createCompleteLpa();
        $lpa->setRepeatCaseNumber(123456);
        return $lpa;
    }

    private function createRequest(string $method, Lpa $lpa): ServerRequest
    {
        $request = new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->getId() . '/checkout', $method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout');

        if ($method === 'POST') {
            $request = $request->withParsedBody([]);
        }

        return $request;
    }

    private function mockForm(): MockObject
    {
        $submitElement = $this->createMock(Submit::class);
        $submitElement->method('setAttribute')->willReturnSelf();

        $form = $this->createMock(\App\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('get')->with('submit')->willReturn($submitElement);

        $this->formElementManager->method('get')
            ->with('App\Form\Lpa\BlankMainFlowForm', $this->anything())
            ->willReturn($form);

        return $form;
    }

    public function testRecoveredPaymentRedirectsToFinishCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $finishResponse = new RedirectResponse('/lpa/123/complete');

        $this->cardPayments->expects($this->once())
            ->method('recoverCompletedPayment')
            ->with($lpa)
            ->willReturn(true);

        $this->checkoutHelper->expects($this->once())
            ->method('finishCheckout')
            ->with($lpa, $this->isInstanceOf(ServerRequest::class))
            ->willReturn($finishResponse);

        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));
        $this->assertSame($finishResponse, $response);
    }

    public function testPostWithIncompleteLpaRedirectsToMoreInfoRequired(): void
    {
        $lpa = $this->createCompleteLpa();
        $redirectResponse = new RedirectResponse('/lpa/123/more-info-required');

        $this->cardPayments->method('recoverCompletedPayment')->willReturn(false);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(false);
        $this->checkoutHelper->expects($this->once())
            ->method('redirectToMoreInfoRequired')
            ->with($lpa, $this->isInstanceOf(ServerRequest::class))
            ->willReturn($redirectResponse);

        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest('POST', $lpa));
        $this->assertSame($redirectResponse, $response);
    }

    public function testGetRendersCheckoutForm(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockForm();

        $this->cardPayments->method('recoverCompletedPayment')->willReturn(false);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/checkout/pay');
        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/lpa/checkout/index.twig', $this->anything())
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithCompleteLpaRendersCheckoutForm(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockForm();

        $this->cardPayments->method('recoverCompletedPayment')->willReturn(false);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/checkout/pay');
        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/lpa/checkout/index.twig', $this->anything())
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', $lpa));
        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testFormIsConfiguredWithCorrectActionAndClass(): void
    {
        $lpa = $this->createCompleteLpa();

        $this->cardPayments->method('recoverCompletedPayment')->willReturn(false);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/checkout/pay');
        $this->renderer->method('render')->willReturn('html');

        $formSetAttributes = [];
        $form = $this->createMock(\App\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnCallback(
            function ($key, $value) use (&$formSetAttributes, $form) {
                $formSetAttributes[] = [$key, $value];
                return $form;
            }
        );

        $submitSetAttributes = [];
        $submitElement = $this->createMock(Submit::class);
        $submitElement->method('setAttribute')->willReturnCallback(
            function ($key, $value) use (&$submitSetAttributes, $submitElement) {
                $submitSetAttributes[] = [$key, $value];
                return $submitElement;
            }
        );

        $form->method('get')->with('submit')->willReturn($submitElement);

        $this->formElementManager->method('get')
            ->with('App\Form\Lpa\BlankMainFlowForm', $this->anything())
            ->willReturn($form);

        $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertContains(['action', '/lpa/123/checkout/pay'], $formSetAttributes);
        $this->assertContains(['class', 'js-single-use'], $formSetAttributes);
        $this->assertContains(['value', 'Confirm and pay by card'], $submitSetAttributes);
        $this->assertContains(['data-cy', 'confirm-and-pay-by-card'], $submitSetAttributes);
    }

    public function testNonRepeatApplicationFeesArePassed(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockForm();

        $this->cardPayments->method('recoverCompletedPayment')->willReturn(false);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/checkout/pay');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/checkout/index.twig',
                $this->callback(function ($data) {
                    $this->assertSame(Calculator::getLowIncomeFee(false), $data['lowIncomeFee']);
                    $this->assertSame(Calculator::getFullFee(false), $data['fullFee']);
                    return true;
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest('GET', $lpa));
    }

    public function testRepeatApplicationFeesArePassed(): void
    {
        $lpa = $this->createRepeatApplicationLpa();
        $this->mockForm();

        $this->cardPayments->method('recoverCompletedPayment')->willReturn(false);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/checkout/pay');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/checkout/index.twig',
                $this->callback(function ($data) {
                    $this->assertSame(Calculator::getLowIncomeFee(true), $data['lowIncomeFee']);
                    $this->assertSame(Calculator::getFullFee(true), $data['fullFee']);
                    return true;
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest('GET', $lpa));
    }

    public function testTemplateVariablesIncludeLpaCompletionStatus(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockForm();

        $this->cardPayments->method('recoverCompletedPayment')->willReturn(false);
        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/lpa/123/checkout/pay');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/checkout/index.twig',
                $this->callback(function ($data) {
                    $this->assertArrayHasKey('lpaIsCompleted', $data);
                    $this->assertTrue($data['lpaIsCompleted']);
                    return true;
                })
            )
            ->willReturn('html');

        $this->handler->handle($this->createRequest('GET', $lpa));
    }
}
