<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\AttorneyDecisionsPrimary;

use RuntimeException;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\AttorneyDecisionsPrimary\Service;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeSharedTest\DataModel\FixturesData;

final class ServiceTest extends AbstractServiceTestCase
{
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Service();
        $this->service->setLogger($this->logger);
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        //Make sure decisions are invalid
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->set('how', 'invalid');

        $validationError = $this->service->update(strval($lpa->getId()), $decisions->toArray());

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
                        'messages' => ['allowed-values:depends,jointly,single-attorney,jointly-attorney-severally'],
                    ]
                ]
            ],
            $validationError->toArray()
        );
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser('3');

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $this->service->update(strval($lpa->getId()), null);
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $decisions = new PrimaryAttorneyDecisions();

        $primaryAttorneyDecisionsEntity = $this->service->update(strval($lpa->getId()), $decisions->toArray());

        $this->assertEquals(new DataModelEntity($decisions), $primaryAttorneyDecisionsEntity);
    }
}
