<?php
namespace Application\ControllerFactory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Stdlib\DispatchableInterface as Dispatchable;

/**
 * A factory is used in order to determine if the user will be sent to WorldPay, or GovPay.
 *
 * Class PaymentControllerFactory
 * @package Application\ControllerFactory
 */
class PaymentControllerFactory implements FactoryInterface {

    public function createService( ServiceLocatorInterface $locator ){

        $segmentsSentToGovPay = 84;

        //---

        $auth = $locator->getServiceLocator()->get('AuthenticationService');

        if ( !$auth->hasIdentity() ){

            // If no auth (should never happen, but as a failsafe).
            $controller = new \Application\Controller\Authenticated\Lpa\WorldpayPaymentController;

        } else {

            $userId = $auth->getIdentity()->id();

            $segment = (abs(crc32($userId)) % 100) + 1;

            if( $segment <= $segmentsSentToGovPay ){

                $controller = new \Application\Controller\Authenticated\Lpa\GovPayPaymentController;

            } else {

                $controller = new \Application\Controller\Authenticated\Lpa\WorldpayPaymentController;

            }

        }

        //---

        // Ensure it's Dispatchable...
        if( !( $controller instanceof Dispatchable ) ){
            throw new \RuntimeException( 'Requested controller class is not Dispatchable' );
        }

        return $controller;

    }

}
