<?php
namespace ApplicationTest\Model;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Service\Signatures\DateCheck;
use DateTime;

/**
 * FormFlowChecker test case.
 */
class DateCheckTest extends AbstractHttpControllerTestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
    }

    public function testAllSignedInCorrectOrder()
    {
        $dates = [
            'donor' => new DateTime('2015-01-14'),
            'certificate-provider' => new DateTime('2015-01-16'),
            'attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
        ];

        $this->assertTrue(DateCheck::checkDates($dates));
    }

    public function testDonorSignsAfterCertificateProvider()
    {
        $dates = [
            'donor' => new DateTime('2015-01-14'),
            'certificate-provider' => new DateTime('2015-01-12'),
            'attorneys' => [
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
        ];

        $this->assertEquals(DateCheck::checkDates($dates), 'The donor must be the first person to sign the LPA.');
    }

    public function testCertificateProviderSignsAfterOneOfTheAttorneys()
    {
        $dates = [
            'donor' => new DateTime('2015-01-14'),
            'certificate-provider' => new DateTime('2015-01-17'),
            'attorneys' => [
                new DateTime('2015-01-16'),
                new DateTime('2015-01-18'),
            ],
        ];

        $this->assertEquals(DateCheck::checkDates($dates), 'The Certificate Provider must sign the LPA before the attorneys.');
    }

    public function testDonorSignsAfterEveryoneElse()
    {
        $dates = [
            'donor' => new DateTime('2015-02-14'),
            'certificate-provider' => new DateTime('2015-01-15'),
            'attorneys' => [
                new DateTime('2015-02-15'),
                new DateTime('2015-02-18'),
            ],
        ];

        $this->assertEquals(DateCheck::checkDates($dates), 'The donor must be the first person to sign the LPA.');
    }

    public function testOneAttorneySignsBeforeEveryoneElse()
    {
        $dates = [
            'donor' => new DateTime('2015-02-14'),
            'certificate-provider' => new DateTime('2015-01-17'),
            'attorneys' => [
                new DateTime('2015-01-06'),
                new DateTime('2015-01-18'),
            ],
        ];

        $this->assertEquals(DateCheck::checkDates($dates), 'The donor must be the first person to sign the LPA.');
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
    }
}
