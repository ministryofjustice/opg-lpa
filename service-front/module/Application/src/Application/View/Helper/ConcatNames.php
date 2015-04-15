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
            $actor = current($nameList);
            if(is_string($actor->name)) return $actor->name;
            else return (string)$actor->name;
        }
       else {
           $lastItem = array_pop($nameList);
           return implode(', ', array_map( function( $item ) { return (string)$item->name; }, $nameList) )
                  . ' and ' . (string)$lastItem->name;
       }
    }
}
