<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Delete implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    /**
     * Deletes a user. i.e. all their LPAs, and their
     *
     * @return bool whether delete was successful.
     */
    public function delete(){

        return $this->getServiceLocator()->get('ApiClient')->deleteUserAndAllTheirLpas();

    } // function

} // class
