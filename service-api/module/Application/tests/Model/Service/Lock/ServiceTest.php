<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Lock\Entity;
use Application\Model\Service\Lock\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use DateTime;
use MakeSharedTest\DataModel\FixturesData;
use RuntimeException;

final class ServiceTest extends AbstractServiceTestCase
{
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Service();
        $this->service->setLogger($this->logger);
    }

    public function testCreateAlreadyLocked()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setLocked(true);

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $apiProblem = $this->service->create(strval($lpa->getId()));

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
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser('3');

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $this->service->create(strval($lpa->getId()));
    }

    public function testCreate()
    {
        //  Initialise the LPA is unlocked
        $lpa = FixturesData::getHwLpa();
        $lpa->setLocked(false);

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $entity = $this->service->create(strval($lpa->getId()));

        $lockData = $entity->toArray();

        //  Create an LPA to compare
        $lpa->setLocked(true);
        $lpa->setLockedAt(new DateTime($lockData['lockedAt']));

        $comparisonEntity = new Entity($lpa);

        $this->assertEquals($comparisonEntity, $entity);
        $this->assertTrue($lpa->isLocked());
    }
}
