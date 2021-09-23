<?php

namespace ApplicationTest\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Application\Model\Service\Mail\Transport\MailTransportInterface;
use Application\View\Helper\LocalViewRenderer;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\View\HelperPluginManager;
use Mockery;
use Mockery\MockInterface;

class AbstractEmailServiceTest extends AbstractServiceTest
{
    /**
     * @var $mailTransport MailTransportInterface
     */
    protected $mailTransport;

    public function setUp(): void
    {
        parent::setUp();

        $this->mailTransport = Mockery::mock(MailTransportInterface::class);

        $this->config = [
            'email' => [
                'sender' => [
                    'default' => [
                        'address' => 'default_sender@uat.digital.justice.gov.uk',
                        'name' => 'DefaultSender'
                    ],
                    'feedback' => [
                        'address' => 'DoctorFeedback@uat.digital.justice.gov.uk',
                        'name' => 'DoctorFeedback'
                    ]
                ]
            ],
            'sendFeedbackEmailTo' => 'FeedbackReceiver@uat.digital.justice.gov.uk'
        ];

        $this->helperPluginManager = Mockery::mock(HelperPluginManager::class);
    }

    public function testConstructor(): void
    {
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            $this->config,
            $this->mailTransport,
            $this->helperPluginManager
        );

        $this->assertEquals($this->authenticationService, $service->getAuthenticationService());
        $this->assertEquals($this->config, $service->getConfig());
        $this->assertEquals($this->mailTransport, $service->getMailTransport());
    }
}
