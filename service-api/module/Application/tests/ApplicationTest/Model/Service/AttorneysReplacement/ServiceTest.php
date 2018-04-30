<?php

namespace ApplicationTest\Model\Service\AttorneysReplacement;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\AttorneysReplacement\Service;
use ApplicationTest\AbstractServiceTest;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
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

    public function testCreateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $service->create($attorneyArray);

        $serviceBuilder->verify();
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $service->create($attorney->toArray());

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

        $attorney = FixturesData::getAttorneyTrust();
        $entity = $service->create($attorney->toArray());

        $this->assertEquals(new DataModelEntity($attorney), $entity);

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

    public function testUpdateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $service->update($attorneyArray, $lpa->document->replacementAttorneys[2]->id);

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $service->update($attorney->toArray(), $lpa->document->replacementAttorneys[1]->id);

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

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getPfLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorney = FixturesData::getAttorneyTrust();
        $id = $lpa->document->replacementAttorneys[0]->id;
        $entity = $service->update($attorney->toArray(), $id);

        //Id will have been set to passed in id
        $attorney->id = $id;

        $this->assertEquals(new DataModelEntity($attorney), $entity);

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
        $lpa = FixturesData::getPfLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorneyCount = count($lpa->document->replacementAttorneys);
        $id = $lpa->document->replacementAttorneys[1]->id;
        $result = $service->delete($id);

        $this->assertTrue($result);
        $this->assertEquals($attorneyCount-1, count($lpa->document->replacementAttorneys));

        $serviceBuilder->verify();
    }
}