<?php

namespace ApplicationTest\Model\Service\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryInterface;
use Application\Model\Service\WhoAreYou\Entity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    public function testUpdateAlreadyAnswered()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setWhoAreYouAnswered(true);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $apiProblem = $service->update($lpa->getId(), null);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(403, $apiProblem->getStatus());
        $this->assertEquals('Question already answered', $apiProblem->getDetail());

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $whoAreYou = new WhoAreYou();
        $validationError = $service->update($lpa->getId(), $whoAreYou->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('who', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);
        $lpa->setUser(3);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $whoAreYou = new WhoAreYou();
        $whoAreYou->setWho('donor');
        $service->update($lpa->getId(), $whoAreYou->toArray());

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);

        $user = FixturesData::getUser();

        $whoRepository = Mockery::mock(WhoRepositoryInterface::class);
        $whoRepository->shouldReceive('insert')->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user, true))
            ->withWhoRepository($whoRepository)
            ->build();

        $whoAreYou = new WhoAreYou();
        $whoAreYou->setWho('donor');
        $entity = $service->update($lpa->getId(), $whoAreYou->toArray());

        $this->assertEquals(new Entity(true), $entity);

        $serviceBuilder->verify();
    }
}
