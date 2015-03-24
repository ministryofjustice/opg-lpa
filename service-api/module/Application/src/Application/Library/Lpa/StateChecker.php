<?php
namespace Application\Library\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker as DataModelStateChecker;

class StateChecker extends DataModelStateChecker {

    /**
     * True if the LPA ahs a valid id.
     *
     * @return bool
     */
    public function isStateStarted(){
        return !$this->getLpa()->validate( ['id'] )->hasErrors();
    }

} // class
