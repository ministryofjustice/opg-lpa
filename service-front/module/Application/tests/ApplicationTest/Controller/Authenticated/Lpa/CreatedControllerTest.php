<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * CreatedController test case.
 */
class CreatedControllerTest extends AbstractLpaTest
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
     * Tests CreatedController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/created');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\Created');
        $this->assertControllerClass('CreatedController');
        $this->assertMatchedRouteName('lpa/created');        
    }
}

