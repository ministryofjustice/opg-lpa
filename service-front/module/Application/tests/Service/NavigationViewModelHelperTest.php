<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Service\NavigationViewModelHelper;
use Application\View\Model\NavigationViewModel;
use DateTime;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NavigationViewModelHelperTest extends TestCase
{
    private SessionUtility|MockObject $sessionUtility;
    private LpaApplicationService|MockObject $lpaApplicationService;
    private NavigationViewModelHelper $helper;

    public function setUp(): void
    {
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);

        $this->helper = new NavigationViewModelHelper(
            $this->sessionUtility,
            $this->lpaApplicationService,
        );
    }

    public function testBuildWhenUserNotLoggedIn(): void
    {
        $currentRoute = 'home';

        $this->sessionUtility
            ->expects($this->exactly(2))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => null,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => false,
                    default => null,
                };
            });

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('getLpaSummaries');

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertFalse($result->userLoggedIn);
        $this->assertEquals('', $result->name);
        $this->assertNull($result->lastLoginAt);
        $this->assertEquals($currentRoute, $result->route);
        $this->assertFalse($result->hasOneOrMoreLPAs);
    }

    public function testBuildWhenUserLoggedInWithFullDetails(): void
    {
        $currentRoute = 'user/dashboard';
        $user = FixturesData::getUser();

        $this->sessionUtility
            ->expects($this->exactly(3))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($user) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => $user,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => true,
                    default => null,
                };
            });

        $this->sessionUtility
            ->expects($this->once())
            ->method('hasInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->never())
            ->method('getLpaSummaries');

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
        $this->assertEquals('Chris Smith', $result->name);
        $this->assertInstanceOf(DateTime::class, $result->lastLoginAt);
        $this->assertEquals($currentRoute, $result->route);
        $this->assertTrue($result->hasOneOrMoreLPAs);
    }

    public function testBuildWhenUserLoggedInWithoutName(): void
    {
        $currentRoute = 'user/dashboard';
        $user = $this->createMock(User::class);
        $user
            ->method('getName')
            ->willReturn(null);
        $user
            ->method('getLastLoginAt')
            ->willReturn(null);

        $lpasSummaries = ['total' => 0, 'applications' => []];

        $this->sessionUtility
            ->expects($this->exactly(3))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($user) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => $user,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => false,
                    default => null,
                };
            });

        $this->sessionUtility
            ->expects($this->once())
            ->method('hasInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->willReturn($lpasSummaries);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs', false);

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
        $this->assertEquals('', $result->name);
        $this->assertNull($result->lastLoginAt);
        $this->assertEquals($currentRoute, $result->route);
        $this->assertFalse($result->hasOneOrMoreLPAs);
    }

    public function testBuildWhenUserLoggedInAndNeedsToCheckLpas(): void
    {
        $currentRoute = 'user/dashboard';
        $user = FixturesData::getUser();
        $lpasSummaries = ['total' => 5, 'applications' => []];

        $this->sessionUtility
            ->expects($this->exactly(2))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($user) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => $user,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => null,
                    default => null,
                };
            });

        $this->sessionUtility
            ->expects($this->once())
            ->method('hasInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs')
            ->willReturn(false);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->willReturn($lpasSummaries);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs', true);

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
        $this->assertEquals('Chris Smith', $result->name);
        $this->assertEquals($currentRoute, $result->route);
    }

    public function testBuildWhenUserLoggedInAndHasNoLpas(): void
    {
        $currentRoute = 'user/dashboard';
        $user = FixturesData::getUser();
        $lpasSummaries = ['total' => 0, 'applications' => []];

        $this->sessionUtility
            ->expects($this->exactly(2))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($user) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => $user,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => null,
                    default => null,
                };
            });

        $this->sessionUtility
            ->expects($this->once())
            ->method('hasInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs')
            ->willReturn(false);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->willReturn($lpasSummaries);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs', false);

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
        $this->assertEquals('Chris Smith', $result->name);
        $this->assertEquals($currentRoute, $result->route);
        $this->assertFalse($result->hasOneOrMoreLPAs);
    }

    public function testBuildWhenUserLoggedInAndLpasSummariesMissingTotal(): void
    {
        $currentRoute = 'user/dashboard';
        $user = FixturesData::getUser();
        $lpasSummaries = ['applications' => []];

        $this->sessionUtility
            ->expects($this->exactly(2))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($user) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => $user,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => null,
                    default => null,
                };
            });

        $this->sessionUtility
            ->expects($this->once())
            ->method('hasInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs')
            ->willReturn(false);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->willReturn($lpasSummaries);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs', false);

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
        $this->assertFalse($result->hasOneOrMoreLPAs);
    }

    public function testBuildWhenUserLoggedInAndHasOneOrMoreLpasCachedAsFalse(): void
    {
        $currentRoute = 'user/dashboard';
        $user = FixturesData::getUser();
        $lpasSummaries = ['total' => 3, 'applications' => []];

        $this->sessionUtility
            ->expects($this->exactly(3))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($user) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => $user,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => false,
                    default => null,
                };
            });

        $this->sessionUtility
            ->expects($this->once())
            ->method('hasInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs')
            ->willReturn(true);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getLpaSummaries')
            ->willReturn($lpasSummaries);

        $this->sessionUtility
            ->expects($this->once())
            ->method('setInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs', true);

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
    }

    public function testBuildWithDifferentRoutes(): void
    {
        $routes = [
            'some/route',
            'another/route',
        ];

        foreach ($routes as $route) {
            $sessionUtility = $this->createMock(SessionUtility::class);
            $sessionUtility
                ->method('getFromMvc')
                ->willReturnCallback(function ($namespace, $key) {
                    return match ([$namespace, $key]) {
                        [ContainerNamespace::USER_DETAILS, 'user'] => null,
                        [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => false,
                        default => null,
                    };
                });

            $helper = new NavigationViewModelHelper(
                $sessionUtility,
                $this->lpaApplicationService,
            );

            $result = $helper->build($route);

            $this->assertEquals($route, $result->route);
            $this->assertInstanceOf(NavigationViewModel::class, $result);
        }
    }

    public function testBuildPreservesLastLoginAtFromUser(): void
    {
        $currentRoute = 'user/dashboard';
        $lastLoginAt = new DateTime('2024-01-15 10:30:00');

        $user = $this->createMock(User::class);
        $userName = new Name(['first' => 'John', 'last' => 'Doe']);
        $user
            ->method('getName')
            ->willReturn($userName);
        $user
            ->method('getLastLoginAt')
            ->willReturn($lastLoginAt);

        $this->sessionUtility
            ->expects($this->exactly(3))
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($user) {
                return match ([$namespace, $key]) {
                    [ContainerNamespace::USER_DETAILS, 'user'] => $user,
                    [ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs'] => true,
                    default => null,
                };
            });

        $this->sessionUtility
            ->expects($this->once())
            ->method('hasInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs')
            ->willReturn(true);

        $result = $this->helper->build($currentRoute);

        $this->assertSame($lastLoginAt, $result->lastLoginAt);
    }
}
