<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

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
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class CommunicationTest extends MockeryTestCase
{
    private Communication $service;
    private MailTransportInterface|MockInterface $mailTransport;
    // PHPUnit createMock used because Mockery cannot generate a class with method named 'unset' (reserved keyword)
    private SessionInterface&MockObject $session;
    private UrlHelper|MockInterface $urlHelper;

    public function setUp(): void
    {
        $this->mailTransport = Mockery::mock(MailTransportInterface::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->urlHelper = Mockery::mock(UrlHelper::class);

        $this->service = new Communication($this->mailTransport);
        $this->service->setSession($this->session);
        $this->service->setUrlHelper($this->urlHelper);
        $this->service->setLogger(Mockery::spy(LoggerInterface::class));

        $user = (object)['email' => (object)['address' => 'test@email.com']];
        $this->session->method('get')->with('user')->willReturn($user);

        // Default URL response — individual tests override with specific expectations where needed
        $this->urlHelper->shouldReceive('generate')->andReturn('https://some.url')->byDefault();
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
            ->andReturn('https://view.docs.url');
        $this->urlHelper->shouldReceive('generate')
            ->with('lpa/date-check', ['lpa-id' => $lpa->id])
            ->andReturn('https://check.dates.url');

        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            Communication::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => Formatter::id($lpa->id),
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with($this->equalTo($expectedMailParams));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
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

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
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

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
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

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
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

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => true,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'feeAmount' => '110',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'feeAmount' => '110',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => true,
                'remission' => true,
                'feeAmount' => '110',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => false,
                'FeeFormOnly' => true,
                'FeeFormPTN' => false,
                'remission' => true,
                'feeAmount' => '41',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                $this->assertEquals($expectedMailParams, $actualMailParams);
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
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
}
