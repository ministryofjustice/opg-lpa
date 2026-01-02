<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Listener\TermsAndConditionsListener;
use Application\Model\Service\Session\SessionUtility;
use DateTime;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response as MVCResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteStackInterface;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class TermsAndConditionsListenerTest extends TestCase
{
    private array $config;
    private SessionUtility|MockObject $sessionUtility;
    private EventManagerInterface|MockObject $eventManager;
    private AuthenticationService|MockObject $authenticationService;

    public function setUp(): void
    {
        $this->config = ['terms' => ['lastUpdated' => '2000-01-01']];
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->eventManager = $this->createMock(EventManagerInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
    }

    public function testAttach(): void
    {
        $expectedFn = function () {
        };

        $this->eventManager
            ->expects($this->once())
            ->method('attach')
            ->with(
                MvcEvent::EVENT_DISPATCH,
                $this->callback(function ($arg) {
                    return is_array($arg)
                        && count($arg) === 2
                        && $arg[0] instanceof TermsAndConditionsListener
                        && $arg[1] === 'listen';
                }),
                1
            )
            ->willReturn($expectedFn);

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $listener->attach($this->eventManager);
    }

    public function testListenWhenNoIdentity()
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertNull($listener->listen(new MvcEvent()));
    }

    public function testListenWhenTermsAcceptedAndUpToDate(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('2001-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertNull($listener->listen(new MvcEvent()));
    }

    public function testListenWhenTermsAlreadyAccepted(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('1999-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with('TermsAndConditionsCheck')
            ->willReturn(true);

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $this->assertNull($listener->listen(new MvcEvent()));
    }

    public function testListenWhenTermsNotAccepted(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('1999-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with('TermsAndConditionsCheck', 'seen')
            ->willReturn(null);
        $this->sessionUtility
            ->method('setInMvc')
            ->with('TermsAndConditionsCheck', 'seen', true);

        $router = $this->createMock(RouteStackInterface::class);
        $router
            ->method('assemble')
            ->with([], ['name' => 'user/dashboard/terms-changed'])
            ->willReturn('/assembled-url');

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getRouter')
            ->willReturn($router);
        $event
            ->method('getResponse')
            ->willReturn(new MVCResponse());

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $result = $listener->listen($event);

        $expectedResponse = new MVCResponse();
        $expectedResponse->getHeaders()->addHeaderLine('Location', '/assembled-url');
        $expectedResponse->setStatusCode('302');

        $this->assertEquals($expectedResponse, $result);
    }

    public function testProcessWhenNoIdentity()
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenTermsAcceptedAndUpToDate(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('2001-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenTermsAlreadyAccepted(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('1999-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with('TermsAndConditionsCheck')
            ->willReturn(true);

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
        );

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $this->assertEquals($expectedResponse, $listener->process($request, $handler));
    }

    public function testProcessWhenTermsNotAccepted(): void
    {
        $identity = new User('1', 'testoken', 10000, new DateTime('1999-01-01'));

        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($identity);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with('TermsAndConditionsCheck', 'seen')
            ->willReturn(null);
        $this->sessionUtility
            ->method('setInMvc')
            ->with('TermsAndConditionsCheck', 'seen', true);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->method('generate')
            ->with('user/dashboard/terms-changed')
            ->willReturn('/generated-url');

        $listener = new TermsAndConditionsListener(
            $this->config,
            $this->sessionUtility,
            $this->authenticationService,
            $urlHelper,
        );

        $request = new ServerRequest();
        $expectedResponse = new RedirectResponse('/generated-url');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->assertEquals(
            $expectedResponse->getHeaderLine('Location'),
            $listener->process($request, $handler)->getHeaderLine('Location')
        );
    }
}
