<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service;

use Application\Model\Service\Mail\Transport\MailTransportInterface;
use Hamcrest\MatcherAssert;
use Hamcrest\Matchers;
use Laminas\View\HelperPluginManager;
use Mockery;
use Mockery\MockInterface;

class AbstractEmailServiceTest extends AbstractServiceTest
{
    protected array $config;
    protected HelperPluginManager|MockInterface $helperPluginManager;
    protected MailTransportInterface $mailTransport;

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

    public function testPluginProxyMethods(): void
    {
        // Test methods which proxy onto HelperPluginManager view helpers
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            $this->config,
            $this->mailTransport,
            $this->helperPluginManager
        );

        // Return a url() function with expectations about which arguments
        // it should be passed to stub out the real view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function ($name, $params, $options): string {
                MatcherAssert::assertThat($name, Matchers::equalTo('/a/route'));
                MatcherAssert::assertThat($params, Matchers::equalTo(['token' => 'foo']));
                MatcherAssert::assertThat($options, Matchers::equalTo(['force_canonical' => true]));
                return 'https://some.url/';
            });

        $result = $service->url('/a/route', ['token' => 'foo'], ['force_canonical' => true]);
        $this->assertEquals('https://some.url/', $result);

        $result = $service->moneyFormat('20000000000');
        $this->assertEquals('20,000,000,000', $result);
    }
}
