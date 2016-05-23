<?php
namespace Alphagov\Pay\Response;

use GuzzleHttp\Psr7\Uri;

use Alphagov\Pay\Exception;

class Payment extends AbstractData {
    use IncludeResponseTrait;

    const STATUS_CREATED    = 'created';
    const STATUS_STARTED    = 'started';
    const STATUS_SUBMITTED  = 'submitted';
    const STATUS_CONFIRMED  = 'confirmed';
    const STATUS_CAPTURED   = 'captured';

    const STATUS_FAILED     = 'failed';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_ERROR      = 'error';


    public function __construct( Array $details ){

        if( isset($details['state']) ){
            $details['state'] = new \ArrayObject( $details['state'], \ArrayObject::ARRAY_AS_PROPS );
        }

        parent::__construct( $details );

    }

    /**
     * Check is the payment has process has finished.
     *
     * @return bool
     */
    public function isFinished(){
        return (bool)$this->state->finished;
    }


    /**
     * Is the payment both finished and successful.
     *
     * @return bool
     */
    public function isSuccessful(){

        return ( $this->isFinished() && ( $this->isConfirmed() || $this->isCaptured() ) );

    }

    //---------------------------

    public function isCreated(){    return ( $this->state->status === self::STATUS_CREATED ); }
    public function isStarted(){    return ( $this->state->status === self::STATUS_STARTED ); }
    public function isSubmitted(){  return ( $this->state->status === self::STATUS_SUBMITTED ); }
    public function isConfirmed(){  return ( $this->state->status === self::STATUS_CONFIRMED ); }
    public function isCaptured(){   return ( $this->state->status === self::STATUS_CAPTURED ); }

    public function isFailed(){     return ( $this->state->status === self::STATUS_FAILED ); }
    public function isCancelled(){  return ( $this->state->status === self::STATUS_CANCELLED ); }
    public function isError(){      return ( $this->state->status === self::STATUS_ERROR ); }

    //---------------------------

    /**
     * Return the URL to the payment page to which the user needs directing to continue.
     *
     * NULL is returned if a payment page URL is not available.
     *
     * @return Uri|null
     */
    public function getPaymentPageUrl(){

        if( $this->isFinished() || !isset($this['_links']['next_url']['href']) ){
            return null;
        }

        return new Uri( $this['_links']['next_url']['href'] );

    }

}
