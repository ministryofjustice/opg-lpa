<?php

namespace ApplicationTest\Model\Service\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\Service\Seed\Entity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use ApplicationTest\Model\Service\Applications\ServiceBuilder as ApplicationsServiceBuilder;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use Mockery;

class ServiceTest extends AbstractServiceTest
{
    public function testFetchSeedNotFound()
    {
        $seedLpa = FixturesData::getPfLpa();

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $apiLpaCollection2 = Mockery::mock(ApplicationRepositoryInterface::class);
        $apiLpaCollection2->shouldReceive('getById')
            ->andReturnNull();

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withApplicationRepository($apiLpaCollection2)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->fetch($lpa->getId(), $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->getStatus());
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testFetchUserDoesNotMatch()
    {
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->setUser(-1);

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withApplicationRepository($this->getApplicationRepository($seedLpa, $user))
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->fetch($lpa->getId(), $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->getStatus());
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testFetch()
    {
        $seedLpa = FixturesData::getPfLpa();

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withApplicationRepository($this->getApplicationRepository($seedLpa, $user))
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->fetch($lpa->getId(), $user->getId());

        $this->assertEquals(new Entity($seedLpa), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $entity = $service->update($lpa->getId(), ['seed' => 'Invalid'], $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->getStatus());
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testUpdateSeedNotFound()
    {
        $seedLpa = FixturesData::getPfLpa();

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withApplicationRepository($this->getApplicationRepository(new Lpa(), $user))
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->update($lpa->getId(), ['seed' => $seedLpa->getId()], $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->getStatus());
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testUpdateUserDoesNotMatch()
    {
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->setUser(-1);

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withApplicationRepository($this->getApplicationRepository($seedLpa, $user))
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->update($lpa->getId(), ['seed' => $seedLpa->getId()], $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->getStatus());
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser(3);

        $user = FixturesData::getUser();

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withApplicationsService($applicationsService)
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->update($lpa->getId(), ['seed' => $lpa->getId()], $user->getId());

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->setId(123456789);

        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withApplicationRepository($this->getApplicationRepository($seedLpa, $user))
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user, true))
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->update($lpa->getId(), ['seed' => $seedLpa->getId()], $user->getId());

        $this->assertEquals(new Entity($seedLpa), $entity);

        $serviceBuilder->verify();
    }
}
