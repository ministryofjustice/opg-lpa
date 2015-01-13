<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * OnlinePaymentSuccessController test case.
 */
class OnlinePaymentSuccessControllerTest extends AbstractLpaTest
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
     * Tests OnlinePaymentSuccessController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/online-payment-success');
        $this->assertResponseStatusCode(200);
        //$this->assertRedirectTo('/user/complete');
        
        $this->assertModuleName('Application');
        $this->assertControllerName('ControllerFactory');
        $this->assertControllerClass('OnlinePaymentSuccessController');
        $this->assertMatchedRouteName('lpa/online-payment-success');        
    }
}

