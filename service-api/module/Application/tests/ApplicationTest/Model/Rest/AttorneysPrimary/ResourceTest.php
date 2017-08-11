<?php

namespace ApplicationTest\Model\Rest\AttorneysPrimary;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\AttorneysPrimary\Entity;
use Application\Model\Rest\AttorneysPrimary\Resource;
use Application\Model\Rest\AttorneysPrimary\Resource as AttorneysPrimaryResource;
use ApplicationTest\AbstractResourceTest;
use ApplicationTest\DummyDocument;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    public function testGetIdentifier()
    {
        $resource = new Resource();
        $this->assertEquals('resourceId', $resource->getIdentifier());
    }

    public function testGetName()
    {
        $resource = new Resource();
        $this->assertEquals('primary-attorneys', $resource->getName());
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $resource->getType());
    }

    public function testCreateCheckAccess()
    {
        /** @var AttorneysPrimaryResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->create(null);
    }

    public function testCreateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $this->setExpectedException(\RuntimeException::class, 'Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $resource->create($attorneyArray);

        $resourceBuilder->verify();
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $resource->create($attorney->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(3, count($validation));
        $this->assertTrue(array_key_exists('address', $validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('dob', $validation));

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

        $attorney = FixturesData::getAttorneyTrust();
        $entity = $resource->create($attorney->toArray());

        $comparisonLpa = FixturesData::getPfLpa();
        $comparisonLpa->createdAt = $lpa->createdAt;
        $comparisonLpa->updatedAt = $lpa->updatedAt;
        $comparisonLpa->completedAt = $lpa->completedAt;
        $comparisonLpa->document->primaryAttorneys[] = $attorney;
        $this->assertEquals(new Entity($attorney, $comparisonLpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchCheckAccess()
    {
        /** @var AttorneysPrimaryResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch(-1);
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
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $entity = $resource->fetch($lpa->document->primaryAttorneys[2]->id);

        $this->assertEquals(new Entity($lpa->document->primaryAttorneys[2], $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchAllCheckAccess()
    {
        /** @var AttorneysPrimaryResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetchAll();
    }

    public function testFetchAllNull()
    {
        $lpa = FixturesData::getHwLpa();
        $document = new DummyDocument($lpa->document->toArray());
        $document->setDirect('primaryAttorneys', null);
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
        $lpa->document->primaryAttorneys = [];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $collection = $resource->fetchAll();

        $this->assertEquals(0, $collection->count());

        $resourceBuilder->verify();
    }

    public function testFetchAll()
    {
        $lpa = FixturesData::getPfLpa();
        $primaryAttorneys = $lpa->document->primaryAttorneys;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $collection = $resource->fetchAll();

        $array = $collection->toArray();
        $this->assertEquals(count($primaryAttorneys), $array['count']);
        $this->assertEquals(count($primaryAttorneys), $array['total']);
        $this->assertEquals(1, $array['pages']);
        /* @var $items Entity[] */
        $items = $array['items'];
        for ($i = 0; $i < count($primaryAttorneys); $i++) {
            $this->assertTrue($array['items'][$i] instanceof Entity);
            $this->assertEquals(new Entity($primaryAttorneys[$i], $lpa), $items[$i]);
        }
    }

    public function testUpdateCheckAccess()
    {
        /** @var AttorneysPrimaryResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->update(null, -1);
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

    public function testUpdateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $this->setExpectedException(\RuntimeException::class, 'Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $resource->update($attorneyArray, $lpa->document->primaryAttorneys[2]->id);

        $resourceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $resource->update($attorney->toArray(), $lpa->document->primaryAttorneys[1]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(3, count($validation));
        $this->assertTrue(array_key_exists('address', $validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('dob', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorney = FixturesData::getAttorneyTrust();
        $id = $lpa->document->primaryAttorneys[0]->id;
        $entity = $resource->update($attorney->toArray(), $id);

        //Id will have been set to passed in id
        $attorney->id = $id;
        $comparisonLpa = FixturesData::getPfLpa();
        $comparisonLpa->document->primaryAttorneys[0] = $attorney;

        $this->assertEquals(new Entity($attorney, $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        /** @var AttorneysPrimaryResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->delete(-1);
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
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorneyCount = count($lpa->document->primaryAttorneys);
        $id = $lpa->document->primaryAttorneys[1]->id;
        $result = $resource->delete($id);

        $this->assertTrue($result);
        $this->assertEquals($attorneyCount-1, count($lpa->document->primaryAttorneys));

        $resourceBuilder->verify();
    }
}