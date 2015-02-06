<?php
namespace Application\Model\Service\Mail\Transport;

use RuntimeException;

use SendGrid as SendGridClient;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Mail\Transport\TransportInterface;

class SendGridFactory implements FactoryInterface {

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return TransportInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator){

        $config = $serviceLocator->get('Config');

        if( !isset($config['email']['sendgrid']['user']) || !isset($config['email']['sendgrid']['key']) ){
            throw new RuntimeException('Sendgrid settings not found ');
        }

        $config = $config['email']['sendgrid'];

        //---

        $client = new SendGridClient( $config['user'], $config['key'] );

        return new SendGrid( $client );

    }

} // class
