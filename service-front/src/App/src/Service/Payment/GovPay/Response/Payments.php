<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception;

class Payments extends AbstractData
{
    use IncludeResponseTrait;

    /**
     * @param array<mixed> $details
     */
    public function __construct(array $details)
    {
        if (!isset($details['results']) || !is_array($details['results'])) {
            throw new Exception\UnexpectedValueException("Payments response missing 'results' key");
        }

        $payments = array_map(
            static fn (mixed $payment): Payment => new Payment((array) $payment),
            $details['results']
        );

        parent::__construct($payments);
    }
}
