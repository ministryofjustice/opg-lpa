<?php

namespace Application\Model\Service\Redis;

use MakeShared\Logging\LoggerTrait;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use InvalidArgumentException;
use Redis;
use RedisException;

/**
 * Basic save handler to connect, write to and read from Redis
 */
class RedisHandler implements SaveHandlerInterface
{
    use LoggerTrait;

    /**
     * Prefix for session keys in Redis; this ensures parity
     * with the prefix used by the stock Redis save handler
     * so that sessions should not be lost in the transition
     * between the stock handler and this one. NB phpredis
     * doesn't appear to define a constant for this.
     */
    /** @var string */
    public const SESSION_PREFIX = 'PHPREDIS_SESSION:';

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

    // generate a session ID key for Redis
    private function getKey($id)
    {
        return self::SESSION_PREFIX . $id;
    }

    /**
     * Constructor
     *
     * @param string $redisUrl In format tcp://host:port or tls://host:port
     * @param int $ttlMs TTL for Redis keys, in milliseconds
     * @param Redis $client Client for Redis access
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

        $this->ttl = $ttlMs;

        if (is_null($redis)) {
            $redis = new Redis();
        }
        $this->redisClient = $redis;
    }

    // $savePath and $sessionName are ignored
    public function open(string $savePath, string $sessionName): bool
    {
        $result = false;

        try {
            // this will throw a RedisException if the Redis server is unavailable;
            // the '@' suppresses PHP warning messages, e.g. if the Redis server's
            // domain name cannot be resolved (in this case, an exception is still thrown)
            $result = @$this->redisClient->connect($this->redisHost, $this->redisPort);
        } catch (RedisException $e) {
            $this->getLogger()->err(sprintf(
                'Unable to connect to Redis server at %s:%s',
                $this->redisHost,
                $this->redisPort
            ));
            $this->getLogger()->err($e->getMessage());
            $result = false;
        }

        return $result;
    }

    public function close(): bool
    {
        return $this->redisClient->close();
    }

    public function read(string $id): string|false
    {
        $key = $this->getKey($id);
        $data = $this->redisClient->get($key);

        // Redis returns FALSE if a key doesn't exist, but
        // PHP expects an empty string to be returned in that situation
        if ($data === false || !is_string($data)) {
            $data = '';
        }

        return $data;
    }

    /**
     * Perform a write of the keys and data
     */
    public function write(string $id, string $data): bool
    {
        $key = $this->getKey($id);

        // This appears to return a Redis instance, not a boolean; so we
        // check that here so we always get a boolean.
        $success = $this->redisClient->setEx($key, $this->ttl, $data);
        if ($success !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        $this->redisClient->del($this->getKey($id));
        return true;
    }

    // no-op, as we let Redis clean up expired keys and rely on TTL
    public function gc(int $max_lifetime): int|false
    {
        return 1;
    }
}
