<?php
namespace Application\ControllerFactory;

use RuntimeException;

use Opg\Lpa\DataModel\Lpa\Lpa;

use Application\Controller\LpaAwareInterface;

use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LpaAwareInitializer implements InitializerInterface {

    /**
     * Inject the current LPA into classes that implement LpaAwareInterface.
     *
     * @param $instance
     * @param ServiceLocatorInterface $controllerManager
     * @return mixed
     */
    public function initialize($instance, ServiceLocatorInterface $controllerManager){

        if( $instance instanceof LpaAwareInterface ){

            $locator = $controllerManager->getServiceLocator();

            //---

            $auth = $locator->get('AuthenticationService');

            // We don't do anything here without a user.
            if ( !$auth->hasIdentity() ) {
                return;
            }

            //---

            // Find the LPA ID form the URL...
            $lpaId = $locator->get('Application')->getMvcEvent()->getRouteMatch()->getParam('lpa-id');

            if( !is_numeric($lpaId) ){
                throw new RuntimeException('Invalid LPA ID passed');
            }

            //---

            // Load the LPA...
            $lpa = $locator->get('LpaApplicationService')->getApplication( (int)$lpaId );

            //---

            // If it's an object, assume it's an LPA (if it's not it'll be picked up later)
            if( $lpa instanceof Lpa ){

                /**
                 * Conduct some paranoia checks. Check the owner of the LPA matches the current user.
                 * The client would not have been allowed access to it if there was a miss-match, but
                 * there's no reason not to check again here.
                 */

                if( $auth->getIdentity()->id() !== $lpa->user ){
                    throw new RuntimeException('Invalid LPA ID');
                }

                //---

                $instance->setLpa( $lpa );

            } // if

        } // if

    } // function

} // class
