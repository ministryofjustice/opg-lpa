<?php

namespace ApplicationTest\Model\Rest\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\NotifiedPeople\Entity;
use Application\Model\Rest\NotifiedPeople\Resource;
use Application\Model\Rest\NotifiedPeople\Resource as NotifiedPeopleResource;
use ApplicationTest\AbstractResourceTest;
use ApplicationTest\DummyDocument;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var NotifiedPeopleResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new NotifiedPeopleResource($this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('resourceId', $this->resource->getIdentifier());
    }

    public function testGetName()
    {
        $this->assertEquals('notified-people', $this->resource->getName());
    }

    public function testGetType()
    {
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $this->resource->getType());
    }

    public function testCreateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->create(null);
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $person = new NotifiedPerson();
        $validationError = $resource->create($person->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(2, count($validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('address', $validation));

        $resourceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $person = new NotifiedPerson(FixturesData::getAttorneyHumanJson());
        $person->id = null;
        $entity = $resource->create($person->toArray());

        $comparisonLpa = FixturesData::getPfLpa();
        $comparisonLpa->createdAt = $lpa->createdAt;
        $comparisonLpa->updatedAt = $lpa->updatedAt;
        $comparisonLpa->completedAt = $lpa->completedAt;
        $person->id = 1;
        $comparisonLpa->document->peopleToNotify[] = $person;
        $this->assertEquals(new Entity($person, $comparisonLpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->fetch(-1);
    }

    public function testFetchNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $resource->fetch(-1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->status);
        $this->assertEquals('Document not found', $apiProblem->detail);

        $resourceBuilder->verify();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $entity = $resource->fetch($lpa->document->peopleToNotify[0]->id);

        $this->assertEquals(new Entity($lpa->document->peopleToNotify[0], $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchAllCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->fetchAll();
    }

    public function testFetchAllNull()
    {
        $lpa = FixturesData::getHwLpa();
        $document = new DummyDocument($lpa->document->toArray());
        $document->setDirect('peopleToNotify', null);
        $lpa->document = $document;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $collection = $resource->fetchAll();

        $this->assertNull($collection);

        $resourceBuilder->verify();
    }

    public function testFetchAllNoRecords()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->document->peopleToNotify = [];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $collection = $resource->fetchAll();

        $this->assertEquals(0, $collection->count());

        $resourceBuilder->verify();
    }

    public function testFetchAll()
    {
        $lpa = FixturesData::getHwLpa();
        $peopleToNotify = $lpa->document->peopleToNotify;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $collection = $resource->fetchAll();

        $array = $collection->toArray();
        $this->assertEquals(count($peopleToNotify), $array['count']);
        $this->assertEquals(count($peopleToNotify), $array['total']);
        $this->assertEquals(1, $array['pages']);
        /* @var $items Entity[] */
        $items = $array['items'];
        for ($i = 0; $i < count($peopleToNotify); $i++) {
            $this->assertTrue($array['items'][$i] instanceof Entity);
            $this->assertEquals(new Entity($peopleToNotify[$i], $lpa), $items[$i]);
        }
    }

    public function testUpdateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->update(null, -1);
    }

    public function testUpdateNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $resource->update(null, -1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->status);
        $this->assertEquals('Document not found', $apiProblem->detail);

        $resourceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $person = new NotifiedPerson();
        $validationError = $resource->update($person->toArray(), $lpa->document->peopleToNotify[0]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(2, count($validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('address', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $person = new NotifiedPerson(FixturesData::getAttorneyHumanJson());
        $id = $lpa->document->peopleToNotify[0]->id;
        $entity = $resource->update($person->toArray(), $id);

        //Id will have been set to passed in id
        $person->id = $id;
        $comparisonLpa = FixturesData::getPfLpa();
        $comparisonLpa->document->peopleToNotify[0] = $person;

        $this->assertEquals(new Entity($person, $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->delete(-1);
    }

    public function testDeleteNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $resource->delete(-1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->status);
        $this->assertEquals('Document not found', $apiProblem->detail);

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

        $attorneyCount = count($lpa->document->peopleToNotify);
        $id = $lpa->document->peopleToNotify[0]->id;
        $result = $resource->delete($id);

        $this->assertTrue($result);
        $this->assertEquals($attorneyCount-1, count($lpa->document->peopleToNotify));

        $resourceBuilder->verify();
    }
}