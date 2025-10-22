<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\AttorneyDecisionsReplacement;

use RuntimeException;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeSharedTest\DataModel\FixturesData;

final class ServiceTest extends AbstractServiceTestCase
{
    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        //Make sure decisions are invalid
        $decisions = new ReplacementAttorneyDecisions();
        $decisions->set('how', 'invalid');

        $validationError = $service->update(strval($lpa->getId()), $decisions->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'how' => [
                        'value' => 'invalid',
                        'messages' => ['allowed-values:depends,jointly,single-attorney,jointly-attorney-severally']
                    ],
                ],
            ],
            $validationError->toArray()
        );

        $serviceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser('3');

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->update(strval($lpa->getId()), null);

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

        $decisions = new ReplacementAttorneyDecisions();

        $replacementAttorneyDecisionsEntity = $service->update(strval($lpa->getId()), $decisions->toArray());

        $this->assertEquals(new DataModelEntity($decisions), $replacementAttorneyDecisionsEntity);

        $serviceBuilder->verify();
    }
}
