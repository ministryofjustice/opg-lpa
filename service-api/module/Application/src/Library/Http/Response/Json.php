<?php

namespace Application\Library\Http\Response;

use Zend\Http\Headers;
use Zend\Http\Response;

/**
 * Class Json
 * @package Application\Library\Http\Response
 */
class Json extends Response
{
    /**
     * @param array $content
     * @param int $code
     */
    public function __construct(array $content, $code = self::STATUS_CODE_200)
    {
        $this->setContent($content);
        $this->setStatusCode($code);

        $headers = new Headers();
        $headers->addHeaderLine('content-type', 'application/json');
        $this->setHeaders($headers);
    }

    /**
     * Get the body of the response in a JSON string
     *
     * @return string
     */
    public function getContent()
    {
        return json_encode($this->content);
    }
}
