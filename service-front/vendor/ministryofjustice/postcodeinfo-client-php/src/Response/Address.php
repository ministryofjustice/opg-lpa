<?php
namespace MinistryOfJustice\PostcodeInfo\Response;

class Address extends AbstractData {

    public function __construct( Array $details ){

        // If we have point data...
        if( isset($details['point']) ){
            // Wrap it in a Point object.
            $details['point'] = new Point( $details['point'] );
        }

        parent::__construct( $details );

    }

}
