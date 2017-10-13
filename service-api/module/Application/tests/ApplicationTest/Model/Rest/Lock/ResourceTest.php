<?php

namespace ApplicationTest\Model\Rest\Lock;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Lock\Entity;
use Application\Model\Rest\Lock\Resource;
use Application\Model\Rest\Lock\Resource as LockResource;
use ApplicationTest\AbstractResourceTest;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    public function testGetIdentifier()
    {
        $resource = new Resource();
        $this->assertEquals('lpaId', $resource->getIdentifier());
    }

    public function testGetName()
    {
        $resource = new Resource();
        $this->assertEquals('lock', $resource->getName());
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var LockResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch();
    }

    public function testFetchNull()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->locked = null;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        $entity = $resource->fetch();
        $this->assertEquals(new Entity(false, $lpa), $entity);
        $resourceBuilder->verify();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->locked = true;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        $entity = $resource->fetch();
        $this->assertEquals(new Entity(true, $lpa), $entity);
        $resourceBuilder->verify();
    }

    public function testCreateCheckAccess()
    {
        /** @var LockResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->create(null);
    }

    public function testCreateAlreadyLocked()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->locked = true;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $resource->create(null); //Data is ignored. Locked always set to true

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(403, $apiProblem->status);
        $this->assertEquals('LPA already locked', $apiProblem->detail);

        $resourceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $resource->create(null); //Data is ignored. Locked always set to true

        $resourceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->locked = false;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $entity = $resource->create(null); //Data is ignored. Locked always set to true

        $this->assertEquals(new Entity(true, $lpa), $entity);
        $this->assertTrue($lpa->locked);

        $resourceBuilder->verify();
    }
}