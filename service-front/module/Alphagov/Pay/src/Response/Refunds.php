<?php

namespace Alphagov\Pay\Response;

use Alphagov\Pay\Exception;

class Refunds extends AbstractData
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

        if (!isset($details['_embedded']->refunds) || !is_array($details['_embedded']->refunds)) {
            throw new Exception\UnexpectedValueException("Refunds response missing '_embedded->refunds' key");
        }

        // Map event details to objects.
        $refunds = array_map(function ($refund) {
            return new Refund((array)$refund);
        }, $details['_embedded']->refunds);


        parent::__construct($refunds);
    }
}
