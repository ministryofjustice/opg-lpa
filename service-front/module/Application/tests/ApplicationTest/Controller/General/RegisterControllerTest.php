<?php
namespace ApplicationTest\Controller\General;

use ApplicationTest\Controller\AbstractTest;

/**
 * RegisterController test case.
 */
class RegisterControllerTest extends AbstractTest
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
     * Tests RegisterController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/register');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('ControllerFactory');
        $this->assertControllerClass('RegisterController');
        $this->assertMatchedRouteName('register');        
    }
}

