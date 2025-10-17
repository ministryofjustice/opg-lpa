<?php

namespace Alphagov\Pay\Response;

use Alphagov\Pay\Exception;

class Events extends AbstractData
{
    use IncludeResponseTrait;

    /**
     * @var string The payment_id to which these events relate.
     */
    public $payment_id;

    public function __construct(array $details)
    {

        if (isset($details['payment_id'])) {
            $this->payment_id = $details['payment_id'];
        }

        if (!isset($details['events']) || !is_array($details['events'])) {
            throw new Exception\UnexpectedValueException("Events response missing 'events' key");
        }

        // Map event details to objects.
        $events = array_map(function ($event) {
            return new Event((array)$event);
        }, $details['events']);


        parent::__construct($events);
    }
}
