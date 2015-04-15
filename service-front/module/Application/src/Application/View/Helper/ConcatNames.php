<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\View\Helper\Traits\ConcatNames;

class ConcatNames extends AbstractHelper
{
    use ConcatNames;
    
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
