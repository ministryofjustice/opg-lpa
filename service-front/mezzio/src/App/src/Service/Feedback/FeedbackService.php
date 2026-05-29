<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Feedback\FeedbackValidationException;
use Psr\Log\LoggerInterface;

class FeedbackService
{
    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws FeedbackValidationException
     */
    public function add(array $data): void
    {
        try {
            $this->apiClient->httpPost('/user-feedback', $data);
        } catch (ApiException $ex) {
            $this->logger->warning('Failed to send feedback data to the API', [
                'status' => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            if ($ex->getStatusCode() === 400) {
                throw new FeedbackValidationException($ex->getMessage());
            }

            throw $ex;
        }
    }
}
