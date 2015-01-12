<?php
namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * AboutYouController test case.
 */
class AboutYouControllerTest extends AbstractAuthenticatedTest
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
     * Tests AboutYouController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/user/about-you');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\AboutYou');
        $this->assertControllerClass('AboutYouController');
        $this->assertMatchedRouteName('user/about-you');        
    }
}

