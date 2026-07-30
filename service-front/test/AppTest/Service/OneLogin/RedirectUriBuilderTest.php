<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\RedirectUriBuilder;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;

class RedirectUriBuilderTest extends TestCase
{
    public function testBuildsUriFromRequestWhenNoBaseUrlConfigured(): void
    {
        $builder = new RedirectUriBuilder();
        $uri     = new Uri('https://service.example.com/auth/onelogin');

        $this->assertSame('https://service.example.com/auth/redirect', $builder($uri));
    }

    public function testConfiguredBaseUrlOverridesRequestUri(): void
    {
        $builder = new RedirectUriBuilder('https://production.example.gov.uk');
        $uri     = new Uri('http://localhost:7002/auth/onelogin');

        $this->assertSame('https://production.example.gov.uk/auth/redirect', $builder($uri));
    }

    public function testTrailingSlashOnBaseUrlIsTrimmed(): void
    {
        $builder = new RedirectUriBuilder('https://production.example.gov.uk/');
        $uri     = new Uri('https://irrelevant.example.com/');

        $this->assertSame('https://production.example.gov.uk/auth/redirect', $builder($uri));
    }

    public function testPortIsPreservedInRequestDerivedUri(): void
    {
        $builder = new RedirectUriBuilder();
        $uri     = new Uri('https://localhost:7002/some/path');

        $this->assertSame('https://localhost:7002/auth/redirect', $builder($uri));
    }

    public function testHttpSchemeIsPreservedWhenNoBaseUrl(): void
    {
        $builder = new RedirectUriBuilder();
        $uri     = new Uri('http://local.dev/something');

        $this->assertSame('http://local.dev/auth/redirect', $builder($uri));
    }
}
