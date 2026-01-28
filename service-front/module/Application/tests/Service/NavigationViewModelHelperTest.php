<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Service\NavigationViewModelHelper;
use Application\View\Model\NavigationViewModel;
use DateTime;
use MakeShared\DataModel\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NavigationViewModelHelperTest extends TestCase
{
    private SessionUtility|MockObject $sessionUtility;
    private NavigationViewModelHelper $helper;

    public function setUp(): void
    {
        $this->sessionUtility = $this->createMock(SessionUtility::class);

        $this->helper = new NavigationViewModelHelper(
            $this->sessionUtility,
        );
    }

    public function testBuildWhenUserNotLoggedIn(): void
    {
        $currentRoute = 'home';

        $this->sessionUtility
            ->expects($this->once())
            ->method('getFromMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user')
            ->willReturn(null);

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
        $user = new User([
            'id' => 'test-user-id',
            'createdAt' => '2024-01-01T00:00:00.000Z',
            'updatedAt' => '2024-01-01T00:00:00.000Z',
            'lastLoginAt' => '2024-01-15T10:30:00.000Z',
            'name' => [
                'title' => 'Mr',
                'first' => 'Chris',
                'last' => 'Smith',
            ],
            'numberOfLpas' => 1,
        ]);

        $this->sessionUtility
            ->expects($this->once())
            ->method('getFromMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user')
            ->willReturn($user);

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
        $user = new User([
            'id' => 'test-user-id',
            'createdAt' => '2024-01-01T00:00:00.000Z',
            'updatedAt' => '2024-01-01T00:00:00.000Z',
            'numberOfLpas' => 0,
        ]);

        $this->sessionUtility
            ->expects($this->once())
            ->method('getFromMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user')
            ->willReturn($user);

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
        $this->assertEquals('', $result->name);
        $this->assertNull($result->lastLoginAt);
        $this->assertEquals($currentRoute, $result->route);
        $this->assertFalse($result->hasOneOrMoreLPAs);
    }

    public static function lpaCountProvider(): array
    {
        return [
            'zero LPAs' => [0, false],
            'one LPA' => [1, true],
            'multiple LPAs' => [5, true],
        ];
    }

    /**
     * @dataProvider lpaCountProvider
     */
    public function testBuildWithDifferentLpaCounts(int $numberOfLpas, bool $expectedHasLpas): void
    {
        $currentRoute = 'user/dashboard';
        $user = new User([
            'id' => 'test-user-id',
            'createdAt' => '2024-01-01T00:00:00.000Z',
            'updatedAt' => '2024-01-01T00:00:00.000Z',
            'name' => [
                'title' => 'Mr',
                'first' => 'Chris',
                'last' => 'Smith',
            ],
            'numberOfLpas' => $numberOfLpas,
        ]);

        $this->sessionUtility
            ->expects($this->once())
            ->method('getFromMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user')
            ->willReturn($user);

        $result = $this->helper->build($currentRoute);

        $this->assertInstanceOf(NavigationViewModel::class, $result);
        $this->assertTrue($result->userLoggedIn);
        $this->assertEquals('Chris Smith', $result->name);
        $this->assertEquals($currentRoute, $result->route);
        $this->assertEquals($expectedHasLpas, $result->hasOneOrMoreLPAs);
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
                ->with(ContainerNamespace::USER_DETAILS, 'user')
                ->willReturn(null);

            $helper = new NavigationViewModelHelper($sessionUtility);

            $result = $helper->build($route);

            $this->assertEquals($route, $result->route);
            $this->assertInstanceOf(NavigationViewModel::class, $result);
        }
    }

    public function testBuildPreservesLastLoginAtFromUser(): void
    {
        $currentRoute = 'user/dashboard';
        $lastLoginAt = new DateTime('2024-01-15T10:30:00.000Z');
        $user = new User([
            'id' => 'test-user-id',
            'createdAt' => '2024-01-01T00:00:00.000Z',
            'updatedAt' => '2024-01-01T00:00:00.000Z',
            'lastLoginAt' => $lastLoginAt->format('Y-m-d\TH:i:s.v\Z'),
            'name' => [
                'title' => 'Mr',
                'first' => 'John',
                'last' => 'Doe',
            ],
            'numberOfLpas' => 1,
        ]);

        $this->sessionUtility
            ->expects($this->once())
            ->method('getFromMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user')
            ->willReturn($user);

        $result = $this->helper->build($currentRoute);

        $this->assertEquals($lastLoginAt->format('Y-m-d H:i:s'), $result->lastLoginAt->format('Y-m-d H:i:s'));
    }
}
