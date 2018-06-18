<?php

namespace ApplicationTest\Model\Service\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\NotifiedPeople\Service;
use ApplicationTest\Model\Service\AbstractServiceTest;
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

        $this->service = new Service($this->lpaCollection);

        $this->service->setLogger($this->logger);
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $person = new NotifiedPerson();
        $validationError = $service->create($lpa->getId(), $person->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
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
        $entity = $service->create($lpa->getId(), $person->toArray());

        //  We expect an ID value to have been added
        $person->setId(1);

        $this->assertEquals(new DataModelEntity($person), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $service->update($lpa->getId(), null, -1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->getStatus());
        $this->assertEquals('Document not found', $apiProblem->getDetail());

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $person = new NotifiedPerson();
        $validationError = $service->update($lpa->getId(), $person->toArray(), $lpa->getDocument()->getPeopleToNotify()[0]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
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
        $id = $lpa->getDocument()->getPeopleToNotify()[0]->id;
        $entity = $service->update($lpa->getId(), $person->toArray(), $id);

        //Id will have been set to passed in id
        $person->setId($id);

        $this->assertEquals(new DataModelEntity($person), $entity);

        $serviceBuilder->verify();
    }

    public function testDeleteNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $service->delete($lpa->getId(), -1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->getStatus());
        $this->assertEquals('Document not found', $apiProblem->getDetail());

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

        $id = $lpa->getDocument()->getPeopleToNotify()[0]->id;
        $result = $service->delete($lpa->getId(), $id);

        $this->assertTrue($result);

        $serviceBuilder->verify();
    }
}