<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Donor;

use RuntimeException;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\Donor\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeShared\DataModel\Lpa\Document\Donor;
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

        //Make sure the donor is invalid
        $donor = new Donor();

        $validationError = $this->service->update(strval($lpa->getId()), $donor->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'address' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'canSign' => ['value' => null, 'messages' => ['cannot-be-null']],
                    'dob' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'name' => ['value' => null, 'messages' => ['cannot-be-blank']],
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

        $this->service->update(strval($lpa->getId()), $lpa->getDocument()->getDonor()->toArray());
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));

        $donor = new Donor($lpa->getDocument()->getDonor()->toArray());
        $donor->getName()->setFirst('Edited');

        $entity = $this->service->update(strval($lpa->getId()), $donor->toArray());

        $this->assertEquals(new DataModelEntity($donor), $entity);
    }
}
