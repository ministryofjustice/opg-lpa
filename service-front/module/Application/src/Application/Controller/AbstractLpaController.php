<?php

namespace Application\Controller;

use Application\Controller\AbstractAuthenticatedController;

class AbstractLpaController extends AbstractAuthenticatedController implements LpaAwareInterface
{
    private $lpa;
    
    /**
     * @return the $lpa
     */
    public function getLpa ()
    {
        return $this->lpa;
    }
    
    /**
     * @param field_type $lpa
     */
    public function setLpa ($lpa)
    {
        $this->lpa = $lpa;
    }
    
}
