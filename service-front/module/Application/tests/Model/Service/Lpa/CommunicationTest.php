<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Communication;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Mockery;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Message;
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
            $this->localViewRenderer,
            $this->mailTransport
        );
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

        $data = [
            'lpa' => $lpa,
            'paymentAmount' => '20.00',
            'isHealthAndWelfare' => true,
        ];

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('lpa-registration.twig', $data)
            ->andReturn('<!-- SUBJECT: LPA registration --><p>message content</p>');

        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($email) use ($expectedAddresses) {
                // Should be sent to two email addresses
                $actualAddresses = array_map(function ($toAddress) {
                    return $toAddress->getEmail();
                }, iterator_to_array($email->getTo()));

                MatcherAssert::assertThat(
                    $expectedAddresses,
                    Matchers::arrayContainingInAnyOrder($actualAddresses)
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

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $data = [
            'lpa' => $lpa,
            'paymentAmount' => '20.00',
            'isHealthAndWelfare' => true
        ];

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('lpa-registration.twig', $data)
            ->once()
            ->andReturn('<!-- SUBJECT: LPA registration --><p>message content</p>');

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(Message::class))
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

        $data = [
            'lpa' => $lpa,
            'paymentAmount' => null,
            'isHealthAndWelfare' => true,
        ];

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('lpa-registration.twig', $data)
            ->andReturn('<!-- SUBJECT: LPA registration --><p>message content</p>');

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(Message::class))
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

        $data = [
            'lpa' => $lpa,
            'paymentAmount' => '20.00',
            'isHealthAndWelfare' => false,
        ];

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('lpa-registration.twig', $data)
            ->andReturn('<!-- SUBJECT: LPA registration --><p>message content</p>');

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(Message::class))
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

        $data = [
            'lpa' => $lpa,
            'paymentAmount' => '20.00',
            'isHealthAndWelfare' => false,
        ];

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('lpa-registration.twig', $data)
            ->andReturn('<!-- SUBJECT: LPA registration --><p>message content</p>');

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(Message::class))
            ->once()
            ->andThrow(new InvalidArgumentException('Test exception'));

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertEquals('failed-sending-email', $result);
    }
}
