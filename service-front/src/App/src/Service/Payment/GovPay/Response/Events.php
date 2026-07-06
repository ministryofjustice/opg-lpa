<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception;

class Events extends AbstractData
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

        if (!isset($details['events']) || !is_array($details['events'])) {
            throw new Exception\UnexpectedValueException("Events response missing 'events' key");
        }

        $events = array_map(
            static fn (mixed $event): Event => new Event((array) $event),
            $details['events']
        );

        parent::__construct($events);
    }
}
