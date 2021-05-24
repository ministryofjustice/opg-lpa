<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;
use Application\Controller\Authenticated\DashboardController;
use Application\Controller\Authenticated\Lpa\StatusController;
use ApplicationTest\Controller\AbstractControllerTest;
use ApplicationTest\Controller\Authenticated\TestableDashboardController;
use DateTime;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Response;
use Laminas\Session\Container;

class StatusControllerTest extends AbstractControllerTest
{
    public function testIndexAction()
    {
        /** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found' => true, 'status' => 'Waiting']]);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testIndexActionInvalidStatus()
    {/** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $status = "InvalidStatus";
        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found'=>true, 'status'=>$status]]);

        $response = new Response();
        $this->redirect->shouldReceive('toRoute')->withArgs(['user/dashboard'])->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @param $status
     * @dataProvider  statusProvider
     */
    public function testIndexActionWithValidStatuses($status)
    {
        /** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found'=>true, 'status'=>$status]]);

        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);

    }
    public function statusProvider()
    {
        return[
            ['waiting'],
            ['received'],
            ['checking'],
            ['processed'],
            ['completed']
        ];
    }

    public function testIndexActionResultContainsCanGenerateLPA120()
    {
        /** @var StatusController $controller */
        $controller = $this->getController(TestableStatusController::class);

        $this->lpaApplicationService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['91333263035' => ['found'=>true, 'status'=>'Waiting']]);

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
     * @dataProvider processedDateFixtureProvider
     */
    public function testIndexActionProcessedDateGeneration($dates, $shouldReceiveByDate)
    {
        if (!is_null($shouldReceiveByDate)) {
            $shouldReceiveByDate = new DateTime($shouldReceiveByDate);
        }

        $testLpa = clone($this->lpa);
        $testLpaId = $testLpa->id;
        $testLpa->setCompletedAt(new DateTime('2020-03-10'));

        // Set one or more dates so we can check in the view that the correct
        // "should receive by" date is given.
        $testLpa->setMetadata(array_merge($testLpa->getMetadata(), $dates));


        $this->lpaApplicationService
             ->shouldReceive('getApplication')
             ->withArgs([$testLpaId])
             ->andReturn($testLpa);

        // Return "Processed" as the status for the LPA
        $this->lpaApplicationService
             ->shouldReceive('getStatuses')
             ->withArgs([$testLpaId])
             ->andReturn(['found' => true, 'status' => 'Processed']);

        $userDetailsSession = new Container();
        $userDetailsSession->user = $this->user;

        // SUT
        $controller = new StatusController(
            $testLpaId,
            $this->formElementManager,
            $this->sessionManager,
            $this->authenticationService,
            $this->config,
            $userDetailsSession,
            $this->lpaApplicationService,
            $this->userDetails,
            $this->replacementAttorneyCleanup,
            $this->metadata
        );

        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);

        // Test the "should receive by date" has been calculated correctly
        $this->assertEquals($result->shouldReceiveByDate, $shouldReceiveByDate);
    }

    public function processedDateFixtureProvider()
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
