<?php
namespace Application\Controller\Authenticated;

use Zend\View\Model\JsonModel;
use Application\Controller\AbstractAuthenticatedController;

class PostcodeController extends AbstractAuthenticatedController {

    public function indexAction(){

        $service = $this->getServiceLocator()->get('AddressLookup');

        //-----------------------
        // Postcode lookup

        $postcode = $this->params()->fromQuery('postcode');

        if( isset($postcode) ){

            $result = $service->lookupPostcode( $postcode );

            return new JsonModel( $result );

        }

        //-----------------------
        // Address lookup

        $addressId = $this->params()->fromQuery('addressId');

        if( isset($addressId) ){

            $result = $service->lookupAddress( $addressId );

            return new JsonModel( $result );

        }

        //---

        // else not found.
        return $this->notFoundAction();

    } // function

} // class
