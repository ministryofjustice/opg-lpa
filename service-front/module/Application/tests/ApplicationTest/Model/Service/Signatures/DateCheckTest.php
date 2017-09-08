<?php
namespace ApplicationTest\Model;

use Application\Model\Service\Date\DateService;
use Application\Model\Service\Date\IDateService;
use Mockery;
use Mockery\MockInterface;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Service\Signatures\DateCheck;
use DateTime;

/**
 * FormFlowChecker test case.
 */
class DateCheckTest extends AbstractHttpControllerTestCase
{
    /**
     * @var MockInterface|IDateService
     */
    private $dateService;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dateService = Mockery::mock(IDateService::class);
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

    public function testAllSignedInCorrectOrderIncludingApplicant()
    {
        $dates = [
            'donor' => new DateTime('2015-01-14'),
            'certificate-provider' => new DateTime('2015-01-16'),
            'attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'applicants' => [
                new DateTime('2015-01-18')
            ]
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

        $this->assertEquals('The donor must be the first person to sign the LPA.', DateCheck::checkDates($dates));
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

        $this->assertEquals('The Certificate Provider must sign the LPA before the attorneys.', DateCheck::checkDates($dates));
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

        $this->assertEquals('The donor must be the first person to sign the LPA.', DateCheck::checkDates($dates));
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

        $this->assertEquals('The donor must be the first person to sign the LPA.', DateCheck::checkDates($dates));
    }

    public function testApplicantSignsBeforeLastAttorney()
    {
        $dates = [
            'donor' => new DateTime('2015-01-14'),
            'certificate-provider' => new DateTime('2015-01-16'),
            'attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'applicants' => [
                new DateTime('2015-01-17')
            ]
        ];

        $this->assertEquals('The applicant must sign on the same day or after all Section 11\'s have been signed.', DateCheck::checkDates($dates));
    }

    public function testApplicantsSignBeforeLastAttorney()
    {
        $dates = [
            'donor' => new DateTime('2015-01-14'),
            'certificate-provider' => new DateTime('2015-01-16'),
            'attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'applicants' => [
                new DateTime('2015-01-17'),
                new DateTime('2015-01-18')
            ]
        ];

        $this->assertEquals('The applicants must sign on the same day or after all Section 11\'s have been signed.', DateCheck::checkDates($dates));
    }

    public function testDatesCannotBeInFuture()
    {
        $dates = [
            'donor' => new DateTime('2015-01-14'),
            'certificate-provider' => new DateTime('2015-01-16'),
            'attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'applicants' => [
                new DateTime('2015-01-19')
            ]
        ];

        $this->dateService->shouldReceive('getToday')->andReturn(new DateTime('2015-01-18'))->once();

        $this->assertEquals('No sign date can be in the future.', DateCheck::checkDates($dates, $this->dateService));
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
    }
}
