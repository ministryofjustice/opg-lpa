<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryInterface;
use Application\Model\Service\WhoAreYou\Entity;
use Application\Model\Service\WhoAreYou\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use MakeSharedTest\DataModel\FixturesData;
use Mockery;
use RuntimeException;

final class ServiceTest extends AbstractServiceTestCase
{
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Service();
        $this->service->setLogger($this->logger);
    }

    public function testUpdateAlreadyAnswered()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setWhoAreYouAnswered(true);

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $apiProblem = $this->service->update(strval($lpa->getId()), null);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Forbidden',
                'status' => 403,
                'detail' => 'Question already answered',
            ],
            $apiProblem->toArray()
        );
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $whoAreYou = new WhoAreYou();
        $validationError = $this->service->update(strval($lpa->getId()), $whoAreYou->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'who' => ['value' => null, 'messages' => ['cannot-be-blank']],
                ]
            ],
            $validationError->toArray()
        );
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);
        $lpa->setUser('3');

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $whoAreYou = new WhoAreYou();
        $whoAreYou->setWho('donor');
        $this->service->update(strval($lpa->getId()), $whoAreYou->toArray());
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);

        $user = FixturesData::getUser();

        $whoRepository = Mockery::mock(WhoRepositoryInterface::class);
        $whoRepository->shouldReceive('insert')->once();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));
        $this->service->setWhoRepository($whoRepository);

        $whoAreYou = new WhoAreYou();
        $whoAreYou->setWho('donor');
        $entity = $this->service->update(strval($lpa->getId()), $whoAreYou->toArray());

        $this->assertEquals(new Entity(true), $entity);
    }
}
