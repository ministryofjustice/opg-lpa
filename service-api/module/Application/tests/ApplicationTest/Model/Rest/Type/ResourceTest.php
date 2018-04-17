<?php

namespace ApplicationTest\Model\Rest\Type;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\Type\Entity;
use Application\Model\Rest\Type\Resource as TypeResource;
use ApplicationTest\AbstractResourceTest;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var TypeResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new TypeResource(FixturesData::getUser()->getId(), $this->lpaCollection);

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
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $validationError = $resource->update(['type' => 'Invalid'], -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('type', $validation));

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

        $resource->update([], -1); //Id is ignored

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

        $entity = $resource->update(['type' => 'property-and-financial'], -1); //Id is ignored

        $this->assertEquals(new Entity('property-and-financial', $lpa), $entity);
        $this->assertEquals('property-and-financial', $lpa->document->type);

        $resourceBuilder->verify();
    }
}
