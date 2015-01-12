<?php
namespace ApplicationTest\Controller\General;

use ApplicationTest\Controller\AbstractTest;

/**
 * StatusController test case.
 */
class StatusControllerTest extends AbstractTest
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
     * Tests StatusController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/status');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\General\Status');
        $this->assertControllerClass('StatusController');
        $this->assertMatchedRouteName('status');        
    }
}

