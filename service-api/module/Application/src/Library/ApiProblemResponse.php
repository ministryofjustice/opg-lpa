<?php

declare(strict_types=1);

namespace Application\Library;

use Laminas\Http\Headers;
use Laminas\Http\Response;
use function json_encode;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Represents an ApiProblem response payload.
 */
class ApiProblemResponse extends Response
{
    /** @var ApiProblem */
    protected $apiProblem;

    /**
     * Flags to use with json_encode.
     *
     * @var int
     */
    protected $jsonFlags;

    public function __construct(ApiProblem $apiProblem)
    {
        $this->apiProblem = $apiProblem;
        $this->setCustomStatusCode($apiProblem->status);

        if ($apiProblem->title !== null) {
            $this->setReasonPhrase($apiProblem->title);
        }

        $this->jsonFlags = JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;
    }

    /**
     * @return ApiProblem
     */
    public function getApiProblem()
    {
        return $this->apiProblem;
    }

    /**
     * Retrieve the content.
     *
     * Serializes the composed ApiProblem instance to JSON.
     *
     * @return string
     */
    public function getContent()
    {
        return json_encode($this->apiProblem->toArray(), $this->jsonFlags);
    }

    /**
     * Retrieve headers.
     *
     * Proxies to parent class, but then checks if we have an content-type
     * header; if not, sets it, with a value of "application/problem+json".
     *
     * @return Headers
     */
    public function getHeaders()
    {
        $headers = parent::getHeaders();
        if (! $headers->has('content-type')) {
            $headers->addHeaderLine('content-type', ApiProblem::CONTENT_TYPE);
        }

        return $headers;
    }

    /**
     * Override reason phrase handling.
     *
     * If no corresponding reason phrase is available for the current status
     * code, return "Unknown Error".
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        if (! empty($this->reasonPhrase)) {
            return $this->reasonPhrase;
        }

        if (isset($this->recommendedReasonPhrases[$this->statusCode])) {
            return $this->recommendedReasonPhrases[$this->statusCode];
        }

        return 'Unknown Error';
    }
}