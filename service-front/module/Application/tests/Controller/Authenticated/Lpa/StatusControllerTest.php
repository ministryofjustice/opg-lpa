<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\StatusController;
use Application\Listener\LpaLoaderListener;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Session\ContainerNamespace;
use ApplicationTest\Controller\AbstractControllerTestCase;
use DateTime;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\Attributes\DataProvider;

final class StatusControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction(): void
    {
        /** @var StatusController $controller */
        $controller = $this->getController(StatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionWithReturnUnpaid(): void
    {
        /** @var StatusController $controller */
        $controller = $this->getController(StatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found' => true, 'status' => 'Processed', 'returnUnpaid' => true]]);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionInvalidStatus(): void
    {
        /** @var StatusController $controller */
        $controller = $this->getController(StatusController::class);

        $status = "InvalidStatus";
        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found' => true, 'status' => $status]]);

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('user/dashboard', $result->getHeaders()->get('Location')->getUri());
    }

    /**
     * @param $status
     */
    #[DataProvider('statusProvider')]
    public function testIndexActionWithValidStatuses(string $status): void
    {
        /** @var StatusController $controller */
        $controller = $this->getController(StatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found' => true, 'status' => $status]]);

        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }
    public static function statusProvider(): array
    {
        return[
            ['waiting'],
            ['received'],
            ['checking'],
            ['processed'],
            ['completed']
        ];
    }

    public function testIndexActionResultContainsCanGenerateLPA120(): void
    {
        /** @var StatusController $controller */
        $controller = $this->getController(StatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        /** @var ViewModel $result */
        $result = $controller->indexAction();
        $canGenerateLPA120 = $result->getVariable('canGenerateLPA120');

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertFalse($canGenerateLPA120);
    }

    /**
     * Test that the processed date shown on the status page for a single LPA
     * is set by one of the dates returned by Sirius (latest of dispatchDate,
     * withdrawnDate, invalidDate or rejectedDate).
     *
     */
    #[DataProvider('processedDateFixtureProvider')]
    public function testIndexActionProcessedDateGeneration(array $dates, ?string $shouldReceiveByDate): void
    {
        if (!is_null($shouldReceiveByDate)) {
            $shouldReceiveByDate = new DateTime($shouldReceiveByDate);
        }

        $testLpa = clone($this->lpa);
        $testLpaId = $testLpa->id;
        $testLpa->setCompletedAt(new DateTime('2020-03-10'));

        $testLpa->setMetadata(array_merge($testLpa->getMetadata(), $dates));

        // Return "Processed" as the status for the LPA
        $this->lpaApplicationService
            ->shouldReceive('getStatuses')
            ->withArgs([$testLpaId])
            ->andReturn(['found' => true, 'status' => 'Processed']);

        // Mock SessionUtility to return user when requested
        $this->sessionUtility->shouldReceive('getFromMvc')
            ->withArgs([ContainerNamespace::USER_DETAILS, 'user'])
            ->andReturn($this->user)
            ->byDefault();

        // SUT
        $controller = new StatusController(
            $this->formElementManager,
            $this->sessionManagerSupport,
            $this->authenticationService,
            $this->config,
            $this->lpaApplicationService,
            $this->userDetails,
            $this->sessionUtility
        );

        // Set up the event with the LPA
        $event = new MvcEvent();
        $flowChecker = new FormFlowChecker($testLpa);
        $event->setParam(LpaLoaderListener::ATTR_LPA, $testLpa);
        $event->setParam(LpaLoaderListener::ATTR_FLOW_CHECKER, $flowChecker);
        $controller->setEvent($event);

        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);

        $this->assertEquals($result->shouldReceiveByDate, $shouldReceiveByDate);
    }

    public static function processedDateFixtureProvider(): array
    {
        /*
         * Each element in the returned array represents the data for a test
         * case, and consists of 2 elements:
         * 0 - Array of dates associated with this LPA
         * 1 - String representation of expected datetime for the shouldReceiveByDate
         */
        return [
            [
                [], null
            ],

            [
                [
                    'application-rejected-date' => '2020-03-01'
                ],
                '2020-03-20'
            ],

            [
                [
                    'application-withdrawn-date' => '2020-04-01'
                ],
                '2020-04-22'
            ],

            [
                [
                    'application-invalid-date' => '2020-05-01'
                ],
                '2020-05-22'
            ],

            [
                [
                    'application-dispatch-date' => '2020-06-01'
                ],
                '2020-06-22'
            ],

            // this is not at all likely but here for completeness
            [
                [
                    'application-invalid-date' => '2021-05-05',
                    'application-dispatch-date' => '2021-05-07',
                    'application-rejected-date' => '2021-05-06',
                    'application-withdrawn-date' => '2021-05-04',
                ],
                '2021-05-28' // 15 working days after 2021-05-07
            ],
        ];
    }
}
