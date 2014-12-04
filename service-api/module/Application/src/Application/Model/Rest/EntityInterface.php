<?php
namespace Application\Model\Rest;

interface EntityInterface {

    public function lpaId();
    public function resourceId();

    /**
     * @return \Application\Library\Hal\Hal
     */
    public function getHal();

} // interface
