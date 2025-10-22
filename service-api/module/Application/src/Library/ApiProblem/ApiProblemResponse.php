<?php

declare(strict_types=1);

namespace Application\Library\ApiProblem;

use Laminas\Http\Headers;
use Laminas\Http\Response;

use function json_encode;

use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class ApiProblemResponse extends Response
{
    /**
     * Flags to use with json_encode.
     */
    protected int $jsonFlags;

    public function __construct(protected readonly ApiProblem $apiProblem)
    {
        $this->setCustomStatusCode($apiProblem->status);

        if ($apiProblem->title !== null) {
            $this->setReasonPhrase($apiProblem->title);
        }

        $this->jsonFlags = JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;
    }

    /**
     * Serializes the composed ApiProblem instance to JSON.
     */
    public function getContent(): string|false
    {
        return json_encode($this->apiProblem->toArray(), $this->jsonFlags);
    }

    /**
     * Retrieve headers.
     *
     * Proxies to parent class, but then checks if we have an content-type
     * header; if not, sets it, with a value of "application/problem+json".
     */
    public function getHeaders(): Headers
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
     */
    public function getReasonPhrase(): string
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
