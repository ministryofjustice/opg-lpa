<?php

namespace ApplicationTest\Model\Rest\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\WhoAreYou\Entity;
use Application\Model\Rest\WhoAreYou\Resource as WhoAreYouResource;
use Application\Model\Rest\WhoAreYou\Resource;
use ApplicationTest\AbstractResourceTest;
use Mockery;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use OpgTest\Lpa\DataModel\FixturesData;
use PhlyMongo\MongoCollectionFactory;

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
        $this->assertEquals('who-are-you', $resource->getName());
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var WhoAreYouResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        $entity = $resource->fetch();
        $this->assertEquals(new Entity($lpa->whoAreYouAnswered, $lpa), $entity);
        $resourceBuilder->verify();
    }

    public function testCreateCheckAccess()
    {
        /** @var WhoAreYouResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->create(null);
    }

    public function testCreateAlreadyAnswered()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->whoAreYouAnswered = true;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $resource->create(null);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(403, $apiProblem->status);
        $this->assertEquals('Question already answered', $apiProblem->detail);

        $resourceBuilder->verify();
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->whoAreYouAnswered = false;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $whoAreYou = new WhoAreYou();
        $validationError = $resource->create($whoAreYou->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('who', $validation));

        $resourceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->whoAreYouAnswered = false;
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $whoAreYou = new WhoAreYou();
        $whoAreYou->who = 'donor';
        $resource->create($whoAreYou->toArray());

        $resourceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->whoAreYouAnswered = false;
        $statsWhoCollection = Mockery::mock(MongoCollectionFactory::class);
        $statsWhoCollection->shouldReceive('insertOne')->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->withStatsWhoCollection($statsWhoCollection)
            ->build();

        $whoAreYou = new WhoAreYou();
        $whoAreYou->who = 'donor';
        $entity = $resource->create($whoAreYou->toArray());

        $this->assertEquals(new Entity(true, $lpa), $entity);
        $this->assertTrue($lpa->whoAreYouAnswered);

        $resourceBuilder->verify();
    }
}