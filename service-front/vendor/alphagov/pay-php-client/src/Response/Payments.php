<?php
namespace Alphagov\Pay\Response;

use Alphagov\Pay\Exception;

class Payments extends AbstractData {
    use IncludeResponseTrait;

    public function __construct( Array $details ){

        if( !isset($details['results']) || !is_array($details['results']) ){
            throw new Exception\UnexpectedValueException( "Payments response missing 'results' key" );
        }

        // Map event details to objects.
        $events = array_map( function($event){
            return new Payment( $event );
        }, $details['results'] );

        parent::__construct( $events );

    }

}
