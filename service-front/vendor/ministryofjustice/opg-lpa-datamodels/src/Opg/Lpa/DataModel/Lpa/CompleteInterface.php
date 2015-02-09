<?php
namespace Opg\Lpa\DataModel\Lpa;

interface CompleteInterface {

    /**
     * Returns bool true iff the LPA document is complete from a business rule perspective. False otherwise.
     *
     * @return bool
     */
    public function isComplete();

} // interface
