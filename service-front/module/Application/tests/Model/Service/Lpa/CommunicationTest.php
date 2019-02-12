<?php

namespace ApplicationTest\Model\Service\Lpa;

use Application\Model\Service\Lpa\Communication;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use Exception;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Zend\Session\Container;

class CommunicationTest extends AbstractEmailServiceTest
{
    /**
     * @var $service Communication
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->service = new Communication(
            $this->authenticationService,
            [],
            $this->twigEmailRenderer,
            $this->mailTransport
        );
    }

    public function testSendRegistrationCompleteEmail() : void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => '20.00',
                'email' => new EmailAddress(['address' => 'payment@email.com'])
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW
            ])
        ]);

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                ['test@email.com', 'payment@email.com'],
                'email-lpa-registration',
                ['lpa' => $lpa, 'paymentAmount' => '20.00', 'isHealthAndWelfare' => true]
            ])->once();

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailNoPaymentEmail() : void
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

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                ['test@email.com'],
                'email-lpa-registration',
                ['lpa' => $lpa, 'paymentAmount' => '20.00', 'isHealthAndWelfare' => true]
            ])->once();

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailInvalidPaymentAmount() : void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => 'no'
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_HW
            ])
        ]);

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                ['test@email.com'],
                'email-lpa-registration',
                ['lpa' => $lpa, 'paymentAmount' => null, 'isHealthAndWelfare' => true]
            ])->once();

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailNotHealthAndWelfare() : void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => '20.00'
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF
            ])
        ]);

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');
        $this->service->setUserDetailsSession($userDetailSession);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                ['test@email.com'],
                'email-lpa-registration',
                ['lpa' => $lpa, 'paymentAmount' => '20.00', 'isHealthAndWelfare' => false]
            ])->once();

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertTrue($result);
    }

    public function testSendRegistrationCompleteEmailException() : void
    {
        $lpa = new Lpa([
            'payment' => new Payment([
                'amount' => '20.00'
            ]),
            'document' => new Document([
                'type' => Document::LPA_TYPE_PF
            ])
        ]);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                ['test@email.com'],
                'email-lpa-registration',
                ['lpa' => $lpa, 'paymentAmount' => '20.00', 'isHealthAndWelfare' => false]
            ])->once()
            ->andThrow(new Exception('Test exception'));

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $result = $this->service->sendRegistrationCompleteEmail($lpa);

        $this->assertEquals('failed-sending-email', $result);
    }
}
