<?php

namespace Application\Model\Service\Mail\Transport;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use SendGrid;
use Twig\Environment as TwigEnvironment;
use RuntimeException;

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

        if ($transport === 'sendgrid') {
            $sendGridConfig = $emailConfig['sendgrid'];

            if (!isset($sendGridConfig['key'])) {
                throw new RuntimeException('Sendgrid settings not found');
            }

            $client = new SendGrid($sendGridConfig['key']);

            return new SendGridMailTransport($client->client);
        }

        throw new RuntimeException('Unable to instantiate email transport; transport is set to ' . $transport);
    }
}
