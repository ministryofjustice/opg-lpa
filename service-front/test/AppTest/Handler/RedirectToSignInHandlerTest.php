<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\RedirectToSignInHandler;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

use function getenv;
use function putenv;

class RedirectToSignInHandlerTest extends TestCase
{
    private string|false $oneLoginEnabled;

    protected function setUp(): void
    {
        $this->oneLoginEnabled = getenv('ONELOGIN_ENABLED');
    }

    protected function tearDown(): void
    {
        putenv($this->oneLoginEnabled === false ? 'ONELOGIN_ENABLED' : 'ONELOGIN_ENABLED=' . $this->oneLoginEnabled);
    }

    public function testRedirectsToTheOneLoginSignInPage(): void
    {
        putenv('ONELOGIN_ENABLED=true');

        $response = (new RedirectToSignInHandler())->handle(new ServerRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/home', $response->getHeaderLine('Location'));
    }
}
