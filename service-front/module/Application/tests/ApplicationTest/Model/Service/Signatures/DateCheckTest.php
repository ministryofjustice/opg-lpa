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
            'sign-date-donor' => new DateTime('2015-01-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-16'),
            'sign-date-attorneys' => [
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
            'sign-date-donor' => new DateTime('2015-01-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-16'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'sign-date-applicants' => [
                new DateTime('2015-01-18')
            ]
        ];

        $this->assertTrue(DateCheck::checkDates($dates));
    }

    public function testDonorSignsOnOrBeforeLifeSustaining()
    {
        $dates = [
            'sign-date-donor' => new DateTime('2015-01-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-16'),
            'sign-date-donor-life-sustaining' => new DateTime('2015-01-16'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
        ];

        $errors = DateCheck::checkDates($dates);
        $this->assertNotTrue($errors);

        $this->assertEquals([
            'sign-date-donor-life-sustaining' => ['The donor must sign Section 5 on the same day or before section 9']
        ], $errors);
    }

    public function testDonorSignsAfterCertificateProvider()
    {
        $dates = [
            'sign-date-donor' => new DateTime('2015-01-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-12'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
        ];

        $errors = DateCheck::checkDates($dates);
        $this->assertNotTrue($errors);

        $this->assertEquals([
            'sign-date-certificate-provider' => ['The donor must be the first person to sign the LPA']
        ], $errors);
    }

    public function testCertificateProviderSignsAfterOneOfTheAttorneys()
    {
        $dates = [
            'sign-date-donor' => new DateTime('2015-01-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-17'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-16'),
                new DateTime('2015-01-18'),
            ],
        ];

        $errors = DateCheck::checkDates($dates);
        $this->assertNotTrue($errors);

        $this->assertEquals([
            'sign-date-certificate-provider' => ['The certificate provider must sign the LPA before the attorneys']
        ], $errors);
    }

    public function testDonorSignsAfterEveryoneElse()
    {
        $dates = [
            'sign-date-donor' => new DateTime('2015-02-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-17'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-06'),
                new DateTime('2015-01-12'),
            ],
            'sign-date-applicants' => [
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17')
            ],
        ];

        $errors = DateCheck::checkDates($dates);
        $this->assertNotTrue($errors);

        $this->assertEquals([
            'sign-date-attorney-0' => ['The donor must be the first person to sign the LPA'],
            'sign-date-attorney-1' => ['The donor must be the first person to sign the LPA'],
            'sign-date-certificate-provider' => [
                'The donor must be the first person to sign the LPA',
                'The certificate provider must sign the LPA before the attorneys'
            ],
            'sign-date-applicant-0' => ['The donor must be the first person to sign the LPA'],
            'sign-date-applicant-1' => ['The donor must be the first person to sign the LPA'],
        ], $errors);
    }

    public function testApplicantSignsBeforeLastAttorney()
    {
        $dates = [
            'sign-date-donor' => new DateTime('2015-01-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-16'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'sign-date-applicants' => [
                new DateTime('2015-01-17')
            ]
        ];

        $errors = DateCheck::checkDates($dates);
        $this->assertNotTrue($errors);

        $this->assertEquals([
            'sign-date-applicant-0' => ['The applicant must sign on the same day or after all Section 11\'s have been signed']
        ], $errors);
    }

    public function testApplicantsSignBeforeLastAttorney()
    {
        $dates = [
            'sign-date-donor' => new DateTime('2015-01-14'),
            'sign-date-certificate-provider' => new DateTime('2015-01-16'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'sign-date-applicants' => [
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17')
            ]
        ];

        $errors = DateCheck::checkDates($dates);
        $this->assertNotTrue($errors);

        $this->assertEquals([
            'sign-date-applicant-0' => ['The applicant must sign on the same day or after all Section 11\'s have been signed'],
            'sign-date-applicant-1' => ['The applicant must sign on the same day or after all Section 11\'s have been signed']
        ], $errors);
    }

    public function testDatesCannotBeInFuture()
    {
        $dates = [
            'sign-date-donor' => new DateTime('2015-01-15'),
            'sign-date-certificate-provider' => new DateTime('2015-01-16'),
            'sign-date-donor-life-sustaining' => new DateTime('2015-01-14'),
            'sign-date-attorneys' => [
                new DateTime('2015-01-18'),
                new DateTime('2015-01-16'),
                new DateTime('2015-01-17'),
            ],
            'sign-date-applicants' => [
                new DateTime('2015-01-19')
            ]
        ];

        $this->dateService->shouldReceive('getToday')->andReturn(new DateTime('2015-01-10'))->once();

        $errors = DateCheck::checkDates($dates, $this->dateService);
        $this->assertNotTrue($errors);

        $this->assertEquals([
            'sign-date-donor' => ['The donor\'s signature date cannot be in the future'],
            'sign-date-certificate-provider' => ['The certificate provider\'s signature date cannot be in the future'],
            'sign-date-donor-life-sustaining' => ['The donor\'s signature date cannot be in the future'],
            'sign-date-attorney-0' => ['The attorney\'s signature date cannot be in the future'],
            'sign-date-attorney-1' => ['The attorney\'s signature date cannot be in the future'],
            'sign-date-attorney-2' => ['The attorney\'s signature date cannot be in the future'],
            'sign-date-applicant-0' => ['The applicant\'s signature date cannot be in the future']
        ], $errors);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
    }
}
