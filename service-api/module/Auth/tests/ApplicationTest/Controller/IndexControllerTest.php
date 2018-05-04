<?php

namespace AuthTest\Controller;

use Auth\Controller\IndexController;
use PHPUnit\Framework\TestCase;

class IndexControllerTest extends TestCase
{
    public function testIndexAction()
    {
        $controller = new IndexController();

        $this->assertInstanceOf('Zend\View\Model\ViewModel', $controller->indexAction());
    }
}
