<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Model\Service\AddressLookup\PostcodeInfo;
use Zend\View\Model\JsonModel;

class PostcodeController extends AbstractAuthenticatedController
{
    /**
     * @var PostcodeInfo
     */
    private $addressLookup;

    /**
     * Allow access to this controller before About You details are set.
     *
     * @var bool
     */
    protected $excludeFromAboutYouCheck = true;

    public function indexAction()
    {
        $postcode = $this->params()->fromQuery('postcode');

        if (empty($postcode)) {
            return $this->notFoundAction();
        }

        $addresses = $this->addressLookup->lookupPostcode($postcode);

        return new JsonModel([
            'isPostcodeValid' => true,
            'success'         => (count($addresses) > 0),
            'addresses'       => $addresses,
        ]);
    }

    public function setAddressLookup(PostcodeInfo $addressLookup)
    {
        $this->addressLookup = $addressLookup;
    }
}
