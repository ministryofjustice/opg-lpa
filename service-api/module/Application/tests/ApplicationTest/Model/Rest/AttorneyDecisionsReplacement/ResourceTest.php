<?php

namespace ApplicationTest\Model\Rest\AttorneyDecisionsReplacement;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AttorneyDecisionsReplacement\Entity;
use Application\Model\Rest\AttorneyDecisionsReplacement\Resource as AttorneyDecisionsReplacementResource;
use ApplicationTest\AbstractResourceTest;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var AttorneyDecisionsReplacementResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new AttorneyDecisionsReplacementResource(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testUpdateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->update(null, -1);
    }

    public function testUpdateValidationFailed()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getHwLpa())->build();

        //Make sure decisions are invalid
        $decisions = new ReplacementAttorneyDecisions();
        $decisions->set('how', 'invalid');

        $validationError = $resource->update($decisions->toArray(), -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('how', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $resource->update(null, -1); //Id is ignored

        $resourceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $decisions = new ReplacementAttorneyDecisions();

        $replacementAttorneyDecisionsEntity = $resource->update($decisions->toArray(), -1); //Id is ignored

        $this->assertEquals(new Entity($decisions, $lpa), $replacementAttorneyDecisionsEntity);

        $resourceBuilder->verify();
    }
}