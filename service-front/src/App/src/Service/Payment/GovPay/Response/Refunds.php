<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception;

class Refunds extends AbstractData
{
    use IncludeResponseTrait;

    public string $payment_id = '';

    /**
     * @param array<mixed> $details
     */
    public function __construct(array $details)
    {
        if (isset($details['payment_id'])) {
            $this->payment_id = (string) $details['payment_id'];
        }

        if (!isset($details['_embedded']->refunds) || !is_array($details['_embedded']->refunds)) {
            throw new Exception\UnexpectedValueException("Refunds response missing '_embedded->refunds' key");
        }

        $refunds = array_map(
            static fn (mixed $refund): Refund => new Refund((array) $refund),
            $details['_embedded']->refunds
        );

        parent::__construct($refunds);
    }
}
