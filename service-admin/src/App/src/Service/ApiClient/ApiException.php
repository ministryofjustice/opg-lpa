<?php

namespace App\Service\ApiClient;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ApiException extends RuntimeException
{
    /**
     * JSON-decoded body
     *
     * @var mixed
     */
    private $body;

    /**
     * ApiException constructor
     *
     * @param ResponseInterface $response
     * @param string|null $message
     */
    public function __construct(ResponseInterface $response, string $message = null)
    {
        $this->body = json_decode(strval($response->getBody()), true);

        //  If no message was provided create one from the response data
        if (is_null($message)) {
            //  Try to get the message from the details section of the body
            $message = $this->getBodyData('detail');

            //  If there is still no message then compose a standard message
            if (is_null($message)) {
                $message = 'HTTP:' . $response->getStatusCode() . ' - Unexpected API response';
            }
        }

        parent::__construct($message, $response->getStatusCode());
    }

    /**
     * Get data from the body if it exists
     *
     * @param string $key
     * @return mixed|null
     */
    private function getBodyData(string $key)
    {
        if (is_array($this->body) && isset($this->body[$key])) {
            return $this->body[$key];
        }

        return null;
    }
}
