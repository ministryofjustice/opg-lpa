<?php
namespace Application\Model\Service\User;

use Application\Model\Service\AbstractService;
use Opg\Lpa\Logger\LoggerTrait;

class Delete extends AbstractService
{
    use LoggerTrait;

    //---

    /**
     * Deletes a user. i.e. all their LPAs, and their
     *
     * @return bool whether delete was successful.
     */
    public function delete(){

        $this->getLogger()->info(
            'Deleting user and all their LPAs', 
            $this->getAuthenticationService()->getIdentity()->toArray()
        );
        
        return $this->getApiClient()->deleteUserAndAllTheirLpas();

    } // function

} // class
