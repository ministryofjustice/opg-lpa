<?php
namespace MinistryOfJustice\PostcodeInfo\Response;

use ArrayObject;

class PostcodeInfo extends AbstractData {
    use IncludeResponseTrait;

    public function __construct( Array $details ){

        // If we have point data...
        if( isset($details['centre']) ){
            // Wrap it in a Point object.
            $details['centre'] = new Point( $details['centre'] );
        }

        // Map other details to objects.
        $details = array_map( function($item){
            return ( is_array($item) ) ? new ArrayObject( $item, ArrayObject::ARRAY_AS_PROPS ) : $item;
        }, $details );


        parent::__construct( $details );

    }
    
}
