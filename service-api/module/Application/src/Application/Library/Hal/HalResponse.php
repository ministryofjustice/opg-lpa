<?php
namespace Application\Library\Hal;

use InvalidArgumentException;

use Zend\Http\Response;

/**
 * Based on ZF\ApiProblem\ApiProblemResponse
 *
 * Class HalResponse
 * @package Application\Library\Http
 */
class HalResponse extends Response {

    /**
     * @var Hal The Hal document.
     */
    protected $hal;

    /**
     * @var string The desired output formal. Either 'json' or 'xml'.
     */
    protected $format;

    /**
     * @var array The supported formats.
     */
    protected $formats = [
        'xml' => 'application/hal+xml',
        'json' => 'application/hal+json',
    ];


    /**
     * @param Hal $hal A Hal document
     * @param string $format Either 'json' or 'xml'.
     */
    public function __construct( Hal $hal, $format ){

        if( !isset( $this->formats[$format] ) ){
            throw new InvalidArgumentException( 'Invalid format requested' );
        }

        $this->hal = $hal;
        $this->format = $format;

    } // function


    /**
     * Retrieve the content
     *
     * Serializes the composed Hal Document instance to JSON or XML.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->hal->getContent( $this->format );
    }


    /**
     * Retrieve headers
     *
     * Proxies to parent class, but then checks if we have an content-type
     * header; if not, sets it, with the correct value.
     *
     * @return \Zend\Http\Headers
     */
    public function getHeaders()
    {
        $headers = parent::getHeaders();
        if (!$headers->has('content-type')) {
            $headers->addHeaderLine('content-type', $this->formats[$this->format]);
        }
        return $headers;
    }


} // class
