<?php

namespace Application\Model\Service\Mail\Transport;

use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mail\Transport\TransportInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SendGrid as SendGridClient;
use RuntimeException;

class SendGridFactory implements FactoryInterface
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
        $config = $serviceLocator->get('Config');
        $sendGridConfig = $config['email']['sendgrid'];

        if (!isset($sendGridConfig['user']) || !isset($sendGridConfig['key'])) {
            throw new RuntimeException('Sendgrid settings not found');
        }

        //  Inject the SendGrid client and the logger into the service
        $client = new SendGridClient($sendGridConfig['user'], $sendGridConfig['key']);

        return new SendGrid($client, $this->getLogger());
    }
}
