<?php

declare(strict_types=1);

namespace App\Service\Session;

use App\Service\Redis\RedisClient;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Redis;

class FilteringSaveHandler implements SaveHandlerInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public const SESSION_PREFIX = 'PHPREDIS_SESSION:';

    private RedisClient $redisClient;

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
