<?php
namespace ApplicationTest\Controller\Authenticated;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * ChangeEmailAddressController test case.
 */
class ChangeEmailAddressControllerTest extends AbstractAuthenticatedTest
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
     * Tests ChangeEmailAddressController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/user/change-email-address');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\ChangeEmailAddress');
        $this->assertControllerClass('ChangeEmailAddressController');
        $this->assertMatchedRouteName('user/change-email-address');        
    }
}

