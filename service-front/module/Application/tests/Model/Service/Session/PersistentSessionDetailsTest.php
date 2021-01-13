<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\PersistentSessionDetails;
use Laminas\Router\RouteMatch;
use Mockery;
use PHPUnit\Framework\TestCase;

class PersistentSessionDetailsTest extends TestCase {

    /**
     * @test
     */
    public function testSuccessfullyCreateClass() {

        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn('lpa/applicant');

        $persistentSession = new PersistentSessionDetails($routeMatch);

        $this->assertInstanceOf(PersistentSessionDetails::class, $persistentSession);
    }

    /**
     * @test
     */
    public function testExpectedValuesFromCurrentAndPreviousRoutes() {
        $currentRoute = 'lpa/applicant';

        $routeMatch = Mockery::mock(RouteMatch::class);
        $routeMatch->shouldReceive('getMatchedRouteName')->andReturn($currentRoute);

        $persistentSession = new PersistentSessionDetails($routeMatch);

        $this->assertEquals($currentRoute, $persistentSession->getCurrentRoute());
        $this->assertEquals($currentRoute, $persistentSession->getPreviousRoute());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function testEmptyValuesFromCurrentRoute() {
        $persistentSession = new PersistentSessionDetails(null);

        $this->assertEquals('', $persistentSession->getCurrentRoute());
    }


}
