<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * CompleteController test case.
 */
class CompleteControllerTest extends AbstractLpaTest
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
     * Tests CompleteController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/complete');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\Complete');
        $this->assertControllerClass('CompleteController');
        $this->assertMatchedRouteName('lpa/complete');        
    }
}

