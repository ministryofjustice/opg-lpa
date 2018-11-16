<?php

namespace Application\Model\Service;

use Application\Model\Service\Analytics\GoogleAnalyticsService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Lpa\Applicant;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\System\Status;
use Application\Model\Service\User\Details;
use Exception;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class ServiceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        Applicant::class => [
            'setLpaApplicationService' => 'LpaApplicationService',
        ],
        Communication::class => [
            'setUserDetailsSession' => 'UserDetailsSession'
        ],
        Details::class => [
            'setUserDetailsSession' => 'UserDetailsSession'
        ],
        GoogleAnalyticsService::class => [
            'setAnalyticsClient' => 'AnalyticsClient'
        ],
        Metadata::class => [
            'setLpaApplicationService' => 'LpaApplicationService',
        ],
        ReplacementAttorneyCleanup::class => [
            'setLpaApplicationService' => 'LpaApplicationService',
        ],
        Status::class => [
            'setApiClient' => 'ApiClient',
        ],
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName) && is_subclass_of($requestedName, AbstractService::class);
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws Exception if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        $serviceName = $requestedName;

        $authenticationService = $container->get('AuthenticationService');
        $config = $container->get('Config');

        if (is_subclass_of($serviceName, AbstractEmailService::class)) {
            $service = new $serviceName(
                $authenticationService,
                $config,
                $container->get('TwigEmailRenderer'),
                $container->get('MailTransport')
            );
        } else {
            $service = new $serviceName(
                $authenticationService,
                $config
            );
        }

        //  Inject the API and/or Auth clients if necessary
        if ($service instanceof ApiClientAwareInterface) {
            /** @var ApiClient $apiClient */
            $apiClient = $container->get('ApiClient');
            $service->setApiClient($apiClient);
        }

        //  If required load any additional services into the resource
        if (array_key_exists($serviceName, $this->additionalServices)
            && is_array($this->additionalServices[$serviceName])) {
            foreach ($this->additionalServices[$serviceName] as $setterMethod => $additionalService) {
                if (!method_exists($service, $setterMethod)) {
                    throw new Exception(sprintf(
                        'The setter method %s does not exist on the requested resource %s',
                        $setterMethod,
                        $serviceName
                    ));
                }

                $service->$setterMethod($container->get($additionalService));
            }
        }

        return $service;
    }
}
