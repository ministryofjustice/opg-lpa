<?php

namespace Alphagov\Pay\Response;

use Alphagov\Pay\Exception;

class Payments extends AbstractData
{
    use IncludeResponseTrait;

    public function __construct(array $details)
    {

        if (!isset($details['results']) || !is_array($details['results'])) {
            throw new Exception\UnexpectedValueException("Payments response missing 'results' key");
        }

        // Map event details to objects.
        $payments = array_map(function ($payment) {
            return new Payment((array)$payment);
        }, $details['results']);

        parent::__construct($payments);
    }
}
