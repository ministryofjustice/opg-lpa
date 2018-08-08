<?php

namespace ApplicationTest\Model\Service\AttorneyDecisionsReplacement;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
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

        //Make sure decisions are invalid
        $decisions = new ReplacementAttorneyDecisions();
        $decisions->set('how', 'invalid');

        $validationError = $service->update($lpa->getId(), $decisions->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('how', $validation));

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

        $service->update($lpa->getId(), null);

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

        $decisions = new ReplacementAttorneyDecisions();

        $replacementAttorneyDecisionsEntity = $service->update($lpa->getId(), $decisions->toArray());

        $this->assertEquals(new DataModelEntity($decisions), $replacementAttorneyDecisionsEntity);

        $serviceBuilder->verify();
    }
}
