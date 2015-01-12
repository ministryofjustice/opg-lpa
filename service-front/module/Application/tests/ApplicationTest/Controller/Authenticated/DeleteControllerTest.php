<?php
namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * DeleteController test case.
 */
class DeleteControllerTest extends AbstractAuthenticatedTest
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
     * Tests DeleteController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/user/delete');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Delete');
        $this->assertControllerClass('DeleteController');
        $this->assertMatchedRouteName('user/delete');        
    }
}

