<?php


namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\StatusController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\DateTime;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;

class StatusControllerTest extends AbstractControllerTest
{
    /**
     * @var $service Service|MockInterface
     */
    private $service;

    /**
     * @var $service ProcessingStatusService|MockInterface
     */
    private $processingStatusService;

    /**
     * @var $statusController StatusController
     */
    private $statusController;

    /**
     * @var $config array
     */
    private $config;


    public function setUp(): void
    {
        parent::setUp();
        $this->service = Mockery::mock(Service::class);
        $this->processingStatusService = Mockery::mock(ProcessingStatusService::class);
        $this->config = ['track-from-date' => '2019-01-01'];

        $this->statusController = new StatusController($this->authorizationService,
            $this->service, $this->processingStatusService, $this->config);
    }

    public function testGetWithFirstUpdateOnValidCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'), 'metadata' => []]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => 'Returned' , 'rejectedDate' => new DateTime('2019-02-11')]
            ]);

        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Returned',
                        'application-receipt-date' => null,
                        'application-registration-date' => null,
                        'application-rejected-date' => new DateTime('2019-02-11')
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true, 'status' => 'Returned','receiptDate' => null, 'registrationDate' => null, 'rejectedDate'  => new DateTime('2019-02-11')
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnValidCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Checking']]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);
        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => 'Returned' , 'rejectedDate' => new DateTime('2019-02-11')]
            ]);
        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Returned',
                        'application-registration-date' => null,
                        'application-receipt-date' => null,
                        'application-rejected-date' => new DateTime('2019-02-11')
                    ]
                ], '98765', '12345'
            ])->once();
        $result = $this->statusController->get('98765');
        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true, 'status' => 'Returned', 'receiptDate' => null, 'registrationDate' => null,'rejectedDate'  => new DateTime('2019-02-11')
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnValidCaseWithSameStatusDateReturn()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Received']]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => 'Received' , 'receiptDate' => new DateTime('2019-02-11')]
            ]);

        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Received',
                        'application-registration-date' => null ,
                        'application-receipt-date' => new DateTime('2019-02-11'),
                        'application-rejected-date' => null
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true, 'status' => 'Received', 'receiptDate'  => new DateTime('2019-02-11'), 'registrationDate' => null, 'rejectedDate' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnValidCaseWithDateReturn()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Checking']]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => 'Checking' , 'registrationDate' => new DateTime('2019-02-11')]
            ]);

        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Checking',
                        'application-registration-date' => new DateTime('2019-02-11'),
                        'application-receipt-date' => null,
                        'application-rejected-date' => null
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true, 'status' => 'Checking','receiptDate' => null,'registrationDate'  => new DateTime('2019-02-11'), 'rejectedDate' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnRejectDateForReturnedCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Returned', Lpa::APPLICATION_REJECTED_DATE => null, Lpa::APPLICATION_REGISTRATION_DATE => null ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => 'Returned' , 'rejectedDate' => new DateTime('2019-02-11')]
            ]);

        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Returned',
                        'application-registration-date' => null,
                        'application-receipt-date' => null,
                        'application-rejected-date' => new DateTime('2019-02-11')
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true, 'status' => 'Returned','receiptDate' => null, 'registrationDate' => null,  'rejectedDate'  => new DateTime('2019-02-11')
                ]
            ]
        ), $result);
    }

    public function testGetWithNoUpdateOnValidCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Checking']]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->once()
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => null,'rejectedDate' => null]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found' => true, 'status' => 'Checking', 'rejectedDate'  => null ]]), $result);
    }

    public function testGetWithNoUpdateOnValidCaseWithNoPreviousStatus()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => []]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->times(1)
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => null,'rejectedDate' => null]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found' => false]]), $result);
    }

    public function testGetWithSameStatus()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Checking']]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => 'Checking','registrationDate' => new DateTime('2019-02-11')]
            ]);

        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Checking',
                        'application-registration-date' => new DateTime('2019-02-11') ,
                        'application-receipt-date' => null,
                        'application-rejected-date' => null
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found' => true, 'status' => 'Checking', 'receiptDate' => null,'registrationDate' => new DateTime('2019-02-11'), 'rejectedDate' => null ]]), $result);
    }

    public function testGetNotFoundInDB()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $this->service->shouldReceive('fetch')->withArgs(['98765', '12345'])
            ->once()
            ->andReturn(new ApiProblem(500, 'Test error'));
        $result = $this->statusController->get('98765');
        $this->assertEquals(new Json(['98765' => ['found' => false]]), $result);
    }

    public function testGetLpaAlreadyReturnedWithRegistrationDateSet()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Returned', Lpa::APPLICATION_REGISTRATION_DATE => new DateTime('2019-02-20')]]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->twice()->andReturn($dataModel);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found'=>true, 'status'=>'Returned']]), $result);
    }

    public function testGetLpaAlreadyReturnedWithRejectedDateSet()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Returned', Lpa::APPLICATION_REJECTED_DATE => new DateTime('2019-02-10')]]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->twice()->andReturn($dataModel);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found'=>true, 'status'=>'Returned']]), $result);
    }

    /**
     * @expectedException Application\Library\ApiProblem\ApiProblemException
     * @expectedExceptionMessage User identifier missing from URL
     */
    public function testNoUserIdPresent()
    {
        $this->statusController->get('98765');
    }

    public function testMultipleStatusUpdateOnValidCases()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => []]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98766', '12345'])
            ->atMost()->times(3)
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => ['status' => 'Returned', 'rejectedDate' => new DateTime('2019-02-11')],
                '98766' => ['status' => 'Received', 'receiptDate' => new DateTime('2019-02-11')]
            ]);

        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Returned',
                        'application-registration-date' => null,
                        'application-receipt-date' => null,
                        'application-rejected-date' => new DateTime('2019-02-11')
                    ]
                ], '98765', '12345'])->once();

        $this->service->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Received',
                        'application-registration-date' => null,
                        'application-receipt-date' => new DateTime('2019-02-11'),
                        'application-rejected-date' => null
                    ]
                ], '98766', '12345'])->once();

        $result = $this->statusController->get('98765,98766');

        $this->assertEquals(new Json([
            98765 => ['found' => true, 'status' => 'Returned', 'receiptDate' => null, 'registrationDate' => null, 'rejectedDate' => new DateTime('2019-02-11')],
            98766 => ['found' => true, 'status' => 'Received', 'receiptDate' => new DateTime('2019-02-11'), 'registrationDate' => null,'rejectedDate' => null]
        ]), $result);
    }
}
