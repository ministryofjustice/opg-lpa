<?php

namespace Alphagov\Pay\Response;

use GuzzleHttp\Psr7\Uri;

class Payment extends AbstractData
{
    use IncludeResponseTrait;

    public const STATUS_CREATED    = 'created';
    public const STATUS_STARTED    = 'started';
    public const STATUS_SUBMITTED  = 'submitted';
    public const STATUS_SUCCESS    = 'success';

    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_ERROR      = 'error';

    /**
     * Check is the payment has process has finished.
     *
     * @return bool
     */
    public function isFinished()
    {
        return (bool)$this->state->finished;
    }

    //---------------------------

    public function isCreated()
    {
        return ( $this->state->status === self::STATUS_CREATED );
    }
    public function isStarted()
    {
        return ( $this->state->status === self::STATUS_STARTED );
    }
    public function isSubmitted()
    {
        return ( $this->state->status === self::STATUS_SUBMITTED );
    }
    public function isSuccess()
    {
        return ( $this->state->status === self::STATUS_SUCCESS );
    }

    public function isFailed()
    {
        return ( $this->state->status === self::STATUS_FAILED );
    }
    public function isCancelled()
    {
        return ( $this->state->status === self::STATUS_CANCELLED );
    }
    public function isError()
    {
        return ( $this->state->status === self::STATUS_ERROR );
    }

    //---------------------------

    /**
     * Return the URL to the payment page to which the user needs directing to continue.
     *
     * NULL is returned if a payment page URL is not available.
     *
     * @return Uri|null
     */
    public function getPaymentPageUrl()
    {

        if ($this->isFinished() || !isset($this->_links->next_url->href)) {
            return null;
        }

        return new Uri($this->_links->next_url->href);
    }
}
