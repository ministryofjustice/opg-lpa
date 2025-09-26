<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Lock;

use RuntimeException;
use Application\Model\Service\Lock\Entity;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use MakeSharedTest\DataModel\FixturesData;
use DateTime;

final class ServiceTest extends AbstractServiceTestCase
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
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Forbidden',
                'status' => 403,
                'detail' => 'LPA already locked',
            ],
            $apiProblem->toArray()
        );

        $serviceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser('3');

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);
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
