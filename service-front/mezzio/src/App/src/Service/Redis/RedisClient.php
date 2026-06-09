<?php

declare(strict_types=1);

namespace App\Service\Redis;

use InvalidArgumentException;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Redis as BaseRedisClient;
use RedisException;

/**
 * Basic save handler to connect, write to and read from Redis.
 *
 * Mezzio port of Application\Model\Service\Redis\RedisClient.
 */
class RedisClient implements LoggerAwareInterface
{
    use LoggerTrait;

    private ?BaseRedisClient $redisClient = null;
    private string $redisHost;
    private int $redisPort = 6379;

    /**
     * TTL for Redis keys, in seconds.
     */
    private int $ttl;

    /**
     * @param string $redisUrl In format tcp://host:port or tls://host:port
     * @param int $ttlMs TTL for Redis keys, in milliseconds
     * @param BaseRedisClient|null $baseRedisClient Client for Redis access
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $redisUrl, int $ttlMs, ?BaseRedisClient $baseRedisClient = null)
    {
        $urlParts = parse_url($redisUrl);

        if (!isset($urlParts['host'])) {
            throw new InvalidArgumentException('Redis hostname could not be parsed from provided URL');
        }
        $this->redisHost = $urlParts['host'];

        if (($urlParts['scheme'] ?? '') === 'tls') {
            $this->redisHost = 'tls://' . $this->redisHost;
        }

        if (isset($urlParts['port'])) {
            $this->redisPort = intval($urlParts['port']);
        }

        // Redis setEx expects TTL in seconds, but this is passed in milliseconds
        $this->ttl = (int) ($ttlMs / 1000);

        $this->redisClient = $baseRedisClient ?? new BaseRedisClient();
    }

    public function open(): bool
    {
        try {
            // suppress PHP warning messages (e.g. unresolvable hostname)
            return @$this->redisClient->connect($this->redisHost, $this->redisPort);
        } catch (RedisException $e) {
            $this->getLogger()->error('Unable to connect to Redis Server', [
                'exception' => $e,
                'host'      => $this->redisHost,
                'port'      => $this->redisPort,
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
        $success = $this->redisClient->setEx($id, $this->ttl, $data);

        return $success !== false;
    }

    public function destroy(string $id): bool
    {
        $this->redisClient->del($id);
        return true;
    }
}
