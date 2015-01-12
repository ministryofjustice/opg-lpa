<?php
namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * AdminController test case.
 */
class AdminControllerTest extends AbstractAuthenticatedTest
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
     * Tests AdminController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/admin/stats');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Admin');
        $this->assertControllerClass('AdminController');
        $this->assertMatchedRouteName('admin-stats');        
    }
}

