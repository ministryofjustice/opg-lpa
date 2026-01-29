<?php

namespace ApplicationTest\Model\Service;

use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use PHPUnit\Framework\Attributes\DataProvider;
use Alphagov\Notifications\Client;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\ServiceAbstractFactory;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\Password\Service as PasswordService;
use Application\Model\Service\Pdfs\Service as PdfsService;
use Application\Model\Service\Seed\Service as SeedService;
use Application\Model\Service\Users\Service as UsersService;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Psr\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;

class ServiceAbstractFactoryTest extends MockeryTestCase
{
    /**
     * @var $container ContainerInterface|MockInterface
     */
    private $container;

    /**
     * @var $factory ServiceAbstractFactory
     */
    private $factory;

    public function setUp(): void
    {
        $this->container = Mockery::mock(ContainerInterface::class);

        $this->factory = new ServiceAbstractFactory();
    }

    /**
     * @return array service to try to create and an array of the dependencies that will be provided by the mock Container
     */
    public static function servicesProvider(): array
    {
        return [
            'AccountCleanupService' => [AccountCleanupService::class,
                [
                    UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
                    ApplicationRepositoryInterface::class =>  Mockery::mock(ApplicationRepositoryInterface::class),
                    'NotifyClient' => Mockery::mock(Client::class),
                    'config' => [],
                    UsersService::class =>  Mockery::mock(UsersService::class),
                    'Logger' => Mockery::mock(LoggerInterface::class),
                ]
            ],
            'PasswordService' => [PasswordService::class,
                [
                    UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
                    AuthenticationService::class => Mockery::mock(AuthenticationService::class),
                    'config' => ['authentication_tokens' => ['use_hash_tokens' => false]],
                ]
            ],
            'PdfsService' => [PdfsService::class,
                [
                    ApplicationRepositoryInterface::class =>  Mockery::mock(ApplicationRepositoryInterface::class),
                    'config' => [],
                    'S3Client' => Mockery::mock(S3Client::class),
                    'SqsClient' => Mockery::mock(SqsClient::class),
                    'Logger' => Mockery::mock(LoggerInterface::class),
                ]
            ],
            'SeedService' => [SeedService::class,
                [
                    ApplicationRepositoryInterface::class =>  Mockery::mock(ApplicationRepositoryInterface::class),
                    ApplicationsService::class => Mockery::mock(ApplicationsService::class),
                    'Logger' => Mockery::mock(LoggerInterface::class),
                ]
            ],
            'UsersService' => [UsersService::class,
                [
                    LogRepositoryInterface::class => Mockery::mock(LogRepositoryInterface::class),
                    UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
                    ApplicationRepositoryInterface::class => Mockery::mock(ApplicationRepositoryInterface::class),
                    ApplicationsService::class => Mockery::mock(ApplicationsService::class),
                    'config' => ['authentication_tokens' => ['use_hash_tokens' => false]],
                ]
            ],
            'ProcessingStatusService' => [ProcessingStatusService::class,
                [
                    GuzzleHttpClient::class => Mockery::mock(GuzzleHttpClient::class),
                    'config' => ['processing-status' => ['endpoint' => 'test endpoint']],
                    'AwsCredentials' => Mockery::mock(CredentialsInterface::class),
                    'AwsApiGatewaySignature' => Mockery::mock(SignatureV4::class),
                    'Logger' => Mockery::mock(LoggerInterface::class),
                ]
            ]
        ];
    }

    /**
     * @param $service string Service class to check
     */
    #[DataProvider('servicesProvider')]
    public function testCanCreate($service)
    {
        $result = $this->factory->canCreate($this->container, $service);

        $this->assertTrue($result);
    }

    /**
     * @param $service string Service class to check
     * @param $dependancies array Dependencies that the mock container will provide
     * @throws ApiProblemException
     */
    #[DataProvider('servicesProvider')]
    public function testInvoke($service, $dependancies)
    {
        foreach ($dependancies as $key => $value) {
            $this->container->shouldReceive('get')->withArgs([$key])->once()->andReturn($value);
        }

        $this->factory->__invoke($this->container, $service);
    }
}
