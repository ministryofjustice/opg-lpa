<?php

declare(strict_types=1);

namespace App\Service\Session;

use App\Service\Redis\RedisClient;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Redis;

/**
 * Custom save handler to which write filters can be applied.
 * If any filter in the chain returns FALSE, the session will not be written.
 *
 * Mezzio port of Application\Model\Service\Session\FilteringSaveHandler.
 */
class FilteringSaveHandler implements SaveHandlerInterface, LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * Prefix for session keys in Redis; this ensures parity with the prefix
     * used by the stock Redis save handler so sessions are not lost in the
     * transition between the stock handler and this one.
     */
    public const SESSION_PREFIX = 'PHPREDIS_SESSION:';

    private RedisClient $redisClient;

    /**
     * Array of closures, called in order to determine whether to write a session or not.
     */
    private array $filters;

    private function getKey(string $id): string
    {
        return self::SESSION_PREFIX . $id;
    }

    /**
     * @param RedisClient $redisClient Client for Redis access
     * @param array $filters Filters to assign
     */
    public function __construct(RedisClient $redisClient, array $filters = [])
    {
        $this->filters     = $filters;
        $this->redisClient = $redisClient;
    }

    /**
     * Add a filter to the chain. Filters in the chain are checked in the order they were added.
     */
    public function addFilter(callable $closure): self
    {
        $this->filters[] = $closure;
        return $this;
    }

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
        $key   = $this->getKey($id);
        $value = $this->redisClient->read($key);

        return $value instanceof Redis ? false : $value;
    }

    /**
     * Filtered session write. If any filter in the chain returns FALSE, the session
     * is not written. We still return TRUE so PHP's session machinery knows the save
     * handler has done its job.
     */
    public function write(string $id, string $data): bool
    {
        $doWrite = true;
        foreach ($this->filters as $filter) {
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
