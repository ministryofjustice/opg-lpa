<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay\Response;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * @psalm-suppress UndefinedMagicPropertyFetch
 */
class Payment extends AbstractData
{
    use IncludeResponseTrait;

    public const STATUS_CREATED   = 'created';
    public const STATUS_STARTED   = 'started';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_SUCCESS   = 'success';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR     = 'error';

    public function isFinished(): bool
    {
        return (bool) $this->state->finished;
    }

    public function isCreated(): bool
    {
        return $this->state->status === self::STATUS_CREATED;
    }

    public function isStarted(): bool
    {
        return $this->state->status === self::STATUS_STARTED;
    }

    public function isSubmitted(): bool
    {
        return $this->state->status === self::STATUS_SUBMITTED;
    }

    public function isSuccess(): bool
    {
        return $this->state->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->state->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->state->status === self::STATUS_CANCELLED;
    }

    public function isError(): bool
    {
        return $this->state->status === self::STATUS_ERROR;
    }

    /**
     * Return the URL to the payment page to which the user needs directing to continue.
     *
     * Returns null if a payment page URL is not available (e.g. payment is finished).
     */
    public function getPaymentPageUrl(): ?UriInterface
    {
        if ($this->isFinished() || !isset($this->_links->next_url->href)) {
            return null;
        }

        return new Uri($this->_links->next_url->href);
    }
}
