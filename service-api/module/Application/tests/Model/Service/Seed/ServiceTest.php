<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\Service\Seed\Entity;
use Application\Model\Service\Seed\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use ApplicationTest\Model\Service\Applications\TestableService;
use MakeShared\DataModel\Lpa\Lpa;
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

    public function testFetchSeedNotFound()
    {
        $seedLpa = FixturesData::getPfLpa();

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $apiLpaCollection2 = Mockery::mock(ApplicationRepositoryInterface::class);
        $apiLpaCollection2->shouldReceive('getById')
            ->andReturnNull();

        $applicationsService = new TestableService();
        $applicationsService->setApplicationRepository($apiLpaCollection2);
        $applicationsService->setLogger($this->logger);

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setApplicationsService($applicationsService);

        $entity = $this->service->fetch(strval($lpa->getId()), $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Invalid LPA identifier to seed from',
            ],
            $entity->toArray()
        );
    }

    public function testFetchUserDoesNotMatch()
    {
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->setUser('-1');

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsService = new TestableService();
        $applicationsService->setApplicationRepository($this->getApplicationRepository($seedLpa, $user));
        $applicationsService->setLogger($this->logger);

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setApplicationsService($applicationsService);

        $entity = $this->service->fetch(strval($lpa->getId()), $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'LPA user does not match fetched LPA\'s user',
            ],
            $entity->toArray()
        );
    }

    public function testFetch()
    {
        $seedLpa = FixturesData::getPfLpa();

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsService = new TestableService();
        $applicationsService->setApplicationRepository($this->getApplicationRepository($seedLpa, $user));
        $applicationsService->setLogger($this->logger);

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setApplicationsService($applicationsService);

        $entity = $this->service->fetch(strval($lpa->getId()), $user->getId());

        $this->assertEquals(new Entity($seedLpa), $entity);
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $entity = $this->service->update(strval($lpa->getId()), ['seed' => 'Invalid'], $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Invalid LPA identifier to seed from',
            ],
            $entity->toArray()
        );
    }

    public function testUpdateSeedNotFound()
    {
        $seedLpa = FixturesData::getPfLpa();

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsService = new TestableService();
        $applicationsService->setApplicationRepository($this->getApplicationRepository(new Lpa(), $user));
        $applicationsService->setLogger($this->logger);

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setApplicationsService($applicationsService);

        $entity = $this->service->update(strval($lpa->getId()), ['seed' => $seedLpa->getId()], $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Invalid LPA identifier to seed from',
            ],
            $entity->toArray()
        );
    }

    public function testUpdateUserDoesNotMatch()
    {
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->setUser('-1');

        $lpa = FixturesData::getHwLpa();
        $lpa->setSeed($seedLpa->getId());

        $user = FixturesData::getUser();

        $applicationsService = new TestableService();
        $applicationsService->setApplicationRepository($this->getApplicationRepository($seedLpa, $user));
        $applicationsService->setLogger($this->logger);

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setApplicationsService($applicationsService);

        $entity = $this->service->update(strval($lpa->getId()), ['seed' => $seedLpa->getId()], $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Invalid LPA identifier to seed from',
            ],
            $entity->toArray()
        );
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser('3');

        $user = FixturesData::getUser();

        $applicationsService = new TestableService();
        $applicationsService->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $applicationsService->setLogger($this->logger);

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setApplicationsService($applicationsService);

        //So we expect an exception and for no document to be updated
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $this->service->update(strval($lpa->getId()), ['seed' => $lpa->getId()], $user->getId());
    }

    public function testUpdate()
    {
        $seedLpa = FixturesData::getPfLpa();
        $seedLpa->setId(123456789);

        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $applicationsService = new TestableService();
        $applicationsService->setApplicationRepository($this->getApplicationRepository($seedLpa, $user));
        $applicationsService->setLogger($this->logger);

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user, true));
        $this->service->setApplicationsService($applicationsService);

        $entity = $this->service->update(strval($lpa->getId()), ['seed' => $seedLpa->getId()], $user->getId());

        $this->assertEquals(new Entity($seedLpa), $entity);
    }
}
