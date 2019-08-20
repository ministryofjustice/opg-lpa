<?php

namespace ApplicationTest\Model\Service;

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
use Http\Client\HttpClient;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use GuzzleHttp\Client as GuzzleHttpClient;

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

    public function setUp()
    {
        $this->container = Mockery::mock(ContainerInterface::class);

        $this->factory = new ServiceAbstractFactory();
    }

    /**
     * @return array service to try to create and an array of the dependencies that will be provided by the mock Container
     */
    public function services()
    {
        return [
            [AccountCleanupService::class,
                [
                    UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
                    ApplicationRepositoryInterface::class =>  Mockery::mock(ApplicationRepositoryInterface::class),
                    'NotifyClient' => Mockery::mock(Client::class),
                    'config' => [],
                    UsersService::class =>  Mockery::mock(UsersService::class),
                ]
            ],
            [PasswordService::class,
                [
                    UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
                    AuthenticationService::class => Mockery::mock(AuthenticationService::class),
                ]
            ],
            [PdfsService::class,
                [
                    ApplicationRepositoryInterface::class =>  Mockery::mock(ApplicationRepositoryInterface::class),
                    'config' => [],
                    'S3Client' => Mockery::mock(S3Client::class),
                    'SqsClient' => Mockery::mock(SqsClient::class),
                ]
            ],
            [SeedService::class,
                [
                    ApplicationRepositoryInterface::class =>  Mockery::mock(ApplicationRepositoryInterface::class),
                    ApplicationsService::class => Mockery::mock(ApplicationsService::class),
                ]
            ],
            [UsersService::class,
                [
                    LogRepositoryInterface::class => Mockery::mock(LogRepositoryInterface::class),
                    UserRepositoryInterface::class => Mockery::mock(UserRepositoryInterface::class),
                    ApplicationsService::class => Mockery::mock(ApplicationsService::class),
                ]
            ],
            [ProcessingStatusService::class,
                [
                    GuzzleHttpClient::class => Mockery::mock(GuzzleHttpClient::class),
                    'config' => ['processing-status' => ['endpoint' => 'test endpoint']],
                    'AwsApiGatewaySignature' => Mockery::mock(\Aws\Signature\SignatureV4::class),
                ]
            ]
        ];
    }

    /**
     * @dataProvider services
     * @param $service string Service class to check
     */
    public function testCanCreate($service)
    {
        $result = $this->factory->canCreate($this->container, $service);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider services
     * @param $service string Service class to check
     * @param $dependancies array Dependencies that the mock container will provide
     * @throws ApiProblemException
     */
    public function testInvoke($service, $dependancies)
    {
        foreach($dependancies as $key => $value){
            $this->container->shouldReceive('get')->withArgs([$key])->once()->andReturn($value);
        }

        $this->factory->__invoke($this->container, $service);
    }

}
