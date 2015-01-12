<?php
namespace ApplicationTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
 * AbstractTest.
 */
class AbstractTest extends AbstractHttpControllerTestCase
{
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
