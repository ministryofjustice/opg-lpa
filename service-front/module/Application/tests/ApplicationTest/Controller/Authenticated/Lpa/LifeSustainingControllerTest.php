<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * LifeSustainingController test case.
 */
class LifeSustainingControllerTest extends AbstractLpaTest
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
     * Tests LifeSustainingController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/life-sustaining');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\LifeSustaining');
        $this->assertControllerClass('LifeSustainingController');
        $this->assertMatchedRouteName('lpa/life-sustaining');        
    }
}

