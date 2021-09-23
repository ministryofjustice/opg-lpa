<?php

namespace ApplicationTest\Model\Service\Mail;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\MessageFactory;
use Application\View\Helper\LocalViewRenderer;
use Laminas\Mail\Exception\InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class MessageFactoryTest extends MockeryTestCase
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var LocalViewRenderer
     */
    private $localViewRenderer;

    public function setUp(): void
    {
        $this->localViewRenderer = Mockery::Mock(LocalViewRenderer::class);

        $this->config = [
            'email' => [
                'sender' => [
                    'default' => [
                        'name' => 'Eeee Vooo',
                        'address' => 'eeeevooo@test.com'
                    ],

                    'feedback' => [
                        'name' => 'Feed Back',
                        'address' => 'feedback@test.com'
                    ]
                ]
            ]
        ];

        $this->messageFactory = new MessageFactory(
            $this->config,
            $this->localViewRenderer
        );
    }

    public function testCreateMessageIllegalTemplateRef(): void
    {
        $mailParameters = new MailParameters('to@test.com', 'foobar');
        $this->expectException(InvalidArgumentException::class);
        $this->messageFactory->createMessage($mailParameters);
    }

    public function testCreateMessageNoTemplateRef(): void
    {
        $mailParameters = new MailParameters('to@test.com', null);
        $this->expectException(InvalidArgumentException::class);
        $this->messageFactory->createMessage($mailParameters);
    }

    public function testCreateMessageBadTemplateRefConfig(): void
    {
        $mailParameters = new MailParameters(
            'to@test.com',
            AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE
        );

        $messageFactory = new MessageFactory(
            [],
            $this->localViewRenderer,
            // template reference exists, but has no template property
            [AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE => []]
        );

        $this->expectException(InvalidArgumentException::class);
        $messageFactory->createMessage($mailParameters);
    }

    public function testCreateMessageTemplateProvidesNoSubject(): void
    {
        $mailParameters = new MailParameters(
            'to@test.com',
            AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE
        );

        // the template we're rendering doesn't produce the magic
        // <!-- SUBJECT: (.*?) --> substring used to populate
        // the subject
        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('registration.twig', [])
            ->andReturn('foo');

        $this->expectException(InvalidArgumentException::class);
        $this->messageFactory->createMessage($mailParameters);
    }

    public function testCreateMessageEverythingIsFine(): void
    {
        $expectedTos = ['to@test.com', 'fro@test.com'];
        $expectedHtml = '<!-- SUBJECT: Feedback thanks --><p>Some other content here</p>';

        $mailParameters = new MailParameters(
            $expectedTos,
            AbstractEmailService::EMAIL_FEEDBACK
        );

        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('feedback.twig', [])
            ->andReturn($expectedHtml);

        $message = $this->messageFactory->createMessage($mailParameters);

        // check the message has been constructed how we expect
        $this->assertEquals('Feedback thanks', $message->getSubject());

        $actualTos = array_map(function ($to) {
            return $to->getEmail();
        }, iterator_to_array($message->getTo(), false));
        $this->assertEquals($expectedTos, $actualTos);

        $this->assertEquals($expectedHtml, $message->getBody()->getParts()[0]->getContent());
    }
}
