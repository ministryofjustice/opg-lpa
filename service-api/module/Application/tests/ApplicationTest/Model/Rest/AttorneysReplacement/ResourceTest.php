<?php

namespace ApplicationTest\Model\Rest\AttorneysReplacement;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AttorneysReplacement\Entity;
use Application\Model\Rest\AttorneysReplacement\Resource as AttorneysReplacementResource;
use ApplicationTest\AbstractResourceTest;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var AttorneysReplacementResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new AttorneysReplacementResource(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testCreateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->create(null);
    }

    public function testCreateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $resource->create($attorneyArray);

        $resourceBuilder->verify();
    }

    public function testCreateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $resource->create($attorney->toArray());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(3, count($validation));
        $this->assertTrue(array_key_exists('address', $validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('dob', $validation));

        $resourceBuilder->verify();
    }

    public function testCreate()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorney = FixturesData::getAttorneyTrust();
        $entity = $resource->create($attorney->toArray());

        $comparisonLpa = FixturesData::getPfLpa();
        $comparisonLpa->createdAt = $lpa->createdAt;
        $comparisonLpa->updatedAt = $lpa->updatedAt;
        $comparisonLpa->completedAt = $lpa->completedAt;
        $comparisonLpa->document->replacementAttorneys[] = $attorney;
        $this->assertEquals(new Entity($attorney, $comparisonLpa), $entity);

        $resourceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->update(null, -1);
    }

    public function testUpdateNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $resource->update(null, -1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->status);
        $this->assertEquals('Document not found', $apiProblem->detail);

        $resourceBuilder->verify();
    }

    public function testUpdateInvalidType()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid type passed');

        $attorney = new Human();
        $attorneyArray = $attorney->toArray();
        $attorneyArray['type'] = 'Invalid';
        $resource->update($attorneyArray, $lpa->document->replacementAttorneys[2]->id);

        $resourceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $attorney = new Human();
        $validationError = $resource->update($attorney->toArray(), $lpa->document->replacementAttorneys[1]->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(3, count($validation));
        $this->assertTrue(array_key_exists('address', $validation));
        $this->assertTrue(array_key_exists('name', $validation));
        $this->assertTrue(array_key_exists('dob', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorney = FixturesData::getAttorneyTrust();
        $id = $lpa->document->replacementAttorneys[0]->id;
        $entity = $resource->update($attorney->toArray(), $id);

        //Id will have been set to passed in id
        $attorney->id = $id;
        $comparisonLpa = FixturesData::getPfLpa();
        $comparisonLpa->document->replacementAttorneys[0] = $attorney;

        $this->assertEquals(new Entity($attorney, $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->delete(-1);
    }

    public function testDeleteNotFound()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $apiProblem = $resource->delete(-1);

        $this->assertTrue($apiProblem instanceof ApiProblem);
        $this->assertEquals(404, $apiProblem->status);
        $this->assertEquals('Document not found', $apiProblem->detail);

        $resourceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $attorneyCount = count($lpa->document->replacementAttorneys);
        $id = $lpa->document->replacementAttorneys[1]->id;
        $result = $resource->delete($id);

        $this->assertTrue($result);
        $this->assertEquals($attorneyCount-1, count($lpa->document->replacementAttorneys));

        $resourceBuilder->verify();
    }
}