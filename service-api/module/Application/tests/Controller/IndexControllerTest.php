<?php

namespace ApplicationTest\Controller;

use Application\Controller\IndexController;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Zend\View\Model\ViewModel;

class IndexControllerTest extends MockeryTestCase
{
    public function testIndexAction()
    {
        $controller = new IndexController();

        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }
}
