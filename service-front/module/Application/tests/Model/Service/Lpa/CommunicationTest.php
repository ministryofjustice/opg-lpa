<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use DateTime;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class CommunicationTest extends AbstractEmailServiceTest
{
    /**
     * @var $service Communication
     */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = Mockery::mock(
            Communication::class,
            [
                $this->authenticationService,
                $this->config,
                $this->mailTransport,
                $this->helperPluginManager,
            ]
        )->makePartial();
        $logger = Mockery::spy(LoggerInterface::class);

        $user = (object)['email' => (object)['address' => 'test@email.com']];
        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionUtility->shouldReceive('getFromMvc')
            ->withArgs([ContainerNamespace::USER_DETAILS, 'user'])
            ->andReturn($user)
            ->byDefault();
        $this->service->setSessionUtility($sessionUtility);
        $this->service->setLogger($logger);
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
            'payment' => new Payment([
                 'reducedFeeLowIncome' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
           'payment' => new Payment([
                'reducedFeeLowIncome' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
            'payment' => new Payment([
                 'reducedFeeReceivesBenefits' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
           'payment' => new Payment([
                'reducedFeeReceivesBenefits' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
            'payment' => new Payment([
                 'reducedFeeAwardedDamages' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
           'payment' => new Payment([
                'reducedFeeAwardedDamages' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
            'payment' => new Payment([
                 'reducedFeeUniversalCredit' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => true,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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
         // note that the system represents no payment, by having a payment object with the reason for no payment set within it
           'payment' => new Payment([
                'reducedFeeUniversalCredit' => true,
            ]),
        ]);

        // The service is partially mocked so we don't have to mess
        // about with expectations on the HelperPluginManager;
        // the formatLpaId() and url() methods on the service are just
        // proxies through the methods on that plugin manager anyway.
        $this->service->shouldReceive('formatLpaId')
            ->with($lpa->id)
            ->andReturn('A111 111 1111');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/view-docs',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://view.docs.url');

        $this->service->shouldReceive('url')
            ->with(
                'lpa/date-check',
                ['lpa-id' => $lpa->id],
                ['force_canonical' => true],
            )
            ->andReturn('https://check.dates.url');

        // What we expect to pass to the mail transport
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
                'PTN' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        // We are testing moneyFormat()
        $this->service->shouldReceive('moneyFormat')
            ->with('200000.00')
            ->andReturn('200,000.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000.00',
                'PTNOnly' => true,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'date' => '5 November 2021',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        // We are testing moneyFormat()
        $this->service->shouldReceive('moneyFormat')
            ->with('200000.00')
            ->andReturn('200,000.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000.00',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'date' => '5 November 2021',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        // We are testing moneyFormat()
        $this->service->shouldReceive('moneyFormat')
            ->with('200000.00')
            ->andReturn('200,000.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000.00',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => true,
                'remission' => true,
                'date' => '5 November 2021',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        // We are testing moneyFormat()
        $this->service->shouldReceive('moneyFormat')
            ->with('200000.00')
            ->andReturn('200,000.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com', 'paymentfrom@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'lpaTypeTitleCase' => 'Health and welfare',
                'lpaPaymentReference' => '12345678',
                'lpaPaymentDate' => '24 September 2021 - 8:54am',
                'paymentAmount' => '200,000.00',
                'PTNOnly' => false,
                'FeeFormOnly' => true,
                'FeeFormPTN' => false,
                'remission' => true,
                'date' => '5 November 2021',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
                      ->andReturn('https://some.url');

        $this->service->shouldReceive('moneyFormat')
            ->with('110.00')
            ->andReturn('110.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => true,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'feeAmount' => '110.00',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        $this->service->shouldReceive('moneyFormat')
            ->with('110.00')
            ->andReturn('110.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => false,
                'remission' => false,
                'feeAmount' => '110.00',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        $this->service->shouldReceive('moneyFormat')
            ->with('110.00')
            ->andReturn('110.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => false,
                'FeeFormOnly' => false,
                'FeeFormPTN' => true,
                'remission' => true,
                'feeAmount' => '110.00',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        $this->service->shouldReceive('moneyFormat')
            ->with('41.00')
            ->andReturn('41.00');

        // Expected data passed to send()
        $expectedMailParams = new MailParameters(
            ['test@email.com'],
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2,
            [
                'donorName' => 'Father Spodo Komodo',
                'lpaType' => 'health and welfare',
                'lpaId' => 'A22222222',
                'viewDocsUrl' => 'https://some.url',
                'checkDatesUrl' => 'https://some.url',
                'PTNOnly' => false,
                'FeeFormOnly' => true,
                'FeeFormPTN' => false,
                'remission' => true,
                'feeAmount' => '41.00',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams): true {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
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

        // We're not testing the URLs or LPA ID formatting in this case
        $this->service->shouldReceive('formatLpaId')
            ->andReturn('A22222222');
        $this->service->shouldReceive('url')
            ->andReturn('https://some.url');

        // Sending the email throws an exception
        $this->mailTransport->shouldReceive('send')
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        // Should see the exception converted into failure message
        $this->assertEquals('failed-sending-email', $result);
    }
}
