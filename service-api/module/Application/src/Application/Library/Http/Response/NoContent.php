<?php
namespace Application\Library\Http\Response;

use Zend\Http\Response;

/**
 * Returns an Empty (204) response. Used for a response after a DELETE.
 *
 * Class NoContent
 * @package Application\Library\Http\Response
 */
class NoContent extends Response {

    public function __construct(){
        $this->setStatusCode( self::STATUS_CODE_204 );
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
            $headers->addHeaderLine('content-type', 'application/hal');
        }

        return $headers;
    }

} // class
