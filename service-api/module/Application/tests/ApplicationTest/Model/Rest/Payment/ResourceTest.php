<?php

namespace ApplicationTest\Model\Rest\Payment;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Payment\Entity;
use Application\Model\Rest\Payment\Resource as PaymentResource;
use Application\Model\Rest\Payment\Resource;
use ApplicationTest\AbstractResourceTest;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    public function testGetIdentifier()
    {
        $resource = new Resource();
        $this->assertEquals('lpaId', $resource->getIdentifier());
    }

    public function testGetName()
    {
        $resource = new Resource();
        $this->assertEquals('payment', $resource->getName());
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var PaymentResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        $entity = $resource->fetch();
        $this->assertEquals(new Entity($lpa->payment, $lpa), $entity);
        $resourceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        /** @var PaymentResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->update(null, -1);
    }

    public function testUpdateValidationFailed()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getHwLpa())->build();

        //Make sure the payment is invalid
        $payment = new Payment();
        $payment->method = 'Invalid';

        $validationError = $resource->update($payment->toArray(), -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('method', $validation));

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

        $resource->update($lpa->payment->toArray(), -1); //Id is ignored

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

        $payment = new Payment($lpa->payment->toArray());
        $payment->reference = 'Edited';

        $entity = $resource->update($payment->toArray(), -1); //Id is ignored

        $this->assertEquals(new Entity($payment, $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        /** @var PaymentResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->delete();
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
        $this->assertNull($lpa->payment);

        $resourceBuilder->verify();
    }
}