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

        //---

        return $client->getAboutMe();

    }

    /*
     * Input options:
     *  An array
     *  The strongly typed form object
     *  An interface all forms implement
     */

    public function updateAllDetails( ServiceDataInputInterface $details ){

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

        $client = $this->getServiceLocator()->get('ApiClient');

        $result = $client->updateAuthEmail( $details->getDataForModel()['email'] );

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

} // class
