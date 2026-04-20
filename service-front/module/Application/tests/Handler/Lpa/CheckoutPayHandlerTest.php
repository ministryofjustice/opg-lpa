<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Alphagov\Pay\Client as GovPayClient;
use Alphagov\Pay\Response\Payment as GovPayPayment;
use Application\Handler\Lpa\CheckoutPayHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element\Submit;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CheckoutPayHandlerTest extends TestCase
{
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private Communication&MockObject $communicationService;
    private GovPayClient&MockObject $paymentClient;
    private MvcUrlHelper&MockObject $urlHelper;
    private CheckoutPayHandler $handler;

    protected function setUp(): void
    {
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->communicationService = $this->createMock(Communication::class);
        $this->paymentClient = $this->createMock(GovPayClient::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new CheckoutPayHandler(
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->communicationService,
            $this->paymentClient,
            $this->urlHelper,
        );
    }

    /**
     * Creates a GovPayPayment with stdClass nested objects, matching how the real Pay client builds them.
     *
     * @param array<string, mixed> $data
     */
    private function makeGovPayPayment(array $data): GovPayPayment
    {
        return new GovPayPayment((array) json_decode((string) json_encode($data)));
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

    private function createRequest(
        string $method,
        Lpa $lpa,
        bool $lpaComplete = true,
        array $postData = [],
    ): ServerRequest {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('backToForm')->willReturn($lpaComplete ? 'lpa/checkout' : 'lpa/other');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest([], [], 'https://example.com/lpa/' . $lpa->id . '/checkout/pay', $method))
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/checkout/pay');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    private function mockBlankFormInvalid(): void
    {
        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('isValid')->willReturn(false);
        $this->formElementManager->method('get')->willReturn($form);
    }

    public function testIncompleteLpaRedirectsToMoreInfoRequired(): void
    {
        $lpa = $this->createIncompleteLpa();

        $this->urlHelper->method('generate')
            ->willReturn('/lpa/91333263035/more-info-required');

        $response = $this->handler->handle($this->createRequest('GET', $lpa, false));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('more-info-required', $response->getHeaderLine('location'));
    }

    public function testPostWithInvalidFormRedirectsToCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $this->mockBlankFormInvalid();

        $this->urlHelper->method('generate')
            ->with('lpa/checkout', ['lpa-id' => $lpa->id], [])
            ->willReturn('/lpa/91333263035/checkout');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, true, ['some' => 'data']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('checkout', $response->getHeaderLine('location'));
    }

    public function testNoExistingGatewayReferenceCreatesNewPayment(): void
    {
        $lpa = $this->createCompleteLpa();

        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $this->formElementManager->method('get')->willReturn($form);

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'new-id',
            'state' => ['status' => 'created', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/pay']],
        ]);

        $this->paymentClient->expects($this->once())->method('createPayment')->willReturn($govPayPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay/response', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/checkout/pay/response');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('pay.gov.uk', $response->getHeaderLine('location'));
    }

    public function testExistingGatewayReferenceNullThrowsException(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->gatewayReference = 'existing-ref';

        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $this->formElementManager->method('get')->willReturn($form);

        $this->paymentClient->method('getPayment')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid GovPay payment reference: existing-ref');

        $this->handler->handle($this->createRequest('GET', $lpa));
    }

    public function testExistingSuccessfulPaymentFinishesCheckout(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->gatewayReference = 'existing-ref';

        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $form->method('get')->willReturn($this->createMock(Submit::class));
        $this->formElementManager->method('get')->willReturn($form);

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'reference' => 'ref-123',
            'email' => 'user@example.com',
            'state' => ['status' => 'success', 'finished' => true],
            '_links' => [],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->lpaApplicationService->expects($this->once())->method('lockLpa');
        $this->communicationService->expects($this->once())->method('sendRegistrationCompleteEmail');
        $this->urlHelper->method('generate')
            ->with('lpa/complete', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/complete');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('complete', $response->getHeaderLine('location'));
    }

    public function testExistingUnfinishedPaymentRedirectsToPaymentPage(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->gatewayReference = 'existing-ref';

        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $this->formElementManager->method('get')->willReturn($form);

        $govPayPayment = $this->makeGovPayPayment([
            'payment_id' => 'existing-ref',
            'state' => ['status' => 'started', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/existing']],
        ]);

        $this->paymentClient->method('getPayment')->willReturn($govPayPayment);

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://pay.gov.uk/existing', $response->getHeaderLine('location'));
    }

    public function testFinishedUnsuccessfulPaymentCreatesNewPayment(): void
    {
        $lpa = $this->createCompleteLpa();
        $lpa->payment->gatewayReference = 'finished-ref';

        $form = $this->createMock(\Application\Form\Lpa\BlankMainFlowForm::class);
        $form->method('setAttribute')->willReturnSelf();
        $this->formElementManager->method('get')->willReturn($form);

        $existingPayment = $this->makeGovPayPayment([
            'payment_id' => 'finished-ref',
            'state' => ['status' => 'failed', 'finished' => true],
            '_links' => [],
        ]);
        $this->paymentClient->method('getPayment')->willReturn($existingPayment);

        $newPayment = $this->makeGovPayPayment([
            'payment_id' => 'new-id',
            'state' => ['status' => 'created', 'finished' => false],
            '_links' => ['next_url' => ['href' => 'https://pay.gov.uk/new']],
        ]);
        $this->paymentClient->expects($this->once())->method('createPayment')->willReturn($newPayment);
        $this->lpaApplicationService->expects($this->once())->method('updateApplication');
        $this->urlHelper->method('generate')
            ->with('lpa/checkout/pay/response', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/checkout/pay/response');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://pay.gov.uk/new', $response->getHeaderLine('location'));
    }
}
