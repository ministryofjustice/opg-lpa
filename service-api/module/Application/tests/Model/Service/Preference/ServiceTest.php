<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Preference;

use RuntimeException;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\Preference\Entity;
use Application\Model\Service\Preference\Service;
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
        //Make sure document is invalid
        $lpa->getDocument()->setType('Invalid');

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $validationError = $this->service->update(strval($lpa->getId()), []);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'type' => [
                        'value' => 'Invalid',
                        'messages' => ['allowed-values:property-and-financial,health-and-welfare']
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

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $this->service->update(strval($lpa->getId()), []);
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $entity = $this->service->update(strval($lpa->getId()), ['preference' => 'Edited']);

        $this->assertEquals(new Entity('Edited'), $entity);
    }
}
