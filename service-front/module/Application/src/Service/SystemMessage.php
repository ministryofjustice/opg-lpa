<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Adapter\DynamoDbKeyValueStore;

use function is_string;
use function trim;

class SystemMessage
{
    public function __construct(
        private readonly DynamoDbKeyValueStore $cache,
    ) {
    }

    public function fetchSanitised(): ?string
    {
        $message = $this->cache->getItem('system-message');

        if (!is_string($message)) {
            return null;
        }

        $message = trim($message);

        if ($message === '') {
            return null;
        }

        return $message;
    }
}
