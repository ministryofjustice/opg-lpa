<?php

declare(strict_types=1);

namespace ApplicationTest\Library\Authentication\Identity;

use Application\Library\Authentication\Identity\AdminService;
use PHPUnit\Framework\TestCase;

class AdminServiceTest extends TestCase
{
    public function testAdminServiceDefaultRole(): void
    {
        $adminService = new AdminService();

        self::assertEquals([0 => 'admin-service'], $adminService->getRoles());
    }
}
