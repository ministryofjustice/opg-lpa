<?php
namespace ApplicationTest\Controller;

use ApplicationTest\Controller\AbstractAuthenticatedTest;

/**
 * AbstractLpaTest.
 */
class AbstractLpaTest extends AbstractAuthenticatedTest
{
    protected $lpa;
    protected $lpa_id = '1234567890';
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        $this->setApplicationConfig(
            include 'config/application.config.php'
        );
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        parent::tearDown();
    }
}
