<?php

namespace Application\Model\Rest;

use Opg\Lpa\DataModel\Lpa\Lpa;

interface LpaConsumerInterface
{
    public function setLpa(Lpa $lpa);

    public function getLpa();
}
