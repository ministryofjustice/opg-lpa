<?php

namespace Application\Model\Service\Redis;

use MakeShared\Logging\LoggerTrait;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Redis;
use RedisException;

/**
 * Basic save handler to connect, write to and read from Redis
 */
class RedisClient implements LoggerAwareInterface
{
    use LoggerTrait;

    /** @var Redis */
    private $redisClient;

    /** @var string */
    private $redisHost;

    /** @var int */
    private $redisPort = 6379;

    /**
     * TTL for Redis keys, in milliseconds
     */
    /** @var int */
    private $ttl;

    /**
     * Constructor
     *
     * @param string $redisUrl In format tcp://host:port or tls://host:port
     * @param int $ttlMs TTL for Redis keys, in milliseconds
     * @param Redis $client Client for Redis access
     * @throw InvalidArgumentException
     */
    public function __construct(string $redisUrl, int $ttlMs, $redis = null)
    {
        $urlParts = parse_url($redisUrl);

        if (!isset($urlParts['host'])) {
            throw new InvalidArgumentException('Redis hostname could not be parsed from provided URL');
        }
        $this->redisHost = $urlParts['host'];

        if ($urlParts['scheme'] === 'tls') {
            $this->redisHost = 'tls://' . $this->redisHost;
        }

        if (isset($urlParts['port'])) {
            $this->redisPort = intval($urlParts['port']);
        }

        # Redis' setEx expects TTL in seconds, but this is passed to the constructor in milliseconds
        $this->ttl = $ttlMs / 1000;

        if (is_null($redis)) {
            $redis = new Redis();
        }
        $this->redisClient = $redis;
    }

    public function open(): bool
    {
        $result = false;

        try {
            // this will throw a RedisException if the Redis server is unavailable;
            // the '@' suppresses PHP warning messages, e.g. if the Redis server's
            // domain name cannot be resolved (in this case, an exception is still thrown)
            $result = @$this->redisClient->connect($this->redisHost, $this->redisPort);
        } catch (RedisException $e) {
            $this->getLogger()->error('Unable to connect to Redis Server', [
                'error_code' => 'CONNECTION_TO_REDIS_FAILED',
                'status' => $e->getStatusCode(),
                'exception' => $e,
                'host' => $this->redisHost,
                'port' => $this->redisPort
            ]);

            $result = false;
        }

        return $result;
    }

    public function close(): bool
    {
        return $this->redisClient->close();
    }

    public function read(string $id): string
    {
        $data = $this->redisClient->get($id);

        // Redis returns FALSE if a key doesn't exist, but
        // PHP expects an empty string to be returned in that situation
        if ($data === false) {
            $data = '';
        }

        return $data;
    }

    /**
     * Perform a write of the keys and data
     */
    public function write(string $id, string $data): bool
    {
        // This appears to return a Redis instance, not a boolean; so we
        // check that here so we always get a boolean.
        $success = $this->redisClient->setEx($id, $this->ttl, $data);
        if ($success !== false) {
            return true;
        }

        return false;
    }

    public function destroy(string $id): bool
    {
        $this->redisClient->del($id);
        return true;
    }
}
