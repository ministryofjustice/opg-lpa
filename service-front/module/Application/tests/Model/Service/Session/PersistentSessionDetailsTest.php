<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use PHPUnit\Framework\Attributes\Test;
use Application\Model\Service\Session\PersistentSessionDetails;
use Laminas\Router\RouteMatch;
use Mockery;
use PHPUnit\Framework\TestCase;

final class PersistentSessionDetailsTest extends TestCase {

    #[Test]
    public function testSuccessfullyCreateClass() {

        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn('lpa/applicant');

        $persistentSession = new PersistentSessionDetails($routeMatch);

        $this->assertInstanceOf(PersistentSessionDetails::class, $persistentSession);
    }

    #[Test]
    public function testExpectedValuesFromCurrentAndPreviousRoutes() {
        $currentRoute = 'lpa/applicant';

        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn($currentRoute);

        $persistentSession = new PersistentSessionDetails($routeMatch);

        $this->assertEquals($currentRoute, $persistentSession->getCurrentRoute());
        $this->assertEquals($currentRoute, $persistentSession->getPreviousRoute());
    }

    #[Test]
    public function testExpectedValuesFromCurrentAndPreviousRoutesPersists() {
        $currentRoute = 'lpa/primary-attorney/add';
        $previousRoute = 'lpa/applicant';

        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn($previousRoute)->once();

        $persistentSession = new PersistentSessionDetails($routeMatch);

        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn($currentRoute)->once();
        $persistentSession = new PersistentSessionDetails($routeMatch);

        $this->assertEquals($currentRoute, $persistentSession->getCurrentRoute());
        $this->assertEquals($previousRoute, $persistentSession->getPreviousRoute());
    }

    #[Test]
    public function testEmptyValuesFromCurrentRoute() {
        $persistentSession = new PersistentSessionDetails(null);

        $this->assertEquals('', $persistentSession->getCurrentRoute());
    }


}
