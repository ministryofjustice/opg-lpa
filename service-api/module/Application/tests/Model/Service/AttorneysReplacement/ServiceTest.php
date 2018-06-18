<?php

namespace ApplicationTest\Model\Service\AttorneysReplacement;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\AttorneysReplacement\Service;
use ApplicationTest\Model\Service\AbstractServiceTest;
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

        $this->service = new Service($this->lpaCollection);

        $this->service->setLogger($this->logger);
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
        $service->create($lpa->getId(), $attorneyArray);

        $serviceBuilder->verify();
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $service->create($lpa->getId(), $attorney->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
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
        $entity = $service->create($lpa->getId(), $attorney->toArray());

        $this->assertEquals(new DataModelEntity($attorney), $entity);

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
        $service->update($lpa->getId(), $attorneyArray, $lpa->getDocument()->getReplacementAttorneys()[2]->id);

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $service->update($lpa->getId(), $attorney->toArray(), $lpa->getDocument()->getReplacementAttorneys()[1]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
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
        $id = $lpa->getDocument()->getReplacementAttorneys()[0]->id;
        $entity = $service->update($lpa->getId(), $attorney->toArray(), $id);

        //Id will have been set to passed in id
        $attorney->setId($id);

        $this->assertEquals(new DataModelEntity($attorney), $entity);

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
        $lpa = FixturesData::getPfLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $id = $lpa->getDocument()->getReplacementAttorneys()[1]->id;
        $result = $service->delete($lpa->getId(), $id);

        $this->assertTrue($result);

        $serviceBuilder->verify();
    }
}
