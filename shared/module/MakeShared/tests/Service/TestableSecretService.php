<?php

declare(strict_types=1);

namespace MakeSharedTest\Service;

use Aws\SecretsManager\SecretsManagerClient;
use MakeShared\Service\SecretService;

/**
 * Testable subclass that injects a mock SecretsManagerClient.
 */
class TestableSecretService extends SecretService
{
    public static SecretsManagerClient $mockClient;
    public static array $lastConfig = [];

    protected static function createClient(array $config): SecretsManagerClient
    {
        static::$lastConfig = $config;
        return static::$mockClient;
    }
}
