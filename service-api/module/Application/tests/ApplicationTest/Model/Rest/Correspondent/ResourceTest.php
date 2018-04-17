<?php

namespace ApplicationTest\Model\Rest\Correspondent;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\Correspondent\Entity;
use Application\Model\Rest\Correspondent\Resource as CorrespondentResource;
use ApplicationTest\AbstractResourceTest;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var CorrespondentResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new CorrespondentResource(FixturesData::getUser()->getId(), $this->lpaCollection);

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

        //Make sure the correspondent is invalid
        $correspondent = new Correspondence();

        $validationError = $resource->update($correspondent->toArray(), -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertTrue(array_key_exists('name/company', $validation));
        $this->assertTrue(array_key_exists('who', $validation));
        $this->assertTrue(array_key_exists('address', $validation));

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

        $resource->update($lpa->document->correspondent->toArray(), -1); //Id is ignored

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

        $correspondent = new Correspondence($lpa->document->correspondent->toArray());
        $correspondent->name->first = 'Edited';

        $entity = $resource->update($correspondent->toArray(), -1); //Id is ignored

        $this->assertEquals(new Entity($correspondent, $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->delete();
    }

    public function testDeleteValidationFailed()
    {
        //LPA's document must be invalid
        $lpa = FixturesData::getHwLpa();
        $lpa->document->primaryAttorneys = [];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $validationError = $resource->delete();

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('whoIsRegistering', $validation));

        $resourceBuilder->verify();
    }

    public function testDeleteMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $resource->delete(); //Id is ignored

        $resourceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $response = $resource->delete(); //Id is ignored

        $this->assertTrue($response);
        $this->assertNull($lpa->document->correspondent);

        $resourceBuilder->verify();
    }
}