<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\StatusController;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\MillisecondDateTime;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Lpa\Lpa;
use Psr\Log\LoggerInterface;

class StatusControllerTest extends AbstractControllerTestCase
{
    /**
     * @var $service ApplicationsService|MockInterface
     */
    private $applicationsService;

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
        $this->applicationsService = Mockery::mock(ApplicationsService::class);
        $this->processingStatusService = Mockery::mock(ProcessingStatusService::class);
        $this->config = ['track-from-date' => '2019-01-01'];

        $this->statusController = new StatusController(
            $this->authorizationService,
            $this->applicationsService,
            $this->processingStatusService,
            $this->config
        );
        $logger = Mockery::spy(LoggerInterface::class);
        $this->statusController->setLogger($logger);
    }

    public function testGetWithFirstUpdateOnValidCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => []]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => [
                        'status' => 'Processed',
                        'rejectedDate' => new MillisecondDateTime('2019-02-11'),
                        'returnUnpaid' => null
                    ]
                ]
            ]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Processed',
                        'application-receipt-date' => null,
                        'application-registration-date' => null,
                        'application-rejected-date' => new MillisecondDateTime('2019-02-11'),
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true,
                    'status' => 'Processed',
                    'returnUnpaid' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnValidCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Received']]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => [
                        'status' => 'Checking',
                        'receiptDate' => new MillisecondDateTime('2019-02-11'),
                        'returnUnpaid' => null
                    ]
                ]
            ]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Checking',
                        'application-registration-date' => null,
                        'application-receipt-date' => new MillisecondDateTime('2019-02-11'),
                        'application-rejected-date' => null,
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true,
                    'status' => 'Checking',
                    'returnUnpaid' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnValidCaseWithSameStatusDifferentReturnDate()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Received']]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Received',
                        'application-registration-date' => null,
                        'application-receipt-date' => new MillisecondDateTime('2019-02-11'),
                        'application-rejected-date' => null,
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'
            ])->once();

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => [
                        'status' => 'Received',
                        'receiptDate' => new MillisecondDateTime('2019-02-11'),
                        'returnUnpaid' => null
                    ]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true,
                    'status' => 'Received',
                    'returnUnpaid' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnValidCaseWithDateReturn()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Received']]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => [
                        'status' => 'Checking',
                        'registrationDate' => new MillisecondDateTime('2019-02-11'),
                        'returnUnpaid' => null
                    ]
                ]
            ]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Checking',
                        'application-registration-date' => new MillisecondDateTime('2019-02-11'),
                        'application-receipt-date' => null,
                        'application-rejected-date' => null,
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true,
                    'status' => 'Checking',
                    'returnUnpaid' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesOnRejectDateForProcessedCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Waiting',
                Lpa::APPLICATION_REJECTED_DATE => null,
                Lpa::APPLICATION_REGISTRATION_DATE => null
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Processed' , 'rejectedDate' => new MillisecondDateTime('2019-02-11')]
                ]
            ]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Processed',
                        'application-registration-date' => null,
                        'application-receipt-date' => null,
                        'application-rejected-date' => new MillisecondDateTime('2019-02-11'),
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true,
                    'status' => 'Processed',
                    'returnUnpaid' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithUpdatesForProcessedCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Processed',
                Lpa::APPLICATION_REJECTED_DATE => null,
                Lpa::APPLICATION_REGISTRATION_DATE => null
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => [
                        'status' => 'Checking',
                        'receiptDate' => new MillisecondDateTime('2019-02-11'),
                        'returnUnpaid' => null
                    ]
                ]
            ]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Checking',
                        'application-registration-date' => null,
                        'application-receipt-date' => new MillisecondDateTime('2019-02-11'),
                        'application-rejected-date' => null,
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(
            [
                98765 => [
                    'found' => true,
                    'status' => 'Checking',
                    'returnUnpaid' => null
                ]
            ]
        ), $result);
    }

    public function testGetWithNoUpdateOnValidCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Checking']]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => null,'rejectedDate' => null]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Checking',
            ]]), $result);
    }

    public function testGetWithNoUpdateOnValidCaseWithNoPreviousStatus()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => []]);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => null,'rejectedDate' => null, 'returnUnpaid' => null]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found' => false]]), $result);
    }

    public function testGetWithSameStatusAndDates()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa([
            'id' => 98765,
            'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Checking',
                Lpa::APPLICATION_RECEIPT_DATE => new MillisecondDateTime('2019-02-02'),
            ]
        ]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Checking', 'receiptDate' => new MillisecondDateTime('2019-02-02')]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Checking',
                'returnUnpaid' => null
            ]]), $result);
    }

    public function testGetNotFoundInDBAndCannotBeSaved()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Checking']]);

        $dataModel = new DataModelEntity($lpa);

        // No existing db record
        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Checking','registrationDate' => new MillisecondDateTime('2019-02-11')]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Checking',
                'returnUnpaid' => null
            ]]), $result);
    }

    public function testGetLpaAlreadyProcessedWithRegistrationDateSet()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Processed',
                Lpa::APPLICATION_REJECTED_DATE => new MillisecondDateTime('2019-02-10')
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Checking','registrationDate' => new MillisecondDateTime('2019-02-11')]
                ]
            ]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Checking',
                        'application-registration-date' => new MillisecondDateTime('2019-02-11') ,
                        'application-receipt-date' => null,
                        'application-rejected-date' => null,
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'
            ])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Checking',
                'returnUnpaid' => null
            ]]), $result);
    }

    public function testGetLpaAlreadyProcessedWithRejectedDateSet()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Processed',
                Lpa::APPLICATION_REJECTED_DATE => new MillisecondDateTime('2019-02-10')
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Processed','rejectedDate' => new MillisecondDateTime('2019-02-10')]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Processed',
                'returnUnpaid' => null
            ]]), $result);
    }

    public function testGetLpaAlreadyProcessedWithReturnUnpaidSetTrue()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98766, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Pending'
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98766'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98766' => [
                    'deleted'   => false,
                    'response'  => [
                        'status' => 'Processed', 'dispatchDate' => new MillisecondDateTime('2019-02-15'),
                        'returnUnpaid' => true
                    ]
                ]
            ]);
        $this->applicationsService->shouldReceive('patch')->once();

        $result = $this->statusController->get('98766');

        $this->assertEquals(new Json([
            '98766' => [
                'found' => true,
                'status' => 'Processed',
                'returnUnpaid' => true
            ]]), $result);
    }

    public function testGetLpaAlreadyProcessedWithReturnUnpaidSetNull()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98766, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Pending'
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98766'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98766' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Processed','dispatchDate' => new MillisecondDateTime('2019-02-15')]
                ]
            ]);
        $this->applicationsService->shouldReceive('patch')->once();

        $result = $this->statusController->get('98766');

        $this->assertEquals(new Json([
            '98766' => [
                'found' => true,
                'status' => 'Processed',
                'returnUnpaid' => null
            ]]), $result);
    }

    public function testNoUserIdPresent()
    {
        $this->expectException(ApiProblemException::class);
        $this->expectExceptionMessage('User identifier missing from URL');
        $this->statusController->get('98765');
    }

    public function testMultipleStatusUpdateOnValidCases()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa1 = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => []]);

        $lpa2 = new Lpa(['id' => 98766, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => []]);

        $dataModel1 = new DataModelEntity($lpa1);
        $dataModel2 = new DataModelEntity($lpa2);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765', '98766'], '12345'])
            ->once()
            ->andReturn([$lpa1, $lpa2]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Processed', 'rejectedDate' => new MillisecondDateTime('2019-02-11')]
                ],
                '98766' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Received', 'receiptDate' => new MillisecondDateTime('2019-02-11')]
                ]
            ]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Processed',
                        'application-registration-date' => null,
                        'application-receipt-date' => null,
                        'application-rejected-date' => new MillisecondDateTime('2019-02-11'),
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98765', '12345'])->once();

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Received',
                        'application-registration-date' => null,
                        'application-receipt-date' => new MillisecondDateTime('2019-02-11'),
                        'application-rejected-date' => null,
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ], '98766', '12345'])->once();

        $result = $this->statusController->get('98765,98766');

        $this->assertEquals(new Json([
            98765 => [
                'found' => true,
                'status' => 'Processed',
                'returnUnpaid' => null
            ],
            98766 => [
                'found' => true,
                'status' => 'Received',
                'returnUnpaid' => null
            ]
        ]), $result);
    }

    public function testGetLpaWithInvalidDate()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Processed',
                Lpa::APPLICATION_INVALID_DATE => new MillisecondDateTime('2019-02-10')
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Processed','invalidDate' => new MillisecondDateTime('2019-02-10')]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Processed',
                'returnUnpaid' => null
            ]]), $result);
    }

    public function testGetLpaWithWithdrawnDate()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Processed',
                Lpa::APPLICATION_WITHDRAWN_DATE => new MillisecondDateTime('2019-02-12')
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => ['status' => 'Processed','withdrawnDate' => new MillisecondDateTime('2019-02-12')]
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Processed',
                'returnUnpaid' => null
            ]]), $result);
    }

    public function testGetLpaProcessingStatusNotFound()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Checking',
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => false,
                    'response'  => null
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => false,
            ]]), $result);
    }

    public function testGetLpaDeleted()
    {
        $this->statusController->onDispatch($this->mvcEvent);

        $lpa = new Lpa(['id' => 98765, 'completedAt' => new MillisecondDateTime('2019-02-01'),
            'metadata' => [
                Lpa::SIRIUS_PROCESSING_STATUS => 'Checking',
            ]]);

        $dataModel = new DataModelEntity($lpa);

        $this->applicationsService->shouldReceive('filterByIdsAndUser')
            ->withArgs([['98765'], '12345'])
            ->once()
            ->andReturn([$lpa]);

        $this->applicationsService->shouldReceive('patch')
            ->withArgs([
                [
                    'metadata' => [
                        'sirius-processing-status' => 'Waiting',
                        'application-registration-date' => null,
                        'application-receipt-date' => null,
                        'application-rejected-date' => null,
                        'application-invalid-date' => null,
                        'application-withdrawn-date' => null,
                        'application-dispatch-date' => null,
                        'application-return-unpaid' => null,
                    ]
                ],
                '98765',
                '12345'
            ])->once();

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn([
                '98765' => [
                    'deleted'   => true,
                    'response'  => null
                ]
            ]);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([
            '98765' => [
                'found' => true,
                'status' => 'Waiting',
                'returnUnpaid' => null,
            ]]), $result);
    }
}
