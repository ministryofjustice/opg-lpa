<?php
namespace Application\Model\Rest\Applications;

class AbbreviatedEntity extends Entity {

    public function toArray(){
        return $this->lpa->abbreviatedToArray();
    }

} // class
