<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Laminas\Session\SaveHandler\SaveHandlerInterface;

final class FakeSaveHandler implements SaveHandlerInterface
{
    public bool $opened = false;

    public function open($savePath, $name): bool
    {
        $this->opened = true;
        return true;
    }
    public function close(): bool
    {
        return true;
    }
    public function read($id): string|false
    {
        return "";
    }
    public function write($id, $data): bool
    {
        return true;
    }
    public function destroy($id): bool
    {
        return true;
    }
    public function gc($maxlifetime): int|false
    {
        return 1;
    }
}
