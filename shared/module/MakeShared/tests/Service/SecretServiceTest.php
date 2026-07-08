<?php

declare(strict_types=1);

namespace MakeSharedTest\Service;

use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;
use MakeShared\Service\SecretService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use RuntimeException;

class SecretServiceTest extends MockeryTestCase
{
    private function mockClient(?string $secretValue = null): SecretsManagerClient
    {
        $result = new Result($secretValue !== null ? ['SecretString' => $secretValue] : []);
        $mock = Mockery::mock(SecretsManagerClient::class);
        $mock->shouldReceive('getSecretValue')->andReturn($result);
        TestableSecretService::$mockClient = $mock;
        return $mock;
    }

    public function testNullArnThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No secret ARN configured');

        SecretService::resolve(null);
    }

    public function testReturnsSecretStringFromSecretsManager(): void
    {
        $this->mockClient('my-secret-value');

        $result = TestableSecretService::resolve('arn:aws:secretsmanager:eu-west-1:123:secret:my-secret');

        $this->assertSame('my-secret-value', $result);
    }

    public function testThrowsWhenSecretStringIsEmpty(): void
    {
        $this->mockClient('');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Secrets Manager returned empty value for ARN');

        TestableSecretService::resolve('arn:aws:secretsmanager:eu-west-1:123:secret:empty');
    }

    public function testThrowsWhenSecretStringIsNull(): void
    {
        $this->mockClient(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Secrets Manager returned empty value for ARN: arn:aws:secretsmanager:eu-west-1:123:secret:null-secret');

        TestableSecretService::resolve('arn:aws:secretsmanager:eu-west-1:123:secret:null-secret');
    }

    public function testUsesNoHardcodedRegion(): void
    {
        $this->mockClient('val');

        TestableSecretService::resolve('arn:aws:secretsmanager:eu-west-1:123:secret:x');

        $this->assertArrayNotHasKey('region', TestableSecretService::$lastConfig);
        $this->assertArrayNotHasKey('endpoint', TestableSecretService::$lastConfig);
    }

    public function testPassesEndpointWhenProvided(): void
    {
        $this->mockClient('val');

        TestableSecretService::resolve(
            arn: 'arn:aws:secretsmanager:eu-west-1:123:secret:x',
            endpoint: 'http://localstack:4566'
        );

        $this->assertSame('http://localstack:4566', TestableSecretService::$lastConfig['endpoint']);
    }
}
