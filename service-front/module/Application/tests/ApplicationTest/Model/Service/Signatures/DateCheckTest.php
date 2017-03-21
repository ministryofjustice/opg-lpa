<?php
namespace ApplicationTest\Model;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Service\Signatures\DateCheck;

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
            'donor' => '14/01/2015',
            'certificate-provider' => '16/01/2015',
            'attorneys' => [
                '18/01/2015',
                '16/01/2015',
                '17/01/2015',
            ],
        ];

        $this->assertTrue(DateCheck::checkDates($dates));
    }

    public function testDonorSignsAfterCertificateProvider()
    {
        $dates = [
            'donor' => '14/01/2015',
            'certificate-provider' => '12/01/2015',
            'attorneys' => [
                '16/01/2015',
                '17/01/2015',
            ],
        ];

        $this->assertEquals(DateCheck::checkDates($dates), 'The donor must be the first person to sign the LPA.');
    }

    public function testCertificateProviderSignsAfterOneOfTheAttorneys()
    {
        $dates = [
            'donor' => '14/01/2015',
            'certificate-provider' => '17/01/2015',
            'attorneys' => [
                '16/01/2015',
                '18/01/2015',
            ],
        ];

        $this->assertEquals(DateCheck::checkDates($dates), 'The Certificate Provider must sign the LPA before the attorneys.');
    }

    public function testDonorSignsAfterEveryoneElse()
    {
        $dates = [
            'donor' => '14/02/2015',
            'certificate-provider' => '17/01/2015',
            'attorneys' => [
                '16/01/2015',
                '18/01/2015',
            ],
        ];

        $this->assertEquals(DateCheck::checkDates($dates), 'The donor must be the first person to sign the LPA.');
    }

    public function testOneAttorneySignsBeforeEveryoneElse()
    {
        $dates = [
            'donor' => '14/02/2015',
            'certificate-provider' => '17/01/2015',
            'attorneys' => [
                '06/01/2015',
                '18/01/2015',
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

