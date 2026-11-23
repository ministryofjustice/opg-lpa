<?php

declare(strict_types=1);

namespace App\Service\Redis;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Redis as BaseRedisClient;
use RedisException;
use SensitiveParameter;

class RedisClient
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly int $keyTtlSeconds,
        private readonly BaseRedisClient $redisClient,
        private readonly string $host,
        private readonly int $port,
        #[SensitiveParameter] private readonly string $authToken,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function open(): bool
    {
        try {
            // suppress PHP warning messages (e.g. unresolvable hostname)
            if (!@$this->redisClient->connect($this->host, $this->port)) {
                return false;
            }

            return $this->redisClient->auth($this->authToken);
        } catch (RedisException $e) {
            $this->logger->error('Unable to connect to Redis Server', [
                'exception' => $e,
                'host'      => $this->host,
                'port'      => $this->port,
            ]);

            return false;
        }
    }

    public function close(): bool
    {
        return $this->redisClient->close();
    }

    public function read(string $id): string|BaseRedisClient
    {
        $data = $this->redisClient->get($id);

        // Redis returns FALSE if a key doesn't exist
        if ($data === false) {
            $data = '';
        }

        return $data;
    }

    public function write(string $id, string $data): bool
    {
        $success = $this->redisClient->setEx($id, $this->keyTtlSeconds, $data);

        return $success !== false;
    }

    public function destroy(string $id): bool
    {
        $this->redisClient->del($id);
        return true;
    }
}
