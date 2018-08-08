<?php

namespace ApplicationTest\Model\Service\Instruction;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\Instruction\Entity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        //Make sure document is invalid
        $lpa->getDocument()->setType('Invalid');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->getApiLpaCollection($lpa, $user))
            ->build();

        $validationError = $service->update($lpa->getId(), []);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('type', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser(3);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->getApiLpaCollection($lpa, $user))
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->update($lpa->getId(), []);

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

        $entity = $service->update($lpa->getId(), ['instruction' => 'Edited']);

        $this->assertEquals(new Entity('Edited'), $entity);

        $serviceBuilder->verify();
    }
}
