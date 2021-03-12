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
            ['returned'],
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
     * Test that the return date shown on the status page for a single LPA
     * is set by one of the dates returned by Sirius (registrationDate,
     * withdrawnDate, invalidDate or rejectedDate)
     *
     * @param $metadataFields Fields which should be set in the metadata
     * for the LPA, which are subsequently used to set the returnDate in the
     * view (what we want to test). Array of
     *
     *     [<fieldName> => <date to set field to as string>, ...]
     *
     * @param expectedDateTime string expected
     *
     * @dataProvider metadataFieldNamesProvider
     */
    public function testIndexActionReturnedDateGeneration($metadataFields, $expectedDateTime)
    {
        $testLpa = clone($this->lpa);
        $testLpaId = $testLpa->id;
        $testLpa->setCompletedAt(new DateTime('2021-03-10'));

        // This is the field we're testing: set dates so we can check
        // in the view that the correct return date is given.
        array_walk($metadataFields, function ($dateString, $fieldName) {
            $metadataFields[$fieldName] = new DateTime($dateString);
        });
        $testLpa->setMetadata(array_merge($testLpa->getMetadata(), $metadataFields));

        $this->lpaApplicationService
             ->shouldReceive('getApplication')
             ->withArgs([$testLpaId])
             ->andReturn($testLpa);

        // Return "Returned" as the status for the LPA
        $this->lpaApplicationService
             ->shouldReceive('getStatuses')
             ->withArgs([$testLpaId])
             ->andReturn(['found' => true, 'status' => 'Returned']);

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

        // Test what's in the view: has the right date been selected
        // as the return date?
        $this->assertEquals($expectedDateTime, $result->returnDate);
    }

    public function metadataFieldNamesProvider()
    {
        return [
            [['application-rejected-date' => '2020-03-01'], '2020-03-01'],
            [['application-withdrawn-date' => '2020-04-01'], '2020-04-01'],
            [['application-invalid-date' => '2020-05-01'], '2020-05-01'],
            [['application-registration-date' => '2020-06-01'], '2020-06-01'],
        ];
    }
}
