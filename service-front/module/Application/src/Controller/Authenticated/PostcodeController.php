<?php

namespace Application\Controller\Authenticated;

use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Controller\AbstractAuthenticatedController;
use Zend\View\Model\JsonModel;

class PostcodeController extends AbstractAuthenticatedController
{
    /**
     * @var OrdnanceSurvey
     */
    private $addressLookup;

    /**
     * Flag to indicate if complete user details are required when accessing this controller
     *
     * @var bool
     */
    protected $requireCompleteUserDetails = false;

    public function indexAction()
    {
        $postcode = $this->params()->fromQuery('postcode');

        if (empty($postcode)) {
            return $this->notFoundAction();
        }

        $addresses = [];

        try {
            $addresses = $this->addressLookup->lookupPostcode($postcode);
        }catch (\RuntimeException $e) {
            $this->getLogger()->warn("Exception from postcode lookup: ".$e->getMessage());
        }


        return new JsonModel([
            'isPostcodeValid' => true,
            'success'         => (count($addresses) > 0),
            'addresses'       => $addresses,
        ]);
    }

    public function setAddressLookup(OrdnanceSurvey $addressLookup)
    {
        $this->addressLookup = $addressLookup;
    }
}
