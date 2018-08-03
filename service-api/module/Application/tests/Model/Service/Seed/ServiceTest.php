<?php

namespace ApplicationTest\Model\Service\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Seed\Entity;
use Application\Model\Service\Seed\Service;
use ApplicationTest\Model\Service\AbstractServiceTest;
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

        $this->service = new Service($this->apiLpaCollection);

        $this->service->setLogger($this->logger);
    }

    public function testFetchSeedNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $user = FixturesData::getUser();

        $lpa->setSeed($seedLpa->getId());

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser($user)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser($user)
            ->withLpa($lpa)
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
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $user = FixturesData::getUser();

        $seedLpa->setUser(-1);
        $lpa->setSeed($seedLpa->getId());

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser($user)
            ->withLpa($seedLpa)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser($user)
            ->withLpa($lpa)
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
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $user = FixturesData::getUser();

        $lpa->setSeed($seedLpa->getId());

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser($user)
            ->withLpa($seedLpa)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser($user)
            ->withLpa($lpa)
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
            ->withUser($user)
            ->withLpa($lpa)
            ->build();

        $entity = $service->update($lpa->getId(), ['seed' => 'Invalid'], $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->getStatus());
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testUpdateSeedNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $user = FixturesData::getUser();

        $lpa->setSeed($seedLpa->getId());

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser($user)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser($user)
            ->withLpa($lpa)
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
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $user = FixturesData::getUser();

        $seedLpa->setUser(-1);
        $lpa->setSeed($seedLpa->getId());

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser($user)
            ->withLpa($seedLpa)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser($user)
            ->withLpa($lpa)
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
        $user = FixturesData::getUser();

        $lpa->setUser(3);

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser($user)
            ->withLpa($lpa)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser($user)
            ->withLpa($lpa)
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
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $user = FixturesData::getUser();

        $seedLpa->setId(123456789);

        $applicationsServiceBuilder = new ApplicationsServiceBuilder();
        $applicationsService = $applicationsServiceBuilder
            ->withUser($user)
            ->withLpa($seedLpa)
            ->build();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser($user)
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->withApplicationsService($applicationsService)
            ->build();

        $entity = $service->update($lpa->getId(), ['seed' => $seedLpa->getId()], $user->getId());

        $this->assertEquals(new Entity($seedLpa), $entity);

        $serviceBuilder->verify();
    }
}
