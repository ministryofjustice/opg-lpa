<?php

namespace Application\Model\Service\OneLogin;

use GuzzleHttp\Client;

class DiscoveryDocumentFetcher
{
    public function __construct(
        private readonly Client $client,
        private readonly string $discoveryUrl,
    ) {
    }

    /**
     * @throws DiscoveryDocumentFetchException
     */
    public function authorizationEndpoint(): string
    {
        $response = $this->client->get($this->discoveryUrl, ['http_errors' => false]);

        if ($response->getStatusCode() !== 200) {
            throw new DiscoveryDocumentFetchException(sprintf(
                'Discovery document request failed with status %d',
                $response->getStatusCode()
            ));
        }

        $document = json_decode((string) $response->getBody(), true);

        if (!is_array($document)) {
            throw new DiscoveryDocumentFetchException(
                'Discovery document response contained malformed JSON'
            );
        }

        if (empty($document['authorization_endpoint'])) {
            throw new DiscoveryDocumentFetchException(
                'Discovery document is missing authorization_endpoint'
            );
        }

        return $document['authorization_endpoint'];
    }
}
