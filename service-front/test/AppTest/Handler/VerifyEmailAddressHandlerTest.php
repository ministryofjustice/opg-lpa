<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\VerifyEmailAddressHandler;
use App\Service\UserDetails;
use App\View\Twig\FlashMessenger;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VerifyEmailAddressHandlerTest extends TestCase
{
    private UserDetails&MockObject $userService;
    private SessionInterface&MockObject $session;
    private FlashMessagesInterface&MockObject $flash;
    private VerifyEmailAddressHandler $handler;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserDetails::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->flash = $this->createMock(FlashMessagesInterface::class);

        $this->handler = new VerifyEmailAddressHandler(
            $this->userService,
        );
    }

    private function createRequestWithToken(?string $token): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $this->flash);

        if ($token !== null) {
            $request = $request->withAttribute('token', $token);
        }

        return $request;
    }

    public function testSessionIsClearedAndRegenerated(): void
    {
        $request = $this->createRequestWithToken('validtoken');

        $this->session
            ->expects($this->once())
            ->method('clear');

        $this->session
            ->expects($this->once())
            ->method('regenerate');

        $this->userService->method('updateEmailUsingToken')->willReturn(true);

        $this->handler->handle($request);
    }

    public function testSuccessfulVerificationShowsSuccessMessage(): void
    {
        $token = 'validtoken123';
        $request = $this->createRequestWithToken($token);

        $this->userService
            ->expects($this->once())
            ->method('updateEmailUsingToken')
            ->with($token)
            ->willReturn(true);

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(
                FlashMessenger::SUCCESS,
                ['Your email address was successfully updated. Please login with your new address.']
            );

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testFailedVerificationShowsErrorMessage(): void
    {
        $token = 'invalidtoken';
        $request = $this->createRequestWithToken($token);

        $this->userService
            ->expects($this->once())
            ->method('updateEmailUsingToken')
            ->with($token)
            ->willReturn(false);

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(
                FlashMessenger::ERROR,
                ['There was an error updating your email address']
            );

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testNullTokenShowsError(): void
    {
        $request = $this->createRequestWithToken(null);

        $this->userService
            ->expects($this->never())
            ->method('updateEmailUsingToken');

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(
                FlashMessenger::ERROR,
                ['There was an error updating your email address']
            );

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testEmptyTokenShowsError(): void
    {
        $request = $this->createRequestWithToken('');

        $this->userService
            ->expects($this->never())
            ->method('updateEmailUsingToken');

        $this->flash
            ->expects($this->once())
            ->method('flash')
            ->with(
                FlashMessenger::ERROR,
                ['There was an error updating your email address']
            );

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }
}
