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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

final class CommunicationTest extends MockeryTestCase
{
    private Communication $service;
    private MailTransportInterface|MockInterface $mailTransport;
    private UrlHelper|MockInterface $urlHelper;
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

        $this->mailTransport = Mockery::mock(MailTransportInterface::class);
        $this->urlHelper = Mockery::mock(UrlHelper::class);
        $this->userDetailsHolder = new UserDetailsHolder();

        $this->service = new Communication(
            $this->mailTransport,
            $this->urlHelper,
            $this->userDetailsHolder,
            Mockery::spy(LoggerInterface::class),
        );

        $user = new User(['email' => ['address' => 'test@email.com']]);
        $this->userDetailsHolder->set($user);

        // Default URL response — individual tests override with specific expectations where needed
        $this->urlHelper->shouldReceive('generate')->andReturn('/some/path')->byDefault();
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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

        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/view-docs', ['lpa-id' => $lpa->id])
            ->andReturn('/view-the-docs');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('/check-the-dates');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://front.example/view-the-docs',
                'checkDatesUrl' => 'https://front.example/check-the-dates',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
                'viewDocsUrl' => 'https://front.example/some/path',
                'checkDatesUrl' => 'https://front.example/some/path',
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
        $this->mailTransport->shouldReceive('send')
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        // Should see the exception converted into failure message
        $this->assertEquals('failed-sending-email', $result);
    }

    private function setupEmailParamsExpectations(): void
    {
        $this->capturedParams = null;
        $this->mailTransport
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (MailParameters $actual) {
                $this->capturedParams = $actual;

                return true;
            }));
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
