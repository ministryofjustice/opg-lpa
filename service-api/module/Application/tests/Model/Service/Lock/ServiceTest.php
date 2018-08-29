<?php

namespace ApplicationTest\Model\Service\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Lock\Entity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use OpgTest\Lpa\DataModel\FixturesData;
use DateTime;

class ServiceTest extends AbstractServiceTest
{
    public function testCreateAlreadyLocked()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setLocked(true);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $apiProblem = $service->create($lpa->getId());

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(403, $apiProblem->getStatus());
        $this->assertEquals('LPA already locked', $apiProblem->getDetail());

        $serviceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser(3);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->create($lpa->getId());

        $serviceBuilder->verify();
    }

    public function testCreate()
    {
        //  Initialise the LPA is unlocked
        $lpa = FixturesData::getHwLpa();
        $lpa->setLocked(false);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user, true))
            ->build();

        $entity = $service->create($lpa->getId());

        $lockData = $entity->toArray();

        //  Create an LPA to compare
        $lpa->setLocked(true);
        $lpa->setLockedAt(new DateTime($lockData['lockedAt']));

        $comparisonEntity = new Entity($lpa);

        $this->assertEquals($comparisonEntity, $entity);
        $this->assertTrue($lpa->isLocked());

        $serviceBuilder->verify();
    }
}
