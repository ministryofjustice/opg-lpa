<?php
namespace ApplicationTest\Controller\Authenticated\Lpa;

use ApplicationTest\Controller\AbstractLpaTest;

/**
 * ApplicantController test case.
 */
class ApplicantControllerTest extends AbstractLpaTest
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
     * Tests ApplicantController->indexAction()
     */
    public function testIndexActionCanBeAccessed ()
    {
        $this->dispatch('/lpa/'.$this->lpa_id.'/applicant');
        $this->assertResponseStatusCode(200);
        
        $this->assertModuleName('Application');
        $this->assertControllerName('Application\Controller\Authenticated\Lpa\Applicant');
        $this->assertControllerClass('ApplicantController');
        $this->assertMatchedRouteName('lpa/applicant');        
    }
}

