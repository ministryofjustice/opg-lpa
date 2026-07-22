<?php

namespace ApplicationTest\Controller;

use Application\Controller\FeedbackController;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Feedback\Service as FeedbackService;
use DateTime;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Mvc\Controller\PluginManager;
use Application\Library\Authentication\Identity\User;
use Laminas\Authentication\AuthenticationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class FeedbackControllerTest extends MockeryTestCase
{
    /**
     * @var FeedbackController
     */
    private $controller;

    /**
     * @var FeedbackService|MockInterface
     */
    private $feedbackService;

    /**
     * @var AuthenticationService|MockInterface
     */
    private $authenticationService;

    private LoggerInterface&MockInterface $logger;

    /**
     * @var PluginManager|MockInterface
     */
    private $pluginManager;


    public function setUp(): void
    {
        $this->feedbackService = Mockery::mock(FeedbackService::class);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->controller = new FeedbackController($this->feedbackService, $this->authenticationService, $this->logger);

        $this->pluginManager = Mockery::mock(PluginManager::class);
        $this->pluginManager->shouldReceive('setController');
        $this->controller->setPluginManager($this->pluginManager);
    }

    public function testWithDateRange()
    {
        $parameters = [
            'to' => '2018-06-01',
            'from' => '2018-09-01',
        ];

        $paramsPlugin = Mockery::mock(Params::class);
        $paramsPlugin->shouldReceive('__invoke')->andReturn($paramsPlugin);
        $paramsPlugin->shouldReceive('fromQuery')->andReturn($parameters);

        $this->pluginManager->shouldReceive('get')->andReturn($paramsPlugin);

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(Mockery::mock(User::class));

        //----------------------------------

        $this->feedbackService->shouldReceive('get');
        $this->feedbackService->shouldReceive('getPruneDate')->andReturn(new DateTime());


        $result = $this->controller->getList();

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testWithoutDateRange()
    {
        $paramsPlugin = Mockery::mock(Params::class);
        $paramsPlugin->shouldReceive('__invoke')->andReturn($paramsPlugin);
        $paramsPlugin->shouldReceive('fromQuery')->andReturn([]);

        $this->pluginManager->shouldReceive('get')->andReturn($paramsPlugin);

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(Mockery::mock(User::class));

        //----------------------------------

        $result = $this->controller->getList();

        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testWithoutAuthorisation()
    {
        $paramsPlugin = Mockery::mock(Params::class);
        $paramsPlugin->shouldReceive('__invoke')->andReturn($paramsPlugin);
        $paramsPlugin->shouldReceive('fromQuery')->andReturn([]);

        $this->pluginManager->shouldReceive('get')->andReturn($paramsPlugin);

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null);

        //----------------------------------

        $result = $this->controller->getList();

        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    public function testCreateWithNoData()
    {
        $this->feedbackService->shouldReceive('add')->andReturn(false);

        $this->logger->shouldReceive('error')->once()->with('Data required for database insert was missing');

        $result = $this->controller->create([]);

        $this->assertInstanceOf(ApiProblemResponse::class, $result);
    }

    public function testCreateWithData()
    {
        $this->feedbackService->shouldReceive('add')->andReturn(true);

        $result = $this->controller->create([
            'details' => 'feedback message',
        ]);

        $this->assertInstanceOf(NoContent::class, $result);
    }
}
