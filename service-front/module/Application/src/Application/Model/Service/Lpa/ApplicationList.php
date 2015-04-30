<?php
namespace Application\Model\Service\Lpa;

use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Document\Donor;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Used for accessing a list of LPAs owned by the current user.
 *
 * Class ApplicationList
 * @package Application\Model\Service\Lpa
 */
class ApplicationList implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    public function getAllALpaSummaries(){

        $v2Apis = $this->getServiceLocator()->get('LpaApplicationService')->getApplicationList();

        return $this->convertToStandardResponse( $v2Apis );

    } // function

    public function searchAllALpaSummaries( $query ){

        $v2Apis = $this->getServiceLocator()->get('LpaApplicationService')->getApplicationList( $query );

        return $this->convertToStandardResponse( $v2Apis );

    } // function

    /**
     * Converts the LPAs to a standard structure between v1 & v2.
     *
     * @param $v2Apis
     * @return array
     */
    private function convertToStandardResponse( $v2Apis ){

        $lpas = array();

        foreach($v2Apis as $lpa){

            $obj = new \stdClass();

            $obj->id = $lpa->id;

            $obj->version = 2;

            $obj->donor = ((($lpa->document->donor instanceof Donor) && ($lpa->document->donor->name instanceof Name))?(string)$lpa->document->donor->name:'');

            $obj->type = $lpa->document->type;

            $obj->updatedAt = $lpa->updatedAt;

            $obj->progress = ($lpa->completedAt instanceof \DateTime)?'Completed':(($lpa->createdAt instanceof \DateTime)?'Created':'Started');

            $lpas[] = $obj;
        }

        return $lpas;

    } // function

} // class
