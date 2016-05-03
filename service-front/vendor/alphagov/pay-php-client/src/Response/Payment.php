<?php
namespace Alphagov\Pay\Response;

use GuzzleHttp\Psr7\Uri;

use Alphagov\Pay\Exception;

class Payment extends AbstractData {
    use IncludeResponseTrait;

    const STATUS_CREATED            = 'CREATED';
    const STATUS_IN_PROGRESS        = 'IN PROGRESS';
    const STATUS_SUCCEEDED          = 'SUCCEEDED';
    const STATUS_EXPIRED            = 'EXPIRED';
    const STATUS_USER_CANCELLED     = 'USER CANCELLED';
    const STATUS_FAILED             = 'FAILED';
    const STATUS_SYSTEM_CANCELLED   = 'SYSTEM CANCELLED';

    /**
     * Check if this payment is currently in play. Otherwise it's in an End State.
     *
     * @return bool
     */
    public function isInPlay(){

        // Payment is in play if it's status is created or in progress...
        if( !in_array( $this->status, [ self::STATUS_CREATED, self::STATUS_IN_PROGRESS ], true ) ){
            return false;
        }

        // and if we have a 'next_url' link.
        if( !isset( $this['_links']['next_url']['href'] ) ){
            return false;
        }

        return true;

    }

    /**
     * Is the payment both in an End State, and successful.
     *
     * @return bool
     */
    public function isSuccessful(){

        return ( $this->status === self::STATUS_SUCCEEDED );

    }

    /**
     * Return the URL to the payment page to which the user needs directing to continue.
     *
     * @return bool|Uri
     */
    public function getPaymentPageUrl(){

        if( !$this->isInPlay() ){
            throw new Exception\UnexpectedValueException('A payment needs to be In Play to return ');
        }

        return new Uri( $this['_links']['next_url']['href'] );

    }

}
