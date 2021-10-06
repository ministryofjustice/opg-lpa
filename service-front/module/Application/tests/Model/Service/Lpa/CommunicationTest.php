<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Mail\MailParameters;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use DateTime;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Laminas\Mail\Exception\ExceptionInterface;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Session\Container;

class CommunicationTest extends AbstractEmailServiceTest
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

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');
        $this->service->setUserDetailsSession($userDetailSession);
    }

    public function testSendRegistrationCompleteEmailWithoutPayment(): void
    {
        $lpa = new Lpa([
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF,
                'donor' => [
                    'name' => new LongName('{"title":"Dr", "first":"Pete", "last":"Vamoose"}')
                ]
            ])
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
            AbstractEmailService::EMAIL_LPA_REGISTRATION,
            [
                'donorName' => 'Dr Pete Vamoose',
                'lpaType' => 'property and financial affairs',
                'lpaId' => 'A111 111 1111',
                'viewDocsUrl' => 'https://view.docs.url',
                'checkDatesUrl' => 'https://check.dates.url',
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParams));

        // Call test method
        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailWithPayment(): void
    {
        $lpa = new Lpa([
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
            AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT,
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
            ]
        );

        $this->mailTransport->shouldReceive('send')
            //->with(Matchers::equalTo($expectedMailParams));
            ->with(Mockery::on(function ($actualMailParams) use ($expectedMailParams) {
                MatcherAssert::assertThat($expectedMailParams, Matchers::equalTo($actualMailParams));
                return true;
            }));

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailSendFails(): void
    {
        $lpa = new Lpa([
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW
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
