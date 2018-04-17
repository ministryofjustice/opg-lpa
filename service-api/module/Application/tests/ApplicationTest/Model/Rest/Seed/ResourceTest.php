<?php

namespace ApplicationTest\Model\Rest\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Rest\Seed\Entity;
use Application\Model\Rest\Seed\Resource as SeedResource;
use ApplicationTest\AbstractResourceTest;
use ApplicationTest\Model\Rest\Applications\ResourceBuilder as ApplicationsResourceBuilder;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var SeedResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new SeedResource(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testFetchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->fetch();
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
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->update(null, -1);
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

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
}
