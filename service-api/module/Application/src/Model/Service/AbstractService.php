<?php

namespace Application\Model\Service;

use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

abstract class AbstractService implements LoggerAwareInterface
{
    use LoggerTrait;

    protected function log(string $level, string $message, array $context = []): void
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Validates the LPA and throws a RuntimeException with detailed logging if invalid.
     *
     * @param Lpa $lpa
     * @param string $context Description of what operation triggered the validation (e.g. "after setting donor")
     * @throws RuntimeException
     */
    protected function assertLpaValid(Lpa $lpa, string $context = 'unknown operation'): void
    {
        $validation = $lpa->validate();

        if ($validation->hasErrors()) {
            $validationErrors = $validation->getArrayCopy();

            $this->log('debug', 'LPA validation failed: ' . $context, [
                'lpaid' => $lpa->id,
                'validation_errors' => $validationErrors,
                'context' => $context,
                'lpa_type' => $lpa->getDocument() ? $lpa->getDocument()->getType() : null,
            ]);

            throw new RuntimeException(sprintf(
                'A malformed LPA object (%s). LPA ID: %s. Validation errors: %s',
                $context,
                $lpa->id,
                json_encode($validationErrors)
            ));
        }
    }
}
