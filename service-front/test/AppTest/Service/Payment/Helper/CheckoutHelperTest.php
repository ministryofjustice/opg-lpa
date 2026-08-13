<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa\Traits;

use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\Helper\CheckoutHelper;
use Exception;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutHelperTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private UrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private CheckoutHelper $helper;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->helper = new CheckoutHelper(
            $this->lpaApplicationService,
            $this->communicationService,
            $this->urlHelper,
            $this->logger,
        );
    }

    public function testIsLpaCompleteReturnsTrueWhenCreatedAndFlowReturnsCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $request = $this->createRequest($lpa, 'lpa/checkout');

        $this->assertTrue($this->helper->isLpaComplete($lpa, $request));
    }

    public function testIsLpaCompleteReturnsFalseWhenFlowDoesNotReturnCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $request = $this->createRequest($lpa, 'lpa/other');

        $this->assertFalse($this->helper->isLpaComplete($lpa, $request));
    }

    public function testRedirectToMoreInfoRequiredUsesExpectedRouteAndOptions(): void
    {
        $lpa = $this->createIncompleteLpa();
        $request = $this->createRequest($lpa, 'lpa/other', ['foo' => 'bar']);

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/more-info-required', ['lpa-id' => $lpa->getId()], ['foo' => 'bar'])
            ->willReturn('/lpa/91333263035/more-info-required?foo=bar');

        $response = $this->helper->redirectToMoreInfoRequired($lpa, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/lpa/91333263035/more-info-required?foo=bar', $response->getHeaderLine('location'));
    }

    public function testFinishCheckoutLocksSendsEmailAndRedirectsToComplete(): void
    {
        $lpa = $this->createCompleteLpa();
        $request = $this->createRequest($lpa, 'lpa/checkout');

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa);
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail')->with($lpa);
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->getId()])
            ->willReturn('/lpa/91333263035/complete');

        $response = $this->helper->finishCheckout($lpa, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/lpa/91333263035/complete', $response->getHeaderLine('location'));
    }

    public function testVerifyLpaPaymentAmountWhenAmountChangesResetsGatewayReferenceAndPersists(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setGatewayReference('original-gateway-ref');
        $lpa->getPayment()->setAmount(99999.0);

        $this->logger->expects($this->once())->method('info');
        $this->lpaApplicationService->expects($this->once())
            ->method('setPayment')
            ->with(
                $lpa,
                $this->callback(function (Payment $payment): bool {
                    return $payment->getGatewayReference() === null;
                })
            )
            ->willReturn(true);

        $this->helper->verifyLpaPaymentAmount($lpa);
    }

    public function testVerifyLpaPaymentAmountThrowsWhenPersistFailsAfterAmountChange(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->getPayment()->setAmount(99999.0);

        $this->logger->expects($this->once())->method('info');
        $this->lpaApplicationService->method('setPayment')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'API client failed to set payment details for id: '
            . $lpa->getId()
            . ' in App\Service\Payment\Helper\CheckoutHelper'
        );

        $this->helper->verifyLpaPaymentAmount($lpa);
    }

    public function testPadLpaIdPadsShortIdWithLeadingZeros(): void
    {
        $this->assertSame('00000012345', $this->helper::padLpaId('12345'));
    }

    public function testPadLpaIdReturnsExactLengthIdUnchanged(): void
    {
        $this->assertSame('12345678901', $this->helper::padLpaId('12345678901'));
    }

    public function testPadLpaIdThrowsExceptionWhenIdIsTooLong(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LPA ID is too long');

        $this->helper::padLpaId('123456789012');
    }

    public function testConstructPaymentTransactionIdDelegatesToPadLpaId(): void
    {
        $this->assertSame('00000012345', $this->helper::constructPaymentTransactionId('12345'));
    }

    private function createRequest(Lpa $lpa, string $backToForm, array $routeOptions = []): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($backToForm);
        $flowChecker->method('getRouteOptions')->willReturn($routeOptions);

        return new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->getId() . '/checkout', 'GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker);
    }

    private function createCompleteLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setPayment(new Payment());
        Calculator::calculate($lpa);

        return $lpa;
    }

    private function createIncompleteLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->setId(91333263035);
        $lpa->setDocument(new Document());
        $lpa->setPayment(new Payment());

        return $lpa;
    }
}
