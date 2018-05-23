<?php

namespace ApplicationTest\Model\Service\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\NotifiedPeople\Service;
use ApplicationTest\AbstractServiceTest;
use ApplicationTest\DummyDocument;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
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

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $person = new NotifiedPerson();
        $validationError = $service->create($person->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(2, count($validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('address', $validation));

        $serviceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getPfLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $person = new NotifiedPerson(FixturesData::getAttorneyHumanJson());
        $person->id = null;
        $entity = $service->create($person->toArray());

        //  We expect an ID value to have been added
        $person->id = 1;

        $this->assertEquals(new DataModelEntity($person), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->update(null, -1);
    }

    public function testUpdateNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $service->update(null, -1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->status);
        $this->assertEquals('Document not found', $apiProblem->detail);

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $person = new NotifiedPerson();
        $validationError = $service->update($person->toArray(), $lpa->document->peopleToNotify[0]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(2, count($validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('address', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $person = new NotifiedPerson(FixturesData::getAttorneyHumanJson());
        $id = $lpa->document->peopleToNotify[0]->id;
        $entity = $service->update($person->toArray(), $id);

        //Id will have been set to passed in id
        $person->id = $id;

        $this->assertEquals(new DataModelEntity($person), $entity);

        $serviceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->delete(-1);
    }

    public function testDeleteNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $service->delete(-1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->status);
        $this->assertEquals('Document not found', $apiProblem->detail);

        $serviceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorneyCount = count($lpa->document->peopleToNotify);
        $id = $lpa->document->peopleToNotify[0]->id;
        $result = $service->delete($id);

        $this->assertTrue($result);
        $this->assertEquals($attorneyCount-1, count($lpa->document->peopleToNotify));

        $serviceBuilder->verify();
    }
}