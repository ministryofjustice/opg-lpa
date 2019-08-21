<?php

namespace Application\Model\Service\ApiClient\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ApiException extends RuntimeException
{
    /**
     * @var array
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
        $this->body = json_decode($response->getBody(), true);

        //  If no message was provided create one from the response data
        if (is_null($message)) {
            //  Try to get the message from the details section of the body
            $message = $this->getBodyData('detail');

            //  If there is still no message then compose a standard message
            if (is_null($message)) {
                $message = 'HTTP:' . $response->getStatusCode() . ' - ' . (is_array($this->body) ? print_r($this->body, true) : 'Unexpected API response');
            }
        }

        parent::__construct($message, $response->getStatusCode());
    }

    /**
     * @return mixed|null
     */
    public function getTitle()
    {
        return $this->getBodyData('title');
    }

    /**
     * Returns additional data from the API error response
     *
     * @param string|null $key
     * @return array|mixed
     */
    public function getData(string $key = null)
    {
        $data = $this->getBodyData('data');

        if (!is_null($key) && is_array($data) && isset($data[$key])) {
            $data = $data[$key];
        }

        return $data;
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
