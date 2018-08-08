<?php

namespace ApplicationTest\Model\Service\RepeatCaseNumber;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\RepeatCaseNumber\Entity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->getApiLpaCollection($lpa, $user))
            ->build();

        $validationError = $service->update($lpa->getId(), ['repeatCaseNumber' => 'Invalid']);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('repeatCaseNumber', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->getApiLpaCollection($lpa, $user, true))
            ->build();

        $entity = $service->update($lpa->getId(), ['repeatCaseNumber' => '123456789']);

        $this->assertEquals(new Entity('123456789'), $entity);

        $serviceBuilder->verify();
    }

    public function testDeleteValidationFailed()
    {
        //LPA's document must be invalid
        $lpa = FixturesData::getHwLpa();
        $lpa->getDocument()->setPrimaryAttorneys([]);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->getApiLpaCollection($lpa, $user))
            ->build();

        $validationError = $service->delete($lpa->getId());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('document.whoIsRegistering', $validation));

        $serviceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->getApiLpaCollection($lpa, $user, true))
            ->build();

        $response = $service->delete($lpa->getId());

        $this->assertTrue($response);
        $this->assertNull($lpa->getRepeatCaseNumber());

        $serviceBuilder->verify();
    }
}