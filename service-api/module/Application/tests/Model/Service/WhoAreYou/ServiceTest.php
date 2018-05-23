<?php

namespace ApplicationTest\Model\Service\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\WhoAreYou\Entity;
use Application\Model\Service\WhoAreYou\Service;
use ApplicationTest\AbstractServiceTest;
use Mockery;
use MongoDB\Collection as MongoCollection;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var Service
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new Service(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->service->setLogger($this->logger);

        $this->service->setAuthorizationService($this->authorizationService);
    }

    public function testCreateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->create(null);
    }

    public function testCreateAlreadyAnswered()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->whoAreYouAnswered = true;
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $service->create(null);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(403, $apiProblem->status);
        $this->assertEquals('Question already answered', $apiProblem->detail);

        $serviceBuilder->verify();
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->whoAreYouAnswered = false;
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $whoAreYou = new WhoAreYou();
        $validationError = $service->create($whoAreYou->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('who', $validation));

        $serviceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->whoAreYouAnswered = false;
        $lpa->user = 3;
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $whoAreYou = new WhoAreYou();
        $whoAreYou->who = 'donor';
        $service->create($whoAreYou->toArray());

        $serviceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->whoAreYouAnswered = false;
        $statsWhoCollection = Mockery::mock(MongoCollection::class);
        $statsWhoCollection->shouldReceive('insertOne')->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->withStatsWhoCollection($statsWhoCollection)
            ->build();

        $whoAreYou = new WhoAreYou();
        $whoAreYou->who = 'donor';
        $entity = $service->create($whoAreYou->toArray());

        $this->assertEquals(new Entity(true), $entity);
        $this->assertTrue($lpa->whoAreYouAnswered);

        $serviceBuilder->verify();
    }
}