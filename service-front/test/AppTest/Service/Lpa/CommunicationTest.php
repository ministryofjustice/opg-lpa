<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Model\UserDetailsHolder;
use App\Service\Lpa\Communication;
use App\Service\Mail\Exception\InvalidArgumentException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use DateTime;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Formatter;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CommunicationTest extends TestCase
{
    private Communication $service;
    private MockObject&MailTransportInterface $mailTransport;
    private MockObject&UrlHelper $urlHelper;
    private UserDetailsHolder $userDetailsHolder;
    private ?string $originalHttps;
    private ?string $originalHost;
    private ?MailParameters $capturedParams = null;

    public function setUp(): void
    {
        $this->originalHttps = $_SERVER['HTTPS'] ?? null;
        $this->originalHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'front.example';

        $this->mailTransport = $this->createMock(MailTransportInterface::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->userDetailsHolder = new UserDetailsHolder();

        $this->service = new Communication(
            $this->mailTransport,
            $this->urlHelper,
            $this->userDetailsHolder,
            $this->createMock(LoggerInterface::class),
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

    public function testSendRegistrationCompleteEmailWithoutPaymentButWithPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ],
            ]),
            'payment' => new Payment([
                 'reducedFeeLowIncome' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => true,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithoutPaymentNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
            ]),
            'payment' => new Payment([
                'reducedFeeLowIncome' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => false,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithoutPaymentReceivesBenefitsButWithPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ],
            ]),
            'payment' => new Payment([
                 'reducedFeeReceivesBenefits' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => true,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithoutPaymentReceivesBenefitsNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
            ]),
            'payment' => new Payment([
                'reducedFeeReceivesBenefits' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => false,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithoutPaymentAwardedDamagesButWithPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ],
            ]),
            'payment' => new Payment([
                 'reducedFeeAwardedDamages' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => true,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithoutPaymentAwardedDamagesNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
            ]),
            'payment' => new Payment([
                'reducedFeeAwardedDamages' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => false,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithoutPaymentUniversalCreditButWithPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ],
            ]),
            'payment' => new Payment([
                 'reducedFeeUniversalCredit' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => true,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithoutPaymentUniversalCreditNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ],
            ]),
            'payment' => new Payment([
                'reducedFeeUniversalCredit' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTN' => false,
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithOnlinePaymentAndPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ]
            ]),
            'payment' => new Payment([
                'amount' => '200000.00',
                'email' => new EmailAddress(['address' => 'paymentfrom@email.com']),
                'reference' => '12345678',
                'date' => new DateTime('2021-09-24 07:54:00'),
            ]),
        ]);

        // formatMoney(200000.0) = '200,000' (whole number, no decimal places)
        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000',
                'PTNOnly' => true,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'date' => '5 November 2021',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithOnlinePaymentNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ]
            ]),
            'payment' => new Payment([
                'amount' => '200000.00',
                'email' => new EmailAddress(['address' => 'paymentfrom@email.com']),
                'reference' => '12345678',
                'date' => new DateTime('2021-09-24 07:54:00'),
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'date' => '5 November 2021',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithReducedOnlinePaymentAndPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ]
            ]),
            'payment' => new Payment([
                'amount' => '200000.00',
                'email' => new EmailAddress(['address' => 'paymentfrom@email.com']),
                'reference' => '12345678',
                'date' => new DateTime('2021-09-24 07:54:00'),
                'reducedFeeLowIncome' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => true,
                'remission' => true,
                'date' => '5 November 2021',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithReducedOnlinePaymentNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ]
            ]),
            'payment' => new Payment([
                'amount' => '200000.00',
                'email' => new EmailAddress(['address' => 'paymentfrom@email.com']),
                'reference' => '12345678',
                'date' => new DateTime('2021-09-24 07:54:00'),
                'reducedFeeLowIncome' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000',
                'PTNOnly' => false,
                'FeeFormOnly' => true,
                'FeeFormPTN' => false,
                'remission' => true,
                'date' => '5 November 2021',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithChequePaymentAndPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ]
            ]),
            'payment' => new Payment([
                'method' => 'cheque',
                'amount' => '110.00',
            ]),
        ]);

        // formatMoney(110.0) = '110' (whole number, no decimal places)
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTNOnly' => true,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'feeAmount' => '110',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithChequePaymentNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ]
            ]),
            'payment' => new Payment([
                'method' => 'cheque',
                'amount' => '110.00',
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'feeAmount' => '110',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithReducedChequePaymentAndPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ],
                'peopleToNotify' => [
                   new NotifiedPerson([
                    "name" => [
                        "title" => "Miss",
                        "first" => "Elizabeth",
                        "last" => "Stout",
                    ],
                   ]),
                ],
            ]),
            'payment' => new Payment([
                'method' => 'cheque',
                'amount' => '110.00',
                'reducedFeeLowIncome' => true,
            ]),
        ]);

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => true,
                'remission' => true,
                'feeAmount' => '110',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailWithReducedChequePaymentNoPersonToNotify(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW,
                'donor' => [
                    'name' => new LongName('{"title":"Father", "first":"Spodo", "last":"Komodo"}')
                ]
            ]),
            'payment' => new Payment([
                'method' => 'cheque',
                'amount' => '41.00',
                'reducedFeeLowIncome' => true,
            ]),
        ]);

        // formatMoney(41.0) = '41' (whole number, no decimal places)
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/lpa/123/lpa/view-docs',
                'checkDatesUrl' => 'https://front.example/lpa/123/lpa/date-check',
                'PTNOnly' => false,
                'FeeFormOnly' => true,
                'FeeFormPTN' => false,
                'remission' => true,
                'feeAmount' => '41',
            ]
        );

        $this->setupEmailParamsExpectations();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
        $this->assertMailParamsEqual($expectedMailParams);
    }

    public function testSendRegistrationCompleteEmailSendFails(): void
    {
        $lpa = new Lpa([
            'id' => 123,
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW
            ]),
            'payment' => new Payment([
                 'reducedFeeLowIncome' => true,
            ]),
        ]);


        // Sending the email throws an exception
        $this->mailTransport->method('send')
            ->willThrowException(new InvalidArgumentException());

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        // Should see the exception converted into failure message
        $this->assertEquals('failed-sending-email', $result);
    }

    private function setupEmailParamsExpectations(): void
    {
        $this->capturedParams = null;
        $this->mailTransport
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (MailParameters $actual) {
                $this->capturedParams = $actual;

                return true;
            });
    }

    private function assertMailParamsEqual(MailParameters $actual): void
    {
        $capturedData = $this->capturedParams->getData();
        ksort($capturedData);
        $actualData = $actual->getData();
        ksort($actualData);

        $this->assertSame($this->capturedParams->getToAddresses(), $actual->getToAddresses());
        $this->assertSame($this->capturedParams->getTemplateRef(), $actual->getTemplateRef());
        $this->assertSame($capturedData, $actualData);
    }
}
