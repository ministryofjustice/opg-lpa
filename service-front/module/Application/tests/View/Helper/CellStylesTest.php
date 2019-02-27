<?php

namespace ApplicationTest\View\Helper;

use Application\View\Helper\CellStyles;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CellStylesTest extends MockeryTestCase
{
    public function testInvoke():void
    {
        $refNum = 'ab';
        $cellStyles = new CellStyles();

        $result = $cellStyles($refNum);
        $expectedResult = "<span style='font-family: mono; font-size:10pt; margin:1px; border:1px solid #CCC; 
                            padding:0 3px 0 3px;'>A</span><span style='font-family: mono; font-size:10pt; 
                            margin:1px; border:1px solid #CCC; padding:0 3px 0 3px;'>B</span>";

        $this->assertEquals($expectedResult, $result);
    }
}