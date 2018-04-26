<?php

namespace ApplicationTest\Model\Service\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Seed\Entity;
use Application\Model\Service\Seed\Service;
use ApplicationTest\AbstractServiceTest;
use ApplicationTest\Model\Service\Applications\ServiceBuilder as ApplicationsServiceBuilder;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var Service
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new Service(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->service->setLogger($this->logger);

        $this->service->setAuthorizationService($this->authorizationService);
    }

    public function testFetchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->fetch();
    }

    public function testFetchSeedNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $lpa->seed = $seedLpa->id;
        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser(FixturesData::getUser())
            ->build();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->fetch();

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $serviceBuilder->verify();
    }

    public function testFetchUserDoesNotMatch()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->user = -1;
        $lpa->seed = $seedLpa->id;
        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->fetch();

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $serviceBuilder->verify();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $lpa->seed = $seedLpa->id;
        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->fetch();

        $this->assertEquals(new Entity($seedLpa), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->update(null, -1);
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $entity = $service->update(['seed' => 'Invalid'], -1); //Id is ignored

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $serviceBuilder->verify();
    }

    public function testUpdateSeedNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $lpa->seed = $seedLpa->id;
        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser(FixturesData::getUser())
            ->build();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->update(['seed' => $seedLpa->id], -1); //Id is ignored

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $serviceBuilder->verify();
    }

    public function testUpdateUserDoesNotMatch()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->user = -1;
        $lpa->seed = $seedLpa->id;
        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->update(['seed' => $seedLpa->id], -1); //Id is ignored

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $serviceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->build();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsService($applicationsService)
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->update(['seed' => $lpa->id], -1); //Id is ignored

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->id = 123456789;
        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->update(['seed' => $seedLpa->id], -1); //Id is ignored

        $this->assertEquals(new Entity($seedLpa), $entity);
        $this->assertEquals($seedLpa->id, $lpa->seed);

        $serviceBuilder->verify();
    }
}
