<?php

namespace ApplicationTest\Model\Rest\CertificateProvider;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\CertificateProvider\Entity;
use Application\Model\Rest\CertificateProvider\Resource as CertificateProviderResource;
use Application\Model\Rest\CertificateProvider\Resource;
use ApplicationTest\AbstractResourceTest;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var CertificateProviderResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        $entity = $resource->fetch();
        $this->assertEquals(new Entity($lpa->document->certificateProvider, $lpa), $entity);
        $resourceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        /** @var CertificateProviderResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->update(null, -1);
    }

    public function testUpdateValidationFailed()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getHwLpa())->build();

        //Make sure the certificate provider is invalid
        $certificateProvider = new CertificateProvider();

        $validationError = $resource->update($certificateProvider->toArray(), -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(2, count($validation));
        $this->assertTrue(array_key_exists('name', $validation));
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
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object');

        $resource->update($lpa->document->certificateProvider->toArray(), -1); //Id is ignored

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

        $certificateProvider = new CertificateProvider($lpa->document->certificateProvider->toArray());
        $certificateProvider->name->first = 'Edited';

        $entity = $resource->update($certificateProvider->toArray(), -1); //Id is ignored

        $this->assertEquals(new Entity($certificateProvider, $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        /** @var CertificateProviderResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->delete();
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
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object');

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
        $this->assertNull($lpa->document->certificateProvider);

        $resourceBuilder->verify();
    }
}