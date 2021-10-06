<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class FormatLpaId extends AbstractHelper
{
    public function __invoke($id)
    {
        return \Opg\Lpa\DataModel\Lpa\Formatter::id($id);
    }
}
