<?php

namespace Application\Controller;

interface LpaAwareInterface
{
    public function getLpa();
    
    public function setLpa($lpa);
    
}
