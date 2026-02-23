<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\Attribute;
use Application\Listener\ViewVariablesListener;
use Application\Model\Service\Authentication\Identity\User as IdentityUser;
use Application\Model\Service\Date\DateService;
use DateTime;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewVariablesListenerTest extends TestCase
{
    private DateService|MockObject $dateService;
    private EventManagerInterface|MockObject $eventManager;

    public function setUp(): void
    {
        $this->dateService = $this->createMock(DateService::class);
        $this->eventManager = $this->createMock(EventManagerInterface::class);
    }

    public function testAttach(): void
    {
        $expectedFn = function () {
        };

        $this->eventManager
            ->expects($this->once())
            ->method('attach')
            ->with(
                MvcEvent::EVENT_RENDER,
                $this->callback(function ($arg) {
                    return is_array($arg)
                        && count($arg) === 2
                        && $arg[0] instanceof ViewVariablesListener
                        && $arg[1] === 'listen';
                }),
                1
            )
            ->willReturn($expectedFn);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $listener->attach($this->eventManager);
    }

    public function testListenWhenResultIsNotViewModel(): void
    {
        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn('string-result');

        $event
            ->expects($this->never())
            ->method('getParam');

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenResultIsJsonModel(): void
    {
        $jsonModel = new JsonModel(['data' => 'test']);

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($jsonModel);

        $event
            ->expects($this->never())
            ->method('getParam');

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $this->assertNull($listener->listen($event));
    }

    public function testListenWhenUserDetailsNotAvailable(): void
    {
        $viewModel = new ViewModel();

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($viewModel);

        $event
            ->method('getParam')
            ->willReturnMap([
                [Attribute::USER_DETAILS, null, null],
                [Attribute::IDENTITY, null, null],
            ]);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $result = $listener->listen($event);

        $this->assertNull($result);
        $this->assertNull($viewModel->getVariable('signedInUser'));
        $this->assertNull($viewModel->getVariable('secondsUntilSessionExpires'));
    }

    public function testListenWhenIdentityNotAvailable(): void
    {
        $viewModel = new ViewModel();
        $userDetails = FixturesData::getUser();

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($viewModel);

        $event
            ->method('getParam')
            ->willReturnMap([
                [Attribute::USER_DETAILS, null, $userDetails],
                [Attribute::IDENTITY, null, null],
            ]);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $result = $listener->listen($event);

        $this->assertNull($result);
        $this->assertNull($viewModel->getVariable('signedInUser'));
        $this->assertNull($viewModel->getVariable('secondsUntilSessionExpires'));
    }

    public function testListenWhenBothUserDetailsAndIdentityAvailable(): void
    {
        $viewModel = new ViewModel();
        $userDetails = FixturesData::getUser();

        $currentTime = new DateTime('2024-01-01 12:00:00');
        $this->dateService
            ->expects($this->once())
            ->method('getToday')
            ->willReturn($currentTime);

        $identity = $this->createMock(IdentityUser::class);
        $identity
            ->method('tokenExpiresAt')
            ->willReturn(new DateTime('2024-01-01 13:00:00')); // 1 hour from current time

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($viewModel);

        $event
            ->method('getParam')
            ->willReturnMap([
                [Attribute::USER_DETAILS, null, $userDetails],
                [Attribute::IDENTITY, null, $identity],
            ]);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $result = $listener->listen($event);

        $this->assertNull($result);
        $this->assertSame($userDetails, $viewModel->getVariable('signedInUser'));

        $secondsUntilExpires = $viewModel->getVariable('secondsUntilSessionExpires');
        $this->assertIsInt($secondsUntilExpires);
        $this->assertEquals(3600, $secondsUntilExpires);
    }

    public function testListenSetsVariablesOnlyOnViewModel(): void
    {
        $viewModel = new ViewModel();
        $userDetails = FixturesData::getUser();

        $currentTime = new DateTime('2024-01-01 12:00:00');
        $this->dateService
            ->expects($this->once())
            ->method('getToday')
            ->willReturn($currentTime);

        $identity = $this->createMock(IdentityUser::class);
        $identity
            ->method('tokenExpiresAt')
            ->willReturn(new DateTime('2024-01-01 13:00:00'));

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($viewModel);

        $event
            ->method('getParam')
            ->willReturnMap([
                [Attribute::USER_DETAILS, null, $userDetails],
                [Attribute::IDENTITY, null, $identity],
            ]);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $this->assertNull($viewModel->getVariable('signedInUser'));
        $this->assertNull($viewModel->getVariable('secondsUntilSessionExpires'));

        $listener->listen($event);

        $this->assertNotNull($viewModel->getVariable('signedInUser'));
        $this->assertNotNull($viewModel->getVariable('secondsUntilSessionExpires'));
    }

    public function testListenCalculatesCorrectSecondsUntilExpiry(): void
    {
        $viewModel = new ViewModel();
        $userDetails = FixturesData::getUser();

        $currentTime = new DateTime('2024-01-01 12:00:00');
        $this->dateService
            ->expects($this->once())
            ->method('getToday')
            ->willReturn($currentTime);

        $identity = $this->createMock(IdentityUser::class);
        $identity
            ->method('tokenExpiresAt')
            ->willReturn(new DateTime('2024-01-01 12:16:40')); // 1000 seconds later

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($viewModel);

        $event
            ->method('getParam')
            ->willReturnMap([
                [Attribute::USER_DETAILS, null, $userDetails],
                [Attribute::IDENTITY, null, $identity],
            ]);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $listener->listen($event);

        $secondsUntilExpires = $viewModel->getVariable('secondsUntilSessionExpires');

        $this->assertEquals(1000, $secondsUntilExpires);
    }

    public function testListenWithExpiredIdentity(): void
    {
        $viewModel = new ViewModel();
        $userDetails = FixturesData::getUser();

        $currentTime = new DateTime('2024-01-01 12:00:00');
        $this->dateService
            ->expects($this->once())
            ->method('getToday')
            ->willReturn($currentTime);

        $identity = $this->createMock(IdentityUser::class);
        $identity
            ->method('tokenExpiresAt')
            ->willReturn(new DateTime('2024-01-01 11:58:20')); // 100 seconds earlier

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($viewModel);

        $event
            ->method('getParam')
            ->willReturnMap([
                [Attribute::USER_DETAILS, null, $userDetails],
                [Attribute::IDENTITY, null, $identity],
            ]);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $listener->listen($event);

        $secondsUntilExpires = $viewModel->getVariable('secondsUntilSessionExpires');

        $this->assertEquals(-100, $secondsUntilExpires);
    }

    public function testListenDoesNotModifyOtherViewModelVariables(): void
    {
        $viewModel = new ViewModel([
            'existingVar' => 'existingValue',
            'anotherVar' => 123,
        ]);

        $currentTime = new DateTime('2024-01-01 12:00:00');
        $this->dateService
            ->expects($this->once())
            ->method('getToday')
            ->willReturn($currentTime);

        $userDetails = FixturesData::getUser();

        $identity = $this->createMock(IdentityUser::class);
        $identity
            ->method('tokenExpiresAt')
            ->willReturn(new DateTime('2024-01-01 13:00:00'));

        $event = $this->createMock(MvcEvent::class);
        $event
            ->method('getResult')
            ->willReturn($viewModel);

        $event
            ->method('getParam')
            ->willReturnMap([
                [Attribute::USER_DETAILS, null, $userDetails],
                [Attribute::IDENTITY, null, $identity],
            ]);

        $listener = new ViewVariablesListener(
            $this->dateService,
        );

        $listener->listen($event);

        $this->assertEquals('existingValue', $viewModel->getVariable('existingVar'));
        $this->assertEquals(123, $viewModel->getVariable('anotherVar'));

        $this->assertSame($userDetails, $viewModel->getVariable('signedInUser'));
        $this->assertNotNull($viewModel->getVariable('secondsUntilSessionExpires'));
    }
}
