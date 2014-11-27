<?php
namespace Application\Library\Hal;

use InvalidArgumentException;

use Nocarrier\Hal as NocarrierHal;

class Hal extends NocarrierHal {

    /**
     * Returns the Hal encoded content as either JSON or XML.
     *
     * @param string $format Either 'json' or 'xml'.
     * @return string JSON or XML
     */
    public function getContent( $format ){

        if( 'json' == $format ){

            return $this->asJson( true );

        } elseif( 'xml' == $format ) {

            return $this->asXml( true );

        } else {

            throw new InvalidArgumentException( 'Invalid format requested' );

        }

    } // function

} // class
