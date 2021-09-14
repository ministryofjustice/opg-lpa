<?php

namespace ApplicationTest\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Application\View\Helper\LocalViewRenderer;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Transport\TransportInterface;
use Mockery;
use Mockery\MockInterface;

class AbstractEmailServiceTest extends AbstractServiceTest
{
    /**
     * @var $localViewRenderer LocalViewRenderer|MockInterface
     */
    protected $localViewRenderer;

    /**
     * @var $mailTransport TransportInterface|MockInterface
     */
    protected $mailTransport;

    public function setUp(): void
    {
        parent::setUp();

        $this->localViewRenderer = Mockery::mock(LocalViewRenderer::class);

        $this->mailTransport = Mockery::mock(TransportInterface::class);

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
    }

    public function testConstructor(): void
    {
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            $this->config,
            $this->localViewRenderer,
            $this->mailTransport
        );

        $this->assertEquals($this->authenticationService, $service->getAuthenticationService());
        $this->assertEquals($this->config, $service->getConfig());
        $this->assertEquals($this->mailTransport, $service->getMailTransport());
    }

    public function testCreateMessageIllegalTemplateRef(): void
    {
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            $this->config,
            $this->localViewRenderer,
            $this->mailTransport
        );

        $this->expectException(InvalidArgumentException::class);
        $service->createMessage('to@test.com', null);
    }

    public function testCreateMessageBadTemplate(): void
    {
        // Partial mock, so we can return a bad template from getTemplate()
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            $this->config,
            $this->localViewRenderer,
            $this->mailTransport
        );

        $this->expectException(InvalidArgumentException::class);
        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('feedback.twig', [])
            ->andReturn('bad html');

        $service->createMessage('to@test.com', 'email-feedback');
    }
}
