<?php

namespace ApplicationTest\Model\Service\Donor;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\Donor\Service;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
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

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //Make sure the donor is invalid
        $donor = new Donor();

        $validationError = $service->update($lpa->getId(), $donor->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(4, count($validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('address', $validation));
        $this->assertTrue(array_key_exists('dob', $validation));
        $this->assertTrue(array_key_exists('canSign', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser(3);
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->update($lpa->getId(), $lpa->getDocument()->getDonor()->toArray());

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

        $donor = new Donor($lpa->getDocument()->getDonor()->toArray());
        $donor->getName()->setFirst('Edited');

        $entity = $service->update($lpa->getId(), $donor->toArray());

        $this->assertEquals(new DataModelEntity($donor), $entity);

        $serviceBuilder->verify();
    }
}
