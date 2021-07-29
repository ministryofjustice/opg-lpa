<?php
namespace Application\Model\Service\RedisClient;

use InvalidArgumentException;
use Redis;
use function intval;
use function parse_url;


/**
 * Wrapper round the raw Redis client.
 *
 * TODO TTL for tokens
 */
class RedisClient {
    /**
     * @var Redis
     */
    private $redis;

    /**
     * Redis host
     * @var string
     */
    private $redisHost;

    /**
     * Redis port (6379 is the Redis distro default)
     * @var string
     */
    private $redisPort = 6379;

    /**
     * Constructor
     * @param $redisUrl In format (tcp|tls)://host:port; a valid URL must be supplied,
     * containing at least a hostname; if the scheme is 'tls', TLS is enabled on
     * connections
     * @param Redis $redis Redis client instance; if not set, a new one is instantiated
     */
    public function __construct(string $redisUrl, Redis $redis = null)
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

        if (is_null($redis)) {
            $redis = new Redis();
        }
        $this->redis = $redis;
    }

    // returns TRUE if connection made successfully, FALSE otherwise;
    // will throw RedisException if unable to connect
    private function connect(): bool
    {
        return $this->redis->connect($this->redisHost, $this->redisPort);
    }

    // returns TRUE if connection closed successfully, FALSE otherwise
    private function close(): bool
    {
        return $this->redis->close();
    }

    /**
     * Set the user's CSRF token in Redis.
     *
     * @param string $key Key to store value under in Redis
     * @param string $value Value to store
     * @throws RedisException (if unable to connect to server)
     * @return bool TRUE if key set successfully in Redis, FALSE otherwise
     */
    public function set(string $key, string $value): bool
    {
        $setOk = FALSE;

        $operationOk = $this->connect();

        if ($operationOk) {
            $setOk = $this->redis->set($key, $value);
            $operationOk = $this->close();
        }

        return $operationOk && $setOk;
    }

    /**
     * Get the current value for a key from Redis.
     *
     * @param string $key Key to retrieve
     * @throws RedisException (if unable to connect to server)
     * @return string Value found in Redis, or null if key was not found or an
     * error occurred which wasn't due to server connection failure
     */
    public function get(string $key): ?string
    {
        $value = null;

        $operationOk = $this->connect();

        if ($operationOk) {
            // Will be FALSE if key was not found (e.g. if expired)
            $value = $this->redis->get($key);

            $operationOk = $this->close();
        }

        if (!($value && $operationOk)) {
            $value = null;
        }

        return $value;
    }
}