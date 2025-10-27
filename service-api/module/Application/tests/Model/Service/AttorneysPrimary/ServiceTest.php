<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\AttorneysPrimary;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AttorneysPrimary\Service;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeSharedTest\DataModel\FixturesData;
use RuntimeException;

final class ServiceTest extends AbstractServiceTestCase
{
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Service();
        $this->service->setLogger($this->logger);
    }

    public function testCreateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $this->service->create(strval(strval($lpa->getId())), $attorneyArray);
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $attorney = new Human();
        $validationError = $this->service->create(strval(strval($lpa->getId())), $attorney->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'address' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'dob' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'name' => ['value' => null, 'messages' => ['cannot-be-blank']],
                ]
            ],
            $validationError->toArray()
        );
    }

    public function testCreate()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $attorney = FixturesData::getAttorneyTrust();
        $entity = $this->service->create(strval($lpa->getId()), $attorney->toArray());

        $this->assertEquals(new DataModelEntity($attorney), $entity);
    }

    public function testUpdateNotFound()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $apiProblem = $this->service->update(strval($lpa->getId()), null, -1);

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
    }

    public function testUpdateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $this->service->update(strval($lpa->getId()), $attorneyArray, $lpa->getDocument()->getPrimaryAttorneys()[2]->id);
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $attorney = new Human();
        $validationError = $this->service->update(strval($lpa->getId()), $attorney->toArray(), $lpa->getDocument()->getPrimaryAttorneys()[1]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'dob' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'name' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'address' => ['value' => null, 'messages' => ['cannot-be-blank']],
                ]
            ],
            $validationError->toArray()
        );
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $attorney = FixturesData::getAttorneyTrust();
        $id = $lpa->getDocument()->getPrimaryAttorneys()[0]->id;
        $entity = $this->service->update(strval($lpa->getId()), $attorney->toArray(), $id);

        //Id will have been set to passed in id
        $attorney->setId($id);

        $this->assertEquals(new DataModelEntity($attorney), $entity);
    }

    public function testDeleteNotFound()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $apiProblem = $this->service->delete(strval($lpa->getId()), -1);

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
    }

    public function testDelete()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $id = $lpa->getDocument()->getPrimaryAttorneys()[1]->id;
        $result = $this->service->delete(strval($lpa->getId()), $id);

        $this->assertTrue($result);
    }
}
