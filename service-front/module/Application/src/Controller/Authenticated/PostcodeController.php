<?php

namespace Application\Controller\Authenticated;

use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Controller\AbstractAuthenticatedController;
use Laminas\View\Model\JsonModel;
use MakeShared\Logging\LoggerTrait;

class PostcodeController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /**
     * @var OrdnanceSurvey
     */
    private $addressLookup;

    /**
     * Flag to indicate if complete user details are required when accessing this controller
     */
    protected bool $requireCompleteUserDetails = false;

    public function indexAction()
    {
        $postcode = $this->params()->fromQuery('postcode');

        if (empty($postcode)) {
            return $this->notFoundAction();
        }

        $addresses = [];

        try {
            $addresses = $this->addressLookup->lookupPostcode($postcode);
        } catch (\RuntimeException $e) {
            $this->getLogger()->warning('Exception from postcode lookup', [
                'exception' => $e,
            ]);
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
