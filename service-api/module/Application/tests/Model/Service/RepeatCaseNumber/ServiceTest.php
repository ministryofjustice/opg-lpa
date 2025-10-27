<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\RepeatCaseNumber;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\RepeatCaseNumber\Entity;
use Application\Model\Service\RepeatCaseNumber\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
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

        $validationError = $this->service->update(strval($lpa->getId()), ['repeatCaseNumber' => 'Invalid']);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'repeatCaseNumber' => ['value' => 'Invalid', 'messages' => ['expected-type:int']],
                ]
            ],
            $validationError->toArray()
        );
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $entity = $this->service->update(strval($lpa->getId()), ['repeatCaseNumber' => '123456789']);

        $this->assertEquals(new Entity('123456789'), $entity);
    }

    public function testDeleteValidationFailed()
    {
        //LPA's document must be invalid
        $lpa = FixturesData::getHwLpa();
        $lpa->getDocument()->setPrimaryAttorneys([]);

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $validationError = $this->service->delete(strval($lpa->getId()));

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'document.whoIsRegistering' => ['value' => '1,2', 'messages' => ['allowed-values:']],
                ]
            ],
            $validationError->toArray()
        );
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $response = $this->service->delete(strval($lpa->getId()));

        $this->assertTrue($response);
        $this->assertNull($lpa->getRepeatCaseNumber());
    }
}
