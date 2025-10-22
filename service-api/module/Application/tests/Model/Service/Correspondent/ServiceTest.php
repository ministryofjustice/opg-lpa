<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Correspondent;

use RuntimeException;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeShared\DataModel\Lpa\Document\Correspondence;
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

        //Make sure the correspondent is invalid
        $correspondent = new Correspondence();

        $validationError = $service->update(strval($lpa->getId()), $correspondent->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'address' => ['value' => null, 'messages' => ['cannot-be-blank']],
                    'name/company' => [
                        'value' => 'MakeShared\DataModel\Lpa\Document\Correspondence',
                        'messages' => ['cannot-be-null']
                    ],
                    'who' => ['value' => null, 'messages' => ['cannot-be-blank']],
                ]
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

        $service->update(strval($lpa->getId()), $lpa->getDocument()->getCorrespondent()->toArray());

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

        $correspondent = new Correspondence($lpa->getDocument()->getCorrespondent()->toArray());
        $correspondent->getName()->setFirst('Edited');

        $entity = $service->update(strval($lpa->getId()), $correspondent->toArray());

        $this->assertEquals(new DataModelEntity($correspondent), $entity);

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
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $validationError = $service->delete(strval($lpa->getId()));

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'whoIsRegistering' => [
                        'value' => '1,2',
                        'messages' => ['allowed-values:']
                    ],
                ]
            ],
            $validationError->toArray()
        );

        $serviceBuilder->verify();
    }

    public function testDeleteMalformedData()
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

        $service->delete(strval($lpa->getId()));

        $serviceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user, true))
            ->build();

        $response = $service->delete(strval($lpa->getId()));

        $this->assertTrue($response);

        $serviceBuilder->verify();
    }
}
