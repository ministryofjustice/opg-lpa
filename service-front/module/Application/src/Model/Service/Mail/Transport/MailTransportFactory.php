<?php

namespace Application\Model\Service\Mail\Transport;

use Alphagov\Notifications\Client as NotifyClient;
use Application\Model\Service\ApiClient\Guzzle6Wrapper;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Twig\Environment as TwigEnvironment;
use RuntimeException;

/**
 * Factory for mail transport, which instantiates objects for
 * sending emails via a variety of external systems.
 */
class MailTransportFactory implements FactoryInterface
{
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
        $emailConfig = $container->get('Config')['email'];

        $transport = null;
        if (isset($emailConfig['transport'])) {
            $transport = $emailConfig['transport'];
        }

        if ($transport === 'notify') {
            if (!isset($emailConfig['notify']['key'])) {
                throw new RuntimeException('Notify API settings not found');
            }

            $notifyClient = new NotifyClient([
                'apiKey' => $emailConfig['notify']['key'],
                'httpClient' => new Guzzle6Wrapper()
            ]);

            return new NotifyMailTransport($notifyClient);
        }

        throw new RuntimeException('Unable to instantiate email transport; transport is set to ' . $transport);
    }
}
