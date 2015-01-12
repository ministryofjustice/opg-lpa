<?php
namespace ApplicationTest\Controller;

use ApplicationTest\Controller\AbstractTest;

/**
 * AbstractAuthenticatedTest.
 */
class AbstractAuthenticatedTest extends AbstractTest
{
    protected $user;
    
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
