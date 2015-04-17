<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class CellStyles extends AbstractHelper
{
    public function __invoke($refNum)
    {
        $html = "";
        foreach(str_split(strtoupper($refNum)) as $char) {
            $html.= "<span style='font-family: mono; font-size:10pt; margin:1px; border:1px solid #CCC; padding:0 3px 0 3px;'>".$char."</span>";
        }
         
        return $html;
    }
}