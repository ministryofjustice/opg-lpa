<?php
namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * LogoutController test case.
 */
class LogoutControllerTest extends AbstractAuthenticatedTest
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
     * Tests LogoutController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/user/logout');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('ControllerFactory');
        $this->assertControllerClass('LogoutController');
        $this->assertMatchedRouteName('user/logout');        
    }
}

