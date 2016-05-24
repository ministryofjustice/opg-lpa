<?php
namespace MinistryOfJustice\PostcodeInfo\Response;

class AddressList extends AbstractData {
    use IncludeResponseTrait;

    public function __construct( Array $details ){
        
        // Map event details to objects.
        $addresses = array_map( function($address){
            return new Address( $address );
        }, $details );

        parent::__construct( $addresses );

    }
}
