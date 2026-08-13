<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Handler\Lpa\CheckoutConfirmHandler;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\Helper\CheckoutHelper;
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
use RuntimeException;

class CheckoutConfirmHandlerTest extends TestCase
{
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private UrlHelper&MockObject $urlHelper;
    private CheckoutHelper&MockObject $checkoutHelper;
    private CheckoutConfirmHandler $handler;

    protected function setUp(): void
    {
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->checkoutHelper = $this->createMock(CheckoutHelper::class);

        $this->handler = new CheckoutConfirmHandler(
            $this->lpaApplicationService,
            $this->communicationService,
            $this->urlHelper,
            $this->checkoutHelper,
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

        return (new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->id . '/checkout/confirm', 'GET'))
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout/confirm');
    }

    public function testIncompleteLpaRedirectsToMoreInfoRequired(): void
    {
        $lpa = $this->createIncompleteLpa();

        $this->checkoutHelper->method('isLpaComplete')->willReturn(false);
        $this->checkoutHelper->method('redirectToMoreInfoRequired')
            ->willReturn(new RedirectResponse('/lpa/91333263035/more-info-required'));

        $response = $this->handler->handle($this->createRequest($lpa, false));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('more-info-required', $response->getHeaderLine('location'));
    }

    public function testThrowsWhenAmountIsNonZero(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->amount = 92;

        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid option');

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testSuccessWithZeroAmountLocksAndRedirects(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->amount = 0;
        $lpa->payment->reducedFeeUniversalCredit = true;

        $this->checkoutHelper->method('isLpaComplete')->willReturn(true);
        $this->checkoutHelper->method('finishCheckout')
            ->willReturn(new RedirectResponse('/lpa/91333263035/complete'));

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('complete', $response->getHeaderLine('location'));
    }
}
