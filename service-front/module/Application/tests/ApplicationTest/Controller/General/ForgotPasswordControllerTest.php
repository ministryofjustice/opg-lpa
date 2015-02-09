<?php
namespace ApplicationTest\Controller\General;

use ApplicationTest\Controller\AbstractTest;

/**
 * ForgotPasswordController test case.
 */
class ForgotPasswordControllerTest extends AbstractTest
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
     * Tests ForgotPasswordController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/forgot-password');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('ControllerFactory');
        $this->assertControllerClass('ForgotPasswordController');
        $this->assertMatchedRouteName('forgot-password');        
    }
}

