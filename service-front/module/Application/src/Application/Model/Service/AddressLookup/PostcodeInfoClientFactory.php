<?php
namespace Application\Model\Service\AddressLookup;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use MinistryOfJustice\PostcodeInfo\Client as PostcodeInfoClient;

class PostcodeInfoClientFactory implements FactoryInterface {

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
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['address']['postcode_info'];

        return new PostcodeInfoClient([
            'httpClient' => $container->get('HttpClient'),
            'apiKey' => $config['token'],
            'baseUrl' => (isset($config['uri'])) ? rtrim($config['uri'], '/') : null
        ]);
    }
} // class
