<?php

namespace Application\Model\Service\Mail\Transport;

use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mail\Transport\TransportInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SendGrid as SendGridClient;
use Twig_Environment;
use RuntimeException;

class MailTransportFactory implements FactoryInterface
{
    use LoggerTrait;

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return TransportInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $emailConfig = $serviceLocator->get('Config')['email'];
        $sendGridConfig = $emailConfig['sendgrid'];

        if (!isset($sendGridConfig['user']) || !isset($sendGridConfig['key'])) {
            throw new RuntimeException('Sendgrid settings not found');
        }

        $client = new SendGridClient($sendGridConfig['user'], $sendGridConfig['key']);

        /** @var Twig_Environment $emailRenderer */
        $emailRenderer = $serviceLocator->get('TwigEmailRenderer');

        return new MailTransport($client, $emailRenderer, $this->getLogger(), $emailConfig);
    }
}
