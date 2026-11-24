<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Model\UserDetailsHolder;
use App\Service\Lpa\PaymentCommunication;
use App\Service\Mail\Exception\InvalidArgumentException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use DateTime;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PaymentCommunicationTest extends TestCase
{
    private PaymentCommunication $service;
    private MockObject&MailTransportInterface $mailTransport;
    private MockObject&UrlHelper $urlHelper;
    private MockObject&LoggerInterface $logger;
    private UserDetailsHolder $userDetailsHolder;
    private ?string $originalHttps;
    private ?string $originalHost;

    /** @var array<MailParameters> */
    private array $capturedParams = [];

    public function setUp(): void
    {
        $this->originalHttps = $_SERVER['HTTPS'] ?? null;
        $this->originalHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'front.example';

        $this->mailTransport = $this->createMock(MailTransportInterface::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->userDetailsHolder = new UserDetailsHolder();

        $this->service = new PaymentCommunication(
            $this->mailTransport,
            $this->urlHelper,
            $this->userDetailsHolder,
            $this->logger,
        );

        $user = new User(['email' => ['address' => 'test@email.com']]);
        $this->userDetailsHolder->set($user);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );
    }

    public function tearDown(): void
    {
        if ($this->originalHttps === null) {
            unset($_SERVER['HTTPS']);
        } else {
            $_SERVER['HTTPS'] = $this->originalHttps;
        }

        if ($this->originalHost === null) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $this->originalHost;
        }
    }

    public static function sendRegistrationCompleteEmailProvider(): array
    {
        return [
            'online payment hw' => [
                self::makeLpa(type: Document::LPA_TYPE_HW),
                [self::makePaymentOnlineEmail(lpaType: 'health and welfare',)],
            ],
            'online payment pfa' => [
                self::makeLpa(type: Document::LPA_TYPE_PF),
                [self::makePaymentOnlineEmail(lpaType: 'property and financial affairs',)],
            ],
            'online payment with ptn' => [
                self::makeLpa(ptn: true),
                [self::makePaymentOnlineEmail(ptnOnly: true,)],
            ],
            'online payment with reduced fee' => [
                self::makeLpa(reducedFee: true),
                [self::makePaymentOnlineEmail(feeFormOnly: true, remission: true,)],
            ],
            'online payment with ptn and reduced fee' => [
                self::makeLpa(ptn: true, reducedFee: true),
                [self::makePaymentOnlineEmail(feeFormPTN: true, remission: true,)],
            ],
            'online payment with payer' => [
                self::makeLpa(payer: true, type: Document::LPA_TYPE_PF),
                [
                    self::makePaymentPayerEmail(lpaType: 'property and financial affairs'),
                    self::makePaymentOnlineEmail(lpaType: 'property and financial affairs'),
                ],
            ],
            'online payment with payer, ptn and reduced fee' => [
                self::makeLpa(payer: true, ptn: true, reducedFee: true),
                [
                    self::makePaymentPayerEmail(),
                    self::makePaymentOnlineEmail(feeFormPTN: true, remission: true),
                ],
            ],
            'cheque payment hw' => [
                self::makeLpa(cheque: true, type: Document::LPA_TYPE_HW),
                [self::makePaymentChequeEmail(lpaType: 'health and welfare',)],
            ],
            'cheque payment pfa' => [
                self::makeLpa(cheque: true, type: Document::LPA_TYPE_PF),
                [self::makePaymentChequeEmail(lpaType: 'property and financial affairs',)],
            ],
            'cheque payment with ptn' => [
                self::makeLpa(cheque: true, ptn: true),
                [self::makePaymentChequeEmail(ptnOnly: true,)],
            ],
            'cheque payment with reduced fee' => [
                self::makeLpa(cheque: true, reducedFee: true),
                [self::makePaymentChequeEmail(feeFormOnly: true, remission: true,)],
            ],
            'cheque payment with ptn and reduced fee' => [
                self::makeLpa(cheque: true, ptn: true, reducedFee: true),
                [self::makePaymentChequeEmail(feeFormPTN: true, remission: true,)],
            ],
            'no payment hw' => [
                self::makeLpa(noPayment: true, type: Document::LPA_TYPE_HW),
                [self::makeNoPaymentEmail(lpaType: 'health and welfare',)],
            ],
            'no payment pfa' => [
                self::makeLpa(noPayment: true, type: Document::LPA_TYPE_PF),
                [self::makeNoPaymentEmail(lpaType: 'property and financial affairs',)],
            ],
            'no payment with ptn' => [
                self::makeLpa(noPayment: true, ptn: true),
                [self::makeNoPaymentEmail(ptn: true,)],
            ],
            'no payment with reduced fee' => [
                self::makeLpa(noPayment: true, reducedFee: true),
                [self::makeNoPaymentEmail()],
            ],
            'no payment with ptn and reduced fee' => [
                self::makeLpa(noPayment: true, ptn: true, reducedFee: true),
                [self::makeNoPaymentEmail(ptn: true,)],
            ],
        ];
    }

    #[DataProvider('sendRegistrationCompleteEmailProvider')]
    public function testSendRegistrationCompleteEmail(Lpa $lpa, array $mailParams): void
    {
        $this->setupEmailParamsExpectations($this->exactly(count($mailParams)));

        $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertMailParamsEqual(...$mailParams);
    }

    public function testSendRegistrationCompleteEmailSendFails(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => new Donor([
                    'name' => new LongName([
                        'first' => 'John',
                        'last' => 'Smith',
                    ]),
                ]),
            ]),
            'payment' => new Payment([
                'reducedFeeLowIncome' => true,
            ]),
        ]);

        $this->logger->expects($this->once())->method('error');

        // Sending the email throws an exception
        $this->mailTransport->method('send')
            ->willThrowException(new InvalidArgumentException());

        $this->service->sendRegistrationCompleteEmail($lpa);
    }

    private function setupEmailParamsExpectations(?InvokedCount $count = null): void
    {
        $this->capturedParams = [];
        $this->mailTransport
            ->expects($count ?? $this->once())
            ->method('send')
            ->willReturnCallback(function (MailParameters $params) {
                $this->capturedParams[] = $params;

                return true;
            });
    }

    private function assertMailParamsEqual(MailParameters ...$actual): void
    {
        $this->assertCount(count($actual), $this->capturedParams);

        for ($i = 0; $i < count($actual); $i++) {
            $capturedData = $this->capturedParams[$i]->getData();
            ksort($capturedData);
            $actualData = $actual[$i]->getData();
            ksort($actualData);

            $this->assertSame($actual[$i]->getToAddresses(), $this->capturedParams[$i]->getToAddresses());
            $this->assertSame($actual[$i]->getTemplateRef(), $this->capturedParams[$i]->getTemplateRef());
            $this->assertSame($actualData, $capturedData);
        }
    }

    private static function makeLpa(
        string $type = Document::LPA_TYPE_HW,
        bool $ptn = false,
        bool $reducedFee = false,
        bool $payer = false,
        bool $cheque = false,
        bool $noPayment = false,
    ): Lpa {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => $type,
                'donor' => new Donor([
                    'name' => new LongName(['title' => 'Father', 'first' => 'Spodo', 'last' => 'Komodo']),
                ]),
            ]),
            'payment' => new Payment([
                'method' => $noPayment ? null : ($cheque ? 'cheque' : 'card'),
                'amount' => $noPayment ? 0 : 432123,
                'email' => new EmailAddress(['address' => $payer ? 'paymentfrom@email.com' : 'test@email.com']),
                'reducedFeeLowIncome' => $reducedFee,
                'date' => new DateTime('2021-09-24 07:54:00'),
                'reference' => 12345678,
            ]),
        ]);

        if ($ptn) {
            $lpa->getDocument()->setPeopleToNotify([
                new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                ]),
            ]);
        }

        return $lpa;
    }

    private static function makePaymentOnlineEmail(
        string $to = 'test@email.com',
        string $lpaType = 'health and welfare',
        bool $ptnOnly = false,
        bool $feeFormOnly = false,
        bool $feeFormPTN = false,
        bool $remission = false,
    ): MailParameters {
        return new MailParameters(
            [$to],
            PaymentCommunication::EMAIL_LPA_REGISTRATION_WITH_PAYMENT,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => $lpaType,
                'lpaId' => 'A000 0000 0123',
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '432,123',
                'PTNOnly' => $ptnOnly,
                'feeFormOnly' => $feeFormOnly,
                'feeFormPTN' => $feeFormPTN,
                'remission' => $remission,
                'date' => '5 November 2021',
            ]
        );
    }

    private static function makePaymentPayerEmail(
        string $to = 'paymentfrom@email.com',
        string $lpaType = 'health and welfare',
    ): MailParameters {
        return new MailParameters(
            [$to],
            PaymentCommunication::EMAIL_LPA_REGISTRATION_WITH_PAYMENT_PAYER,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => $lpaType,
                'lpaId' => 'A000 0000 0123',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '432,123',
                'date' => '5 November 2021',
            ]
        );
    }

    private static function makePaymentChequeEmail(
        string $to = 'test@email.com',
        string $lpaType = 'health and welfare',
        bool $ptnOnly = false,
        bool $feeFormOnly = false,
        bool $feeFormPTN = false,
        bool $remission = false,
    ): MailParameters {
        return new MailParameters(
            [$to],
            PaymentCommunication::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => $lpaType,
                'lpaId' => 'A000 0000 0123',
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'feeAmount' => '432,123',
                'PTNOnly' => $ptnOnly,
                'feeFormOnly' => $feeFormOnly,
                'feeFormPTN' => $feeFormPTN,
                'remission' => $remission,
            ]
        );
    }

    private static function makeNoPaymentEmail(
        string $to = 'test@email.com',
        string $lpaType = 'health and welfare',
        bool $ptn = false,
    ): MailParameters {
        return new MailParameters(
            [$to],
            PaymentCommunication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => $lpaType,
                'lpaId' => 'A000 0000 0123',
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => $ptn,
            ]
        );
    }
}
