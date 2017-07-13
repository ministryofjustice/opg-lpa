<?php

namespace ApplicationTest\Model\Rest\AttorneysPrimary;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\AttorneysPrimary\Entity;
use Application\Model\Rest\AttorneysPrimary\Resource;
use ApplicationTest\Model\DummyDocument;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $resource->getType());
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

        $this->assertEquals(new Entity($attorney, $lpa), $entity);

        $resourceBuilder->verify();
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
}