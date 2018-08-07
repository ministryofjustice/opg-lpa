<?php

namespace ApplicationTest\Model\Service\WhoAreYou;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Mongo\Collection\ApiWhoCollection;
use Application\Model\Service\WhoAreYou\Entity;
use Application\Model\Service\WhoAreYou\Service;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;
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

        $this->service = new Service($this->apiLpaCollection);

        $this->service->setLogger($this->logger);
    }

    public function testCreateAlreadyAnswered()
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->setWhoAreYouAnswered(true);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->build();

        $apiProblem = $service->create($lpa->getId(), null);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(403, $apiProblem->getStatus());
        $this->assertEquals('Question already answered', $apiProblem->getDetail());

        $serviceBuilder->verify();
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->build();

        $whoAreYou = new WhoAreYou();
        $validationError = $service->create($lpa->getId(), $whoAreYou->toArray());

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

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);
        $lpa->setUser(3);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $whoAreYou = new WhoAreYou();
        $whoAreYou->setWho('donor');
        $service->create($lpa->getId(), $whoAreYou->toArray());

        $serviceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setWhoAreYouAnswered(false);

        $whoCollection = Mockery::mock(ApiWhoCollection::class);
        $whoCollection->shouldReceive('insert')->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->withApiWhoCollection($whoCollection)
            ->build();

        $whoAreYou = new WhoAreYou();
        $whoAreYou->setWho('donor');
        $entity = $service->create($lpa->getId(), $whoAreYou->toArray());

        $this->assertEquals(new Entity(true), $entity);

        $serviceBuilder->verify();
    }
}
