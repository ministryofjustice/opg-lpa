<?php

namespace Application\Library\ApiProblem;

use Application\Library\ApiProblem as LaminasApiProblem;

/**
 * While this class superficially duplicates LaminasApiProblem, the difference
 * is that this class makes protected methods on its parent class into
 * public methods.
 */
class ApiProblem extends LaminasApiProblem
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return parent::getTitle();
    }

    /**
     * @return int
     */
    public function getStatus() : int
    {
        return parent::getStatus();
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return parent::getDetail();
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }
} // class
