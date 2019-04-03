<?php

namespace Application\Model\Service\ProcessingStatus;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Service\AbstractService;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Service extends AbstractService
{
    private const SIRIUS_STATUS_TO_LPA = [
            'Pending' => 'Received',
            'Perfect' => 'Checking',
            'Imperfect' => 'Checking',
            'Invalid' => 'Returned',
            'Rejected' => 'Returned',
            'Withdrawn' => 'Returned',
            'Registered' => 'Returned',
            'Cancelled' => 'Returned',
            'Revoked' => 'Returned'
    ];

    /**
     * @var $httpClient HttpClient
     */
    private $httpClient;

    /**
     * @var $processingStatusServiceUri string
     */
    private $processingStatusServiceUri;


    public function setClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setConfig(array $config)
    {
        if(!isset($config['processing-status']['endpoint'])) {
            throw new RuntimeException("Missing config: ['processing-status']['endpoint']");
        }

        $this->processingStatusServiceUri = $config['processing-status']['endpoint'];
    }

    /**
     * @param $id
     * @return mixed
     * @throws ApiProblemException
     * @throws Exception
     */
    public function getStatus($id)
    {
        if (is_numeric($id)) {
            $id = 'A' . sprintf("%011d", $id);
        }

        $url = new Uri($this->processingStatusServiceUri . $id);

        $request = new Request('GET', $url, $this->buildHeaders());

        $response = $this->httpClient->sendRequest($request);

        switch ($response->getStatusCode()) {
            case 200:
                return $this->handleResponse($response);
            case 404:
                // A 404 represents that details for the passed ID could not be found.
                return null;
            default:
                throw new ApiProblemException($response);
        }
    }

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json'
        ];

        return $headers;
    }

    private function handleResponse(ResponseInterface $response)
    {
            $status = json_decode($response->getBody(), true);

            if (is_null($status)){
                return null;
            }

            //  If the body isn't an array now then it wasn't JSON before
            if (!is_array($status)) {
                throw new ApiProblemException($response, 'Malformed JSON response from server');
            }

            if (!$status['status']) {
                return null;
            }

            return self::SIRIUS_STATUS_TO_LPA[$status['status']];
    }
}
