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
            ->twice()
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['98765' => 'Returned']);

        $this->service->shouldReceive('patch')
            ->withArgs([['metadata' => ['sirius-processing-status' => 'Returned']], '98765', '12345'])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([98765 => ['found' => true, 'status' => 'Returned']]), $result);

    }

    public function testGetWithUpdatesOnValidCase()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Checking']]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->twice()
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['98765' => 'Returned']);

        $this->service->shouldReceive('patch')
            ->withArgs([['metadata' => ['sirius-processing-status' => 'Returned']], '98765', '12345'])->once();

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json([98765 => ['found' => true, 'status' => 'Returned']]), $result);

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
            ->andReturn(null);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found' => true, 'status' => 'Checking']]), $result);

    }

    public function testGetWithNoUpdateOnValidCaseWithNoPreviousStatus()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => []]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->once()
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(null);

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
            ->twice()
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['98765' =>'Checking']);

        $result = $this->statusController->get('98765');

        $this->assertEquals(new Json(['98765' => ['found' => true, 'status' => 'Checking']]), $result);

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

    public function testGetLpaAlreadyConcluded()
    {
        $this->statusController->onDispatch($this->mvcEvent);
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'),
            'metadata' => [Lpa::SIRIUS_PROCESSING_STATUS => 'Returned']]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->once()->andReturn($dataModel);

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
        $lpa = new Lpa(['completedAt' => new DateTime('2019-02-01'), 'metadata' => []]);

        $dataModel = new DataModelEntity($lpa);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98765', '12345'])
            ->twice()
            ->andReturn($dataModel);

        $this->service->shouldReceive('fetch')
            ->withArgs(['98766', '12345'])
            ->twice()
            ->andReturn($dataModel);

        $this->processingStatusService->shouldReceive('getStatuses')
            ->once()
            ->andReturn(['98765' => 'Returned', '98766' => 'Returned']);

        $this->service->shouldReceive('patch')
            ->withArgs([['metadata' => ['sirius-processing-status' => 'Returned']], '98765', '12345'])->once();

        $this->service->shouldReceive('patch')
            ->withArgs([['metadata' => ['sirius-processing-status' => 'Returned']], '98766', '12345'])->once();

        $result = $this->statusController->get('98765,98766');

        //print_r($result);

        $this->assertEquals(new Json([98765 => ['found' => true, 'status' => 'Returned'], 98766 => ['found' => true, 'status' => 'Returned']]), $result);

    }

}
