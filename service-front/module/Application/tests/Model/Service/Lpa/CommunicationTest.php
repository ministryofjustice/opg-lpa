<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Mail\MailParameters;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
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

        $this->service = new Communication(
            $this->authenticationService,
            $this->config,
            $this->mailTransport
        );
    }

    public function testSendRegistrationCompleteEmailNoPaymentEmail(): void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => '20.00'
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW
            ])
        ]);

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_LPA_REGISTRATION,
            [
                'lpa' => $lpa,
                'paymentAmount' => '20.00',
                'isHealthAndWelfare' => true
            ]
        );

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailInvalidPaymentAmount(): void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => 'no'
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW
            ])
        ]);

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_LPA_REGISTRATION,
            [
                'lpa' => $lpa,
                'paymentAmount' => null,
                'isHealthAndWelfare' => true
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailNotHealthAndWelfare(): void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => '20.00'
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF
            ])
        ]);

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_LPA_REGISTRATION,
            [
                'lpa' => $lpa,
                'paymentAmount' => '20.00',
                'isHealthAndWelfare' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailException(): void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => '20.00'
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF
            ])
        ]);

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_LPA_REGISTRATION,
            [
                'lpa' => $lpa,
                'paymentAmount' => '20.00',
                'isHealthAndWelfare' => false,
            ]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once()
            ->andThrow(new InvalidArgumentException('Test exception'));

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testSendRegistrationCompleteWithPaymentEmail(): void
    {
        $expectedAddresses = ['payment@email.com', 'test@email.com'];

        $lpa = new Lpa([
           'payment' => new Payment([
                'amount' => '20.00',
                'email' => new EmailAddress(['address' => 'payment@email.com'])
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW
            ])
        ]);

        // Test is specifically for multiple email addresses, so expectations are only
        // set for those, not for the whole $mailParameters object
        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($mailParameters) use ($expectedAddresses) {
                // Check both emails are in the mail parameters
                MatcherAssert::assertThat(
                    $expectedAddresses,
                    Matchers::arrayContainingInAnyOrder($mailParameters->getToAddresses())
                );

                return true;
            }))
            ->once();

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }
}
