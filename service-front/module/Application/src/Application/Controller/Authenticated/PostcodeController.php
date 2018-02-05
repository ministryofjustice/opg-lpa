<?php
namespace Application\Controller\Authenticated;

use Application\Model\Service\AddressLookup\PostcodeInfo;
use Zend\View\Model\JsonModel;
use Application\Controller\AbstractAuthenticatedController;

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

    public function indexAction(){

        $postcode = $this->params()->fromQuery('postcode');

        if( empty($postcode) ){
            return $this->notFoundAction();
        }

        //---

        $result = $this->addressLookup->lookupPostcode($postcode);

        // Map the result to match the format from v1.
        $formattedData = array_map( function($addr) {

            return [
                'id' => $addr['Id'],
                'description' => $addr['Summary'],
                'line1' => $addr['Detail']['line1'],
                'line2' => $addr['Detail']['line2'],
                'line3' => $addr['Detail']['line3'],
                'postcode' => $addr['Detail']['postcode'],
            ];

        }, $result );

        return new JsonModel( [
            'isPostcodeValid'=>true,
            'success'=> ( count($formattedData) > 0 ),
            'addresses' => $formattedData,
            'postcodeService' => 'mojDs',
        ]);

    }

    public function setAddressLookup(PostcodeInfo $addressLookup)
    {
        $this->addressLookup = $addressLookup;
    }

} // class
