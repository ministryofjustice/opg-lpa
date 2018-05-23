<?php

namespace ApplicationTest\Model\Service\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Lock\Entity;
use Application\Model\Service\Lock\Service;
use ApplicationTest\AbstractServiceTest;
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

    public function testCreateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->create(null);
    }

    public function testCreateAlreadyLocked()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->locked = true;
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $service->create(null); //Data is ignored. Locked always set to true

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(403, $apiProblem->status);
        $this->assertEquals('LPA already locked', $apiProblem->detail);

        $serviceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->create(null); //Data is ignored. Locked always set to true

        $serviceBuilder->verify();
    }

    public function testCreate()
    {
        //  Initialise the LPA is unlocked
        $lpa = FixturesData::getHwLpa();
        $lpa->locked = false;

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $entity = $service->create(null); //Data is ignored. Locked always set to true

        //  Create an LPA to compare
        $lpa->locked = true;
        $comparisonEntity = new Entity($lpa);

        $this->assertEquals($comparisonEntity, $entity);
        $this->assertTrue($lpa->locked);

        $serviceBuilder->verify();
    }
}