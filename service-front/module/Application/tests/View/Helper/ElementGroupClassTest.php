<?php

namespace ApplicationTest\View\Helper;

use Application\View\Helper\ElementGroupClass;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Zend\Form\ElementInterface;

class ElementGroupClassTest extends MockeryTestCase
{
    public function testInvoke():void
    {
        $elementInterface = Mockery::mock(ElementInterface::class);
        $elementInterface->shouldReceive('getMessages')->withArgs([])->once()->andReturn("This is test failure message");

        $elementGroup = new ElementGroupClass();
        $elementGroup($elementInterface);
    }
}