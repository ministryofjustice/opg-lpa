<?php

namespace Application\Model\Service\Session;

use MakeLogger\Logging\LoggerTrait;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use InvalidArgumentException;
use Redis;
use RedisException;

/**
 * Custom save handler to which write filters can be applied.
 * If any filter in the chain returns FALSE, the session
 * will not be written.
 * Typically, filters are closures which inspect the incoming
 * request, environment etc. to determine whether the session
 * should be written after execution of an action.
 */
class FilteringSaveHandler implements SaveHandlerInterface
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

    /**
     * Array of closures, called in order to determine
     * whether to write a session or not.
     */
    /** @var array */
    private $filters = [];

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
     * @param array $filters Filters to assign
     * @param Redis $client Client for Redis access
     */
    public function __construct(string $redisUrl, int $ttlMs, $filters = [], $redis = null)
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

        if (!empty($filters)) {
            $this->filters = $filters;
        }

        if (is_null($redis)) {
            $redis = new Redis();
        }
        $this->redisClient = $redis;
    }

    /**
     * Add a filter to the chain. Filters in the chain
     * are checked in the order they were added.
     *
     * @param callable $closure Closure which returns TRUE
     * (session should be written) or FALSE (ignore the write
     * for this session)
     */
    public function addFilter(callable $closure)
    {
        $this->filters[] = $closure;
        return $this;
    }

    // $savePath and $sessionName are ignored
    public function open(string $savePath, string $sessionName): bool
    {
        $result = false;

        try {
            // this will throw a RedisException if the Redis server is unavailable
            $result = $this->redisClient->connect($this->redisHost, $this->redisPort);
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
     * Filtered session write. If any filter in the chain returns FALSE,
     * the session is not written. We still return TRUE so that PHP's
     * session machinery knows that the save handler has done its job.
     */
    public function write(string $id, string $data): bool
    {
        // Ignore writes if any filter returns FALSE
        $doWrite = true;
        foreach ($this->filters as $_ => $filter) {
            if (!$filter()) {
                $doWrite = false;
                break;
            }
        }

        $key = $this->getKey($id);

        if ($doWrite) {
            // This appears to return a Redis instance, not a boolean; so we
            // check that here so we always get a boolean.
            $success = $this->redisClient->setEx($key, $this->ttl, $data);
            if ($success !== false) {
                $success = true;
            }

            return $success;
        } else {
            return true;
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
