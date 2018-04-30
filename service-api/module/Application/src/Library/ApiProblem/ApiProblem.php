<?php
namespace Application\Library\ApiProblem;

use ZF\ApiProblem\ApiProblem as ZFApiProblem;

class ApiProblem extends ZFApiProblem
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return parent::getTitle();
    }

    /**
     * @return string
     */
    public function getStatus()
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
