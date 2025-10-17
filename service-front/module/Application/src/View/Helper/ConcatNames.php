<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Application\View\Helper\Traits\ConcatNamesTrait;

class ConcatNames extends AbstractHelper
{
    use ConcatNamesTrait;

    /**
     *
     * @param array $nameList[AbstractData, ]
     * @return NULL|string
     */
    public function __invoke(array $nameList)
    {
        return $this->concatNames($nameList);
    }
}
