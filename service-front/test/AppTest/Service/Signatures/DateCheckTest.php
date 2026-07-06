<?php

declare(strict_types=1);

namespace AppTest\Service\Signatures;

use App\Service\Date\DateService;
use App\Service\Signatures\DateCheck;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DateCheckTest extends TestCase
{
    public function testCheckDatesReturnsTrueWhenDatesAreInValidOrder(): void
    {
        $dates = [
            'sign-date-donor' => 1,
            'sign-date-certificate-provider' => 2,
            'sign-date-attorneys' => [3, 4],
            'sign-date-applicants' => [4],
        ];

        $this->assertTrue(
            DateCheck::checkDates(
                $dates,
                false,
                ['canSign' => true, 'isApplicant' => false],
                $this->createDateServiceForTimestamp(10)
            )
        );
    }

    public function testCheckDatesReturnsDraftErrorsForOutOfOrderSignatures(): void
    {
        $errors = DateCheck::checkDates(
            [
                'sign-date-donor' => 5,
                'sign-date-certificate-provider' => 4,
                'sign-date-donor-life-sustaining' => 6,
                'sign-date-attorneys' => [3, 7],
            ],
            true,
            ['canSign' => true],
            $this->createDateServiceForTimestamp(10)
        );

        $this->assertIsArray($errors);
        $this->assertSame(
            'The donor must be the first person to sign the LPA. You need to print and re-sign sections 10 and 11',
            $errors['sign-date-certificate-provider'][0]
        );
        $this->assertSame(
            'The certificate provider must sign the LPA before the attorneys. You need to print and re-sign section 11',
            $errors['sign-date-certificate-provider'][1]
        );
        $this->assertSame(
            'The donor must sign Section 5 on the same day or before they sign continuation sheet 3. ' .
            'You need to print and re-sign continuation sheet 3, section 10 and section 11',
            $errors['sign-date-donor-life-sustaining'][0]
        );
        $this->assertSame(
            'The donor must be the first person to sign the LPA. You need to print and re-sign sections 10 and 11',
            $errors['sign-date-attorney-0'][0]
        );
    }

    public function testCheckDatesUsesRepresentativeLabelsForApplicantErrors(): void
    {
        $errors = DateCheck::checkDates(
            [
                'sign-date-donor' => 5,
                'sign-date-certificate-provider' => 6,
                'sign-date-attorneys' => [7],
                'sign-date-applicants' => [4],
            ],
            false,
            ['canSign' => false, 'isApplicant' => true],
            $this->createDateServiceForTimestamp(10)
        );

        $this->assertIsArray($errors);
        $this->assertSame(
            'The person signing on behalf of the donor must be the first person to sign the LPA. ' .
            'You need to print and re-sign sections 10, 11 and 15',
            $errors['sign-date-applicant-0'][0]
        );
        $this->assertSame(
            'The person signing on behalf of the applicant must sign on the same day or after all section 11s ' .
            'have been signed. You need to print and re-sign section 15',
            $errors['sign-date-applicant-0'][1]
        );
    }

    public function testCheckDatesReturnsFutureDateErrorsForAllSignerTypes(): void
    {
        $errors = DateCheck::checkDates(
            [
                'sign-date-donor' => new DateTime('2030-01-10'),
                'sign-date-donor-life-sustaining' => new DateTime('2030-01-09'),
                'sign-date-certificate-provider' => new DateTime('2030-01-11'),
                'sign-date-attorneys' => [new DateTime('2030-01-12')],
                'sign-date-applicants' => [new DateTime('2030-01-13')],
            ],
            false,
            ['canSign' => false, 'isApplicant' => true],
            $this->createDateServiceForDate('2029-12-31')
        );

        $this->assertContains(
            'Check your dates. The signature date of the person signing on behalf of the donor cannot be in the future',
            $errors['sign-date-donor']
        );
        $this->assertContains(
            'Check your dates. The signature date of the person signing on behalf of the donor cannot be in the future',
            $errors['sign-date-donor-life-sustaining']
        );
        $this->assertContains(
            'Check your dates. The certificate provider\'s signature date cannot be in the future',
            $errors['sign-date-certificate-provider']
        );
        $this->assertContains(
            'Check your dates. The attorney\'s signature date cannot be in the future',
            $errors['sign-date-attorney-0']
        );
        $this->assertContains(
            'Check your dates. The signature date of the person signing on behalf of the applicant cannot be in the future',
            $errors['sign-date-applicant-0']
        );
    }

    private function createDateServiceForTimestamp(int $timestamp): DateService&MockObject
    {
        $dateService = $this->createMock(DateService::class);
        $dateService->method('getToday')->willReturn((new DateTime())->setTimestamp($timestamp));

        return $dateService;
    }

    private function createDateServiceForDate(string $date): DateService&MockObject
    {
        $dateService = $this->createMock(DateService::class);
        $dateService->method('getToday')->willReturn(new DateTime($date));

        return $dateService;
    }
}
