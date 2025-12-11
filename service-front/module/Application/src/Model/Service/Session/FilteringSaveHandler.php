<?php

namespace Application\Model\Service\Session;

use Application\Model\Service\Redis\RedisClient;
use MakeShared\Logging\LoggerTrait;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Redis;

/**
 * Custom save handler to which write filters can be applied.
 * If any filter in the chain returns FALSE, the session
 * will not be written.
 * Typically, filters are closures which inspect the incoming
 * request, environment etc. to determine whether the session
 * should be written after execution of an action.
 */
class FilteringSaveHandler implements SaveHandlerInterface, LoggerAwareInterface
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

    /** @var RedisClient */
    private $redisClient;

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
     * @param RedisClient $redisClient Client for Redis access
     * @param array $filters Filters to assign
     */
    public function __construct($redisClient, $filters = [])
    {
        $this->filters = $filters;
        $this->redisClient = $redisClient;
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

    // $path and $name are ignored
    public function open(string $path, string $name): bool
    {
        return $this->redisClient->open();
    }

    public function close(): bool
    {
        return $this->redisClient->close();
    }

    public function read(string $id): string|false
    {
        $key = $this->getKey($id);
        $value = $this->redisClient->read($key);

        return $value instanceof Redis ? false : $value;
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

        if ($doWrite) {
            $key = $this->getKey($id);
            return $this->redisClient->write($key, $data);
        }

        return true;
    }

    public function destroy(string $id): bool
    {
        return $this->redisClient->destroy($this->getKey($id));
    }

    // no-op, as we let Redis clean up expired keys and rely on TTL
    public function gc(int $max_lifetime): int|false
    {
        return 1;
    }
}
