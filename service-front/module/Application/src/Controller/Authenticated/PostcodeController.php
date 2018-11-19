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

        try {

            $addresses = $this->addressLookup->lookupPostcode($postcode);

            return new JsonModel([
                'isPostcodeValid' => true,
                'success'         => (count($addresses) > 0),
                'addresses'       => $addresses,
            ]);

        }catch (\RuntimeException $e) {}


        return new JsonModel([
            'isPostcodeValid' => false,
            'success'         => false,
            'addresses'       => null,
        ]);
    }

    public function setAddressLookup(OrdnanceSurvey $addressLookup)
    {
        $this->addressLookup = $addressLookup;
    }
}
