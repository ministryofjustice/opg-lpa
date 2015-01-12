<?php
namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * PostcodeController test case.
 */
class PostcodeControllerTest extends AbstractAuthenticatedTest
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
     * Tests PostcodeController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/postcode?postcode=SW1H 9AJ');
        $this->assertResponseStatusCode(200);
        $this->reset();
        $this->dispatch('/postcode?postcode=SW1H%209AJ');
        $this->assertResponseStatusCode(200);
        $this->reset();
        $this->dispatch('/postcode?id=123456789');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Postcode');
        $this->assertControllerClass('PostcodeController');
        $this->assertMatchedRouteName('postcode');        
    }
}

