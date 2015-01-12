<?php
namespace ApplicationTest\Controller\General;

use ApplicationTest\Controller\AbstractTest;

/**
 * LoginController test case.
 */
class LoginControllerTest extends AbstractTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        parent::tearDown();
    }

    /**
     * Tests LoginController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/login');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\General\Login');
        $this->assertControllerClass('LoginController');
        $this->assertMatchedRouteName('login');        
    }
}

