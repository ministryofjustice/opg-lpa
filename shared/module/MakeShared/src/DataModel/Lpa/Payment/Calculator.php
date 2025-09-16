<?php

namespace MakeShared\DataModel\Lpa\Payment;

use DateTimeImmutable;
use DateTimeZone;
use MakeShared\DataModel\Lpa\Lpa;

class Calculator
{
    public const STANDARD_FEE = 82;
    private static ?DateTimeImmutable $now = null;
    private static ?DateTimeZone $timeZone = null;
    private static ?DateTimeImmutable $effectiveDate = null;
    private static int $baseBefore = self::STANDARD_FEE;
    private static int $baseAfter  = 92;

    public static function bootstrap(array $feesconfig, ?DateTimeImmutable $now = null): void
    {
        $tz = new DateTimeZone($feesconfig['timezone']);

        self::$timeZone     = $tz;
        self::$effectiveDate = new DateTimeImmutable(
            $feesconfig['effectiveDate'] ?? '2025-11-17T00:00:00',
            $tz
        );
        self::$baseBefore = (int)($feesconfig['baseBefore']);
        self::$baseAfter  = (int)($feesconfig['baseAfter']);
        self::$now        = ($now ?? new DateTimeImmutable('now'))->setTimezone($tz);
    }

    public static function setNow(DateTimeImmutable $now): void
    {
        if (!self::$timeZone) {
            self::$timeZone = new DateTimeZone('Europe/London');
        }
        self::$now = $now->setTimezone(self::$timeZone);
    }

    public function __construct(array $feesconfig, ?DateTimeImmutable $now = null)
    {
        self::bootstrap($feesconfig, $now);
    }

    private static function ensureInit(): void
    {
        if (self::$now === null || self::$effectiveDate === null || self::$timeZone === null) {
            self::bootstrap([
                'timezone'      => 'Europe/London',
                'effectiveDate' => '2025-11-17T00:00:00',
                'baseBefore'    => 82,
                'baseAfter'     => 92,
            ]);
        }
    }

    /**
     * Calculate LPA payment amount
     *
     * @param Lpa $lpa
     * @return NULL|Payment
     */
    public static function calculate(Lpa $lpa): ?Payment
    {
        self::ensureInit();

        if (!$lpa->getPayment() instanceof Payment) {
            return null;
        }

        if ($lpa->getPayment()->isReducedFeeReceivesBenefits() && $lpa->getPayment()->isReducedFeeAwardedDamages()) {
            $amount = self::getBenefitsFee();
        } else {
            $isRepeatApplication = ($lpa->getRepeatCaseNumber() != null);

            if ($lpa->getPayment()->isReducedFeeUniversalCredit()) {
                $amount = null;
            } elseif ($lpa->getPayment()->isReducedFeeLowIncome()) {
                $amount = self::getLowIncomeFee($isRepeatApplication);
            } else {
                $amount = self::getFullFee($isRepeatApplication);
            }
        }

        $lpa->getPayment()->setAmount($amount);
        return $lpa->getPayment();
    }

    public static function getFullFee(bool $isRepeatApplication = false): int
    {
        self::ensureInit();
        return $isRepeatApplication ? self::repeatApplicationFee() : self::baseFee();
    }

    public static function getLowIncomeFee(bool $isRepeatApplication = false): int
    {
        self::ensureInit();
        return self::getFullFee($isRepeatApplication) / 2;
    }

    public static function getBenefitsFee(): int
    {
        self::ensureInit();
        return 0.0;
    }

    public static function baseFee(): int
    {
        self::ensureInit();
        return (self::$now >= self::$effectiveDate) ? self::$baseAfter : self::$baseBefore;
    }

    public static function halfFee(): int
    {
        self::ensureInit();
        return intdiv(self::baseFee(), 2);
    }

    public static function repeatApplicationFee(): int
    {
        self::ensureInit();
        return self::halfFee();
    }
}
