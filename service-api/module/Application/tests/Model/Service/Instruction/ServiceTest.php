<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Instruction;

use RuntimeException;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\Instruction\Entity;
use Application\Model\Service\Instruction\Service;
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

    public function testUpdateValidationFailedOnlyInstruction()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $lpa->getDocument()->setPreference('https://www.example.org not valid preference');

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $validationError = $this->service->update(strval($lpa->getId()), ['instruction' => 'https://www.example.org not valid instruction']);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'instruction' => [
                        'value' => 'https://www.example.org not valid instruction',
                        'messages' => ['no-links-allowed'],
                    ],
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

        $this->logger->shouldReceive('debug');

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);

        $this->service->update(strval($lpa->getId()), []);
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $entity = $this->service->update(strval($lpa->getId()), ['instruction' => 'Edited']);

        $this->assertEquals(new Entity('Edited'), $entity);
    }
}
