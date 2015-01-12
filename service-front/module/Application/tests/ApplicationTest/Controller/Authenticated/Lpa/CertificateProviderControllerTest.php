<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * CertificateProviderController test case.
 */
class CertificateProviderControllerTest extends AbstractLpaTest
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
     * Tests CertificateProviderController->indexAction()
     */
    public function testIndexActionCaBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/certificate-provider');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\CertificateProvider');
        $this->assertControllerClass('CertificateProviderController');
        $this->assertMatchedRouteName('lpa/certificate-provider');        
    }
}

