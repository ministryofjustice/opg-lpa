<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeSharedTest\DataModel\FixturesData;

final class ServiceTest extends AbstractServiceTestCase
{
    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $person = new NotifiedPerson();
        $validationError = $service->create(strval($lpa->getId()), $person->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'address' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'name' => ['value' => null, 'messages' => ['cannot-be-blank']],
                ]
            ],
            $validationError->toArray()
        );

        $serviceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user, true))
            ->build();

        $person = new NotifiedPerson(FixturesData::getAttorneyHumanJson());
        $person->id = null;
        $entity = $service->create(strval($lpa->getId()), $person->toArray());

        //  We expect an ID value to have been added
        $person->setId(1);

        $this->assertEquals(new DataModelEntity($person), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateNotFound()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $apiProblem = $service->update(strval($lpa->getId()), null, -1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Document not found',
            ],
            $apiProblem->toArray()
        );

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $person = new NotifiedPerson();
        $validationError = $service->update(strval($lpa->getId()), $person->toArray(), $lpa->getDocument()->getPeopleToNotify()[0]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'address' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'name' => ['value' => null, 'messages' => ['cannot-be-blank']],
                ]
            ],
            $validationError->toArray()
        );

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user, true))
            ->build();

        $person = new NotifiedPerson(FixturesData::getAttorneyHumanJson());
        $id = $lpa->getDocument()->getPeopleToNotify()[0]->id;
        $entity = $service->update(strval($lpa->getId()), $person->toArray(), $id);

        //Id will have been set to passed in id
        $person->setId($id);

        $this->assertEquals(new DataModelEntity($person), $entity);

        $serviceBuilder->verify();
    }

    public function testDeleteNotFound()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $apiProblem = $service->delete(strval($lpa->getId()), -1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Document not found',
            ],
            $apiProblem->toArray()
        );

        $serviceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user, true))
            ->build();

        $id = $lpa->getDocument()->getPeopleToNotify()[0]->id;
        $result = $service->delete(strval($lpa->getId()), $id);

        $this->assertTrue($result);

        $serviceBuilder->verify();
    }
}
