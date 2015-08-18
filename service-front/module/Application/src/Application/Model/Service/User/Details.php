<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Application\Model\Service\ServiceDataInputInterface;

class Details implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    public function load(){

        $client = $this->getServiceLocator()->get('ApiClient');

        return $client->getAboutMe();

    }

    /**
     * Update the user's basic details.
     *
     * @param ServiceDataInputInterface $details
     * @return mixed
     */
    public function updateAllDetails( ServiceDataInputInterface $details ){

        $this->getServiceLocator()->get('Logger')->info(
            'Updating user details',
            $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray()
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        //---

        // Load the existing details...
        $userDetails = $client->getAboutMe();

        // Apply the new ones...
        $userDetails->populateWithFlatArray( $details->getDataForModel() );

        //---

        $validator = $userDetails->validate();

        if( $validator->hasErrors() ){
            throw new \RuntimeException('Unable to save details');
        }

        //---

        $result = $client->setAboutMe( $userDetails );

        if( $result !== true ){
            throw new \RuntimeException('Unable to save details');
        }

        return $userDetails;

    } // function

    /**
     * Update the user's email address.
     *
     * @param ServiceDataInputInterface $details
     * @return bool|string
     */
    public function updateEmailAddress( ServiceDataInputInterface $details ){

        $this->getServiceLocator()->get('Logger')->info(
            'Updating email address to ' . $details->getDataForModel()['email'],
            $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray()
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $result = $client->updateAuthEmail( strtolower($details->getDataForModel()['email']) );

        //---

        if( $result !== true ){

            // There was an error...

            $error = $client->getLastContent();

            if( isset($error['error_description']) && $error['error_description'] == 'email address is already registered' ){
                return 'address-already-registered';
            } else {
                return 'unknown-error';
            }

        } // if

        return true;

    } // function

    /**
     * Update the user's password.
     *
     * @param ServiceDataInputInterface $details
     * @return bool|string
     */
    public function updatePassword( ServiceDataInputInterface $details ){

        $this->getServiceLocator()->get('Logger')->info(
            'Updating password',
            $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray()
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $result = $client->updateAuthPassword( $details->getDataForModel()['password'] );

        //---

        if( $result !== true ){

            return 'unknown-error';

        } // if

        return true;

    } // function

} // class
