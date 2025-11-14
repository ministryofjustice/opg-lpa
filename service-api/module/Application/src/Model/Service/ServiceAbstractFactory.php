<?php

namespace Application\Model\Service;

use Application\Model\DataAccess\Repository\Application as ApplicationRepository;
use Application\Model\DataAccess\Repository\User as UserRepository;
use Application\Model\DataAccess\Repository\Stats as StatsRepository;
use Application\Model\DataAccess\Repository\Feedback as FeedbackRepository;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\Users\Service as UsersService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Exception;
use MakeShared\Logging\LoggerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Creates a service and injects the required dependencies
 *
 * Class ServiceAbstractFactory
 * @package Application\Model\Service
 * @psalm-api
 */
class ServiceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        AccountCleanup\Service::class => [
            'setConfig'         => 'config',
            'setNotifyClient'   => 'NotifyClient',
            'setUsersService'   => UsersService::class,
        ],
        Feedback\Service::class => [
            'setFeedbackValidator' => 'FeedbackValidator',
        ],
        Password\Service::class => [
            'setAuthenticationService' => AuthenticationService::class,
        ],
        Pdfs\Service::class => [
            'setPdfConfig'         => 'config',
            'setS3Client'          => 'S3Client',
            'setSqsClient'         => 'SqsClient',
        ],
        Seed\Service::class => [
            'setApplicationsService' => ApplicationsService::class,
        ],
        Users\Service::class => [
            'setApplicationsService' => ApplicationsService::class,
        ],
        ProcessingStatus\Service::class => [
            'setClient' => Client::class,
            'setConfig' => 'config',
            'setCredentials' => 'AwsCredentials',
            'setAwsSignatureV4' => 'AwsApiGatewaySignature',
        ]
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return (class_exists($requestedName)
            && is_subclass_of($requestedName, AbstractService::class));
    }

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array|null $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new Exception(
                sprintf(
                    'Abstract factory %s can not create the requested service %s',
                    get_class($this),
                    $requestedName
                )
            );
        }

        // Use custom factory method for AuthenticationService;
        // the AppAuthenticationService factory method is defined in
        // service-api/module/Application/src/Module.php
        if ($requestedName === 'Application\Model\Service\Authentication\Service') {
            $service = $container->get('AppAuthenticationService');
        } else {
            $service = new $requestedName();
        }

        $traitsUsed = class_uses($service);

        //  Inject the required data repositories
        if (is_array($traitsUsed)) {
            if (in_array(UserRepository\LogRepositoryTrait::class, $traitsUsed)) {
                $service->setLogRepository($container->get(UserRepository\LogRepositoryInterface::class));
            }

            if (in_array(UserRepository\UserRepositoryTrait::class, $traitsUsed)) {
                $service->setUserRepository($container->get(UserRepository\UserRepositoryInterface::class));
            }

            if (in_array(ApplicationRepository\WhoRepositoryTrait::class, $traitsUsed)) {
                $service->setWhoRepository($container->get(ApplicationRepository\WhoRepositoryInterface::class));
            }

            if (in_array(StatsRepository\StatsRepositoryTrait::class, $traitsUsed)) {
                $service->setStatsRepository($container->get(StatsRepository\StatsRepositoryInterface::class));
            }

            if (in_array(ApplicationRepository\ApplicationRepositoryTrait::class, $traitsUsed)) {
                $service->setApplicationRepository(
                    $container->get(ApplicationRepository\ApplicationRepositoryInterface::class)
                );
            }

            if (in_array(FeedbackRepository\FeedbackRepositoryTrait::class, $traitsUsed)) {
                $service->setFeedbackRepository(
                    $container->get(FeedbackRepository\FeedbackRepositoryInterface::class)
                );
            }

            if (in_array(LoggerTrait::class, $traitsUsed)) {
                $service->setLogger($container->get('Logger'));
            }

            if (in_array(TokenGenerationTrait::class, $traitsUsed)) {
                $config = $container->get('config');
                $useHashTokens = $config['auth']['use_hash_tokens'];

                $service->setUseHashTokens($useHashTokens);
            }
        }

        //  If required load any additional services into the service
        if (
            array_key_exists($requestedName, $this->additionalServices) &&
            is_array($this->additionalServices[$requestedName])
        ) {
            foreach ($this->additionalServices[$requestedName] as $setterMethod => $additionalService) {
                if (!method_exists($service, $setterMethod)) {
                    throw new Exception(
                        sprintf(
                            'The setter method %s does not exist on the requested service %s',
                            $setterMethod,
                            $requestedName
                        )
                    );
                }

                $service->$setterMethod($container->get($additionalService));
            }
        }

        return $service;
    }
}
