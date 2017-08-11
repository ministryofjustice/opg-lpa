<?php

namespace ApplicationTest\Model\Rest\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Seed\Entity;
use Application\Model\Rest\Seed\Resource as SeedResource;
use Application\Model\Rest\Seed\Resource;
use ApplicationTest\AbstractResourceTest;
use ApplicationTest\Model\Rest\Applications\ResourceBuilder as ApplicationsResourceBuilder;
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
        $this->assertEquals('seed', $resource->getName());
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var SeedResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch();
    }

    public function testFetchNullSeed()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->seed = null;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        $entity = $resource->fetch();
        $this->assertEquals(new Entity(null, $lpa), $entity);
        $resourceBuilder->verify();
    }

    public function testFetchSeedNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $lpa->seed = $seedLpa->id;
        $applicationsResourceBuilder = new ApplicationsResourceBuilder();
        $applicationsResource = $applicationsResourceBuilder
            ->withUser(FixturesData::getUser())
            ->build();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsResource($applicationsResource)
            ->build();

        $entity = $resource->fetch();

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testFetchUserDoesNotMatch()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->user = -1;
        $lpa->seed = $seedLpa->id;
        $applicationsResourceBuilder = new ApplicationsResourceBuilder();
        $applicationsResource = $applicationsResourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsResource($applicationsResource)
            ->build();

        $entity = $resource->fetch();

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $lpa->seed = $seedLpa->id;
        $applicationsResourceBuilder = new ApplicationsResourceBuilder();
        $applicationsResource = $applicationsResourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsResource($applicationsResource)
            ->build();

        $entity = $resource->fetch();

        $this->assertEquals(new Entity($seedLpa, $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        /** @var SeedResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->update(null, -1);
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $entity = $resource->update(['seed' => 'Invalid'], -1); //Id is ignored

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testUpdateSeedNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $lpa->seed = $seedLpa->id;
        $applicationsResourceBuilder = new ApplicationsResourceBuilder();
        $applicationsResource = $applicationsResourceBuilder
            ->withUser(FixturesData::getUser())
            ->build();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsResource($applicationsResource)
            ->build();

        $entity = $resource->update(['seed' => $seedLpa->id], -1); //Id is ignored

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testUpdateUserDoesNotMatch()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->user = -1;
        $lpa->seed = $seedLpa->id;
        $applicationsResourceBuilder = new ApplicationsResourceBuilder();
        $applicationsResource = $applicationsResourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsResource($applicationsResource)
            ->build();

        $entity = $resource->update(['seed' => $seedLpa->id], -1); //Id is ignored

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(400, $entity->status);
        $this->assertEquals('Invalid LPA identifier to seed from', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $applicationsResourceBuilder = new ApplicationsResourceBuilder();
        $applicationsResource = $applicationsResourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->build();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withApplicationsResource($applicationsResource)
            ->build();

        //So we expect an exception and for no document to be updated
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object');

        $resource->update(['seed' => $lpa->id], -1); //Id is ignored

        $resourceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->id = 123456789;
        $applicationsResourceBuilder = new ApplicationsResourceBuilder();
        $applicationsResource = $applicationsResourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($seedLpa)
            ->build();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->withApplicationsResource($applicationsResource)
            ->build();

        $entity = $resource->update(['seed' => $seedLpa->id], -1); //Id is ignored

        $this->assertEquals(new Entity($seedLpa, $lpa), $entity);
        $this->assertEquals($seedLpa->id, $lpa->seed);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        /** @var SeedResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->delete();
    }

    public function testDeleteMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object');

        $resource->delete();

        $resourceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $response = $resource->delete();

        $this->assertTrue($response);
        $this->assertNull($lpa->seed);

        $resourceBuilder->verify();
    }
}