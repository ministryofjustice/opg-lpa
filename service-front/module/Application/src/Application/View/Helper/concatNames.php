<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ConcatNames extends AbstractHelper
{
    /**
     * 
     * @param array $nameList[AbstractData, ]
     * @return NULL|string
     */
    public function __invoke(array $nameList)
    {
        $count = count($nameList);
        if($count == 0) {
            return null;
        }
        elseif($count == 1) {
            if(is_string($nameList[0]->name)) return $nameList[0]->name;
            else return $nameList[0]->name->__toString();
        }
       else {
           $lastItem = array_pop($nameList);
           return implode(', ', array_map( function( $item ) { return (is_string($item->name)?$item->name:$item->name->__toString()); }, $nameList) )
                  . ' and ' . (is_string($lastItem->name)?$lastItem->name:$lastItem->name->__toString());
       }
    }
}
