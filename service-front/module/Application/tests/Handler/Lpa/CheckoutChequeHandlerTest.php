<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\CheckoutChequeHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CheckoutChequeHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private CheckoutChequeHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new CheckoutChequeHandler(
            $this->lpaApplicationService,
            $this->communicationService,
            $this->urlHelper,
        );
    }

    private function createCompleteLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->payment = new Payment();
        Calculator::calculate($lpa);

        return $lpa;
    }

    private function createIncompleteLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->payment = new Payment();

        return $lpa;
    }

    private function createRequest(Lpa $lpa, bool $lpaComplete = true): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($lpaComplete ? 'lpa/checkout' : 'lpa/other');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        return (new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->id . '/checkout/cheque', 'POST'))
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout/cheque');
    }

    public function testIncompleteLpaRedirectsToMoreInfoRequired(): void
    {
        $lpa = $this->createIncompleteLpa();

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/more-info-required', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/more-info-required');

        $response = $this->handler->handle($this->createRequest($lpa, false));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('more-info-required', $response->getHeaderLine('location'));
    }

    public function testThrowsWhenSetPaymentFails(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->amount = 9200;

        $this->lpaApplicationService->method('setPayment')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'API client failed to set payment details for id: ' . $lpa->id
            . ' in Application\Handler\Lpa\CheckoutChequeHandler'
        );

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testThrowsWhenSetPaymentFailsAfterAmountChange(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->amount = 99999;
        $lpa->payment->gatewayReference = 'old-ref';

        $this->lpaApplicationService->method('setPayment')->willReturn(false);

        $this->expectException(RuntimeException::class);

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testSuccessLocksLpaAndRedirectsToComplete(): void
    {
        $lpa = $this->createCompleteLpa();

        $this->lpaApplicationService->method('setPayment')->willReturn(true);
        $this->lpaApplicationService->expects($this->once())->method('lockLpa');
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail');

        $this->urlHelper->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/complete');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('complete', $response->getHeaderLine('location'));
    }
}
