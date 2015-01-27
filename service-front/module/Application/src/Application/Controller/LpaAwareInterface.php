<?php
namespace Application\Controller;

use Opg\Lpa\DataModel\Lpa\Lpa;

interface LpaAwareInterface
{
    public function getLpa();
    
    public function setLpa( Lpa $lpa );
    
}
