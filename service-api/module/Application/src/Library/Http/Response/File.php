<?php
namespace Application\Library\Http\Response;

use Zend\Http\Response;

/**
 * Returns an Empty (204) response. Used for a response after a DELETE.
 *
 * Class NoContent
 * @package Application\Library\Http\Response
 */
class File extends Response {

    const TYPE_PDF = 'application/pdf';

    protected $contentType;

    public function __construct( $data, $contentType ){
        $this->setContent( $data );
        $this->contentType = $contentType;
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

        $headers->clearHeaders()
            ->addHeaderLine( 'Content-Type', $this->contentType)
            ->addHeaderLine( 'Content-Disposition', 'attachment')
            ->addHeaderLine( 'Content-Length', strlen( $this->getContent() ) );

        return $headers;
    }

} // class
