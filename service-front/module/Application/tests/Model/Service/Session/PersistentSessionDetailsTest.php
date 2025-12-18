<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\SessionUtility;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Application\Model\Service\Session\PersistentSessionDetails;
use Laminas\Router\RouteMatch;
use Mockery;
use PHPUnit\Framework\TestCase;

class PersistentSessionDetailsTest extends TestCase
{
    private MockInterface|RouteMatch $routeMatch;
    private MockInterface|SessionUtility $sessionUtility;

    public function setUp(): void
    {
        $this->routeMatch = Mockery::mock(RouteMatch::class);

        $this->sessionUtility = Mockery::mock(SessionUtility::class);
    }

    #[DoesNotPerformAssertions]
    #[Test]
    public function testExpectedValuesFromCurrentAndPreviousRoutes(): void
    {
        $currentRoute = 'lpa/applicant';

        $this->routeMatch
            ->shouldReceive('getMatchedRouteName')
            ->andReturn($currentRoute);

        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('SessionDetails', 'currentRoute', 'lpa/applicant');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('SessionDetails', 'routeStore')
            ->andReturn('/example');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('SessionDetails', 'previousRoute')
            ->andReturn('/example');
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('SessionDetails', 'routeStore', 'lpa/applicant');

        new PersistentSessionDetails($this->routeMatch, $this->sessionUtility);
    }

    #[DoesNotPerformAssertions]
    #[Test]
    public function testPreviousRouteSetToRouteStoreWhenDoesNotMatch(): void
    {
        $currentRoute = 'lpa/applicant';

        $this->routeMatch
            ->shouldReceive('getMatchedRouteName')
            ->andReturn($currentRoute);

        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('SessionDetails', 'currentRoute', 'lpa/applicant');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('SessionDetails', 'routeStore')
            ->andReturn('/example/1');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('SessionDetails', 'previousRoute')
            ->andReturn('/example/2');
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('SessionDetails', 'previousRoute', '/example/1');
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('SessionDetails', 'routeStore', 'lpa/applicant');

        new PersistentSessionDetails($this->routeMatch, $this->sessionUtility);
    }

    #[DoesNotPerformAssertions]
    #[Test]
    public function testEmptyValuesFromCurrentRoute(): void
    {
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('SessionDetails', 'currentRoute', '');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('SessionDetails', 'routeStore')
            ->andReturn('/example');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('SessionDetails', 'previousRoute')
            ->andReturn('/example');
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('SessionDetails', 'routeStore', '');

        $persistentSession = new PersistentSessionDetails(null, $this->sessionUtility);
    }
}
