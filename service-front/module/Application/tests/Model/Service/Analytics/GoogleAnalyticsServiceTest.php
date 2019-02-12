<?php

namespace ApplicationTest\Model\Service\Analytics;

use Application\Model\Service\Analytics\GoogleAnalyticsService;
use Application\Model\Service\Authentication\AuthenticationService;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use TheIconic\Tracking\GoogleAnalytics\Analytics;
use TheIconic\Tracking\GoogleAnalytics\AnalyticsResponse;

class GoogleAnalyticsServiceTest extends MockeryTestCase
{
    /**
     * @var $googleAnalyticsService GoogleAnalyticsService|MockInterface
     */
    private $googleAnalyticsService;

    protected function setUp() : void
    {
        parent::setUp();

        /** @var $authenticationService AuthenticationService */
        $authenticationService = Mockery::mock(AuthenticationService::class);

        $this->googleAnalyticsService = new GoogleAnalyticsService($authenticationService, []);
    }

    protected function tearDown()
    {
        parent::tearDown();

        if (isset($_COOKIE['_ga'])) {
            unset($_COOKIE['_ga']);
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Could not find google analytics cookie
     */
    public function testGetClientIdWithNoCookieSet() : void
    {
        $this->googleAnalyticsService->getAnalyticsClientId();
    }

    /**
     * @throws Exception
     */
    public function testGetClientIdWithValidCookieSet() : void
    {
        $_COOKIE['_ga'] = 'padding.more-padding.12345678.87654321';

        $clientId = $this->googleAnalyticsService->getAnalyticsClientId();

        $this->assertEquals('12345678.87654321', $clientId);
    }

    /**
     * @throws Exception
     */
    public function testSendPageViewSuccess() : void
    {
        $_COOKIE['_ga'] = 'padding.more-padding.12345678.87654321';

        /** @var $analyticsClient Analytics|MockInterface */
        $analyticsClient = Mockery::mock(Analytics::class);
        $analyticsClient->expects('setProtocolVersion')->withArgs(['1'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setTrackingId')->withArgs(['UA-33184303-1'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setClientId')->withArgs(['12345678.87654321'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setDocumentHostName')->withArgs(['host name'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setDocumentPath')->withArgs(['test/view/path'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setDocumentTitle')->withArgs(['Test Title'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setAnonymizeIp')->withArgs([true])->andReturn($analyticsClient)->once();

        $analyticsResponse = Mockery::mock(AnalyticsResponse::class);
        $analyticsResponse->expects('getHttpStatusCode')->andReturn(200)->once();

        $analyticsClient->expects('sendPageview')->andReturn($analyticsResponse)->once();

        $this->googleAnalyticsService->setAnalyticsClient($analyticsClient);

        $this->googleAnalyticsService->sendPageView('host name', 'test/view/path', 'Test Title');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Test Exception
     */
    public function testSendPageViewFailed() : void
    {
        $_COOKIE['_ga'] = 'padding.more-padding.12345678.87654321';

        /** @var $analyticsClient Analytics|MockInterface */
        $analyticsClient = Mockery::mock(Analytics::class);
        $analyticsClient->expects('setProtocolVersion')->withArgs(['1'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setTrackingId')->withArgs(['UA-33184303-1'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setClientId')->withArgs(['12345678.87654321'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setDocumentHostName')->withArgs(['host name'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setDocumentPath')->withArgs(['test/view/path'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setDocumentTitle')->withArgs(['Test Title'])->andReturn($analyticsClient)->once();
        $analyticsClient->expects('setAnonymizeIp')->withArgs([true])->andReturn($analyticsClient)->once();

        $exception = new Exception('Test Exception');
        $analyticsClient->expects('sendPageview')->andThrowExceptions([$exception])->once();

        $this->googleAnalyticsService->setAnalyticsClient($analyticsClient);

        $this->googleAnalyticsService->sendPageView('host name', 'test/view/path', 'Test Title');
    }
}
