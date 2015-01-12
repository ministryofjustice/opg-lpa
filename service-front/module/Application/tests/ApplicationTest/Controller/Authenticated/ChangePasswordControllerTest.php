<?php
namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * ChangePasswordController test case.
 */
class ChangePasswordControllerTest extends AbstractAuthenticatedTest
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
     * Tests ChangePasswordController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/user/change-password');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\ChangePassword');
        $this->assertControllerClass('ChangePasswordController');
        $this->assertMatchedRouteName('user/change-password');        
    }
}

