<?php

namespace ApplicationTest\Model\Rest\Users;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Users\Entity;
use Application\Model\Rest\Users\Resource;
use Application\Model\Rest\Users\Resource as UsersResource;
use Application\Model\Rest\Applications\Resource as ApplicationsResource;
use ApplicationTest\AbstractResourceTest;
use Mockery;
use MongoDB\UpdateResult;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use PhlyMongo\MongoCollectionFactory;

class ResourceTest extends AbstractResourceTest
{
    public function testGetIdentifier()
    {
        $resource = new Resource();
        $this->assertEquals('userId', $resource->getIdentifier());
    }

    public function testGetName()
    {
        $resource = new Resource();
        $this->assertEquals('users', $resource->getName());
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var UsersResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch(-1);
    }

    public function testFetchDoesNotExist()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollectionFactory::class);
        $userCollection->shouldReceive('findOne')->andReturn(null)->twice();
        $userCollection->shouldReceive('insertOne')->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->build();

        $entity = $resource->fetch($user->id);
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->id = $user->id;
        $expectedUser->email = $user->email;
        $expectedUser->createdAt = $entityArray['createdAt'];
        $expectedUser->updatedAt = $entityArray['updatedAt'];
        $this->assertEquals(new Entity($expectedUser), $entity);

        $resourceBuilder->verify();
    }

    public function testFetch()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollectionFactory::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toMongoArray())->once();
        $userCollection->shouldNotReceive('insertOne');
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->build();

        $entity = $resource->fetch($user->id);

        $this->assertEquals(new Entity($user), $entity);

        $resourceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        /** @var UsersResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->update(null, -1);
    }

    public function testUpdateNotFound()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollectionFactory::class);
        $userCollection->shouldReceive('findOne')->andReturn(null)->once();
        $userCollection->shouldReceive('insertOne')->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->build();

        $entity = $resource->update(null, $user->id);
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->id = $user->id;
        $expectedUser->email = $user->email;
        $expectedUser->createdAt = $entityArray['createdAt'];
        $expectedUser->updatedAt = $entityArray['updatedAt'];
        $this->assertEquals(new Entity($expectedUser), $entity);

        $resourceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollectionFactory::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toMongoArray())->once();
        $userCollection->shouldNotReceive('updateOne');
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->build();

        $userUpdate = FixturesData::getUser();
        $userUpdate->name->title = 'TooLong';
        $validationError = $resource->update($userUpdate->toArray(), $user->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('name.title', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdate()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollectionFactory::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toMongoArray())->once();
        $updateResult = Mockery::mock(UpdateResult::class);
        $updateResult->shouldReceive('getModifiedCount')->andReturn(1);
        $userCollection->shouldReceive('updateOne')->andReturn($updateResult)->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->build();

        $userUpdate = FixturesData::getUser();
        $userUpdate->name->first = 'Edited';
        $entity = $resource->update($userUpdate->toArray(), $user->id);
        $entityArray = $entity->toArray();

        $userUpdate->updatedAt = $entityArray['updatedAt'];
        $this->assertEquals(new Entity($userUpdate), $entity);

        $resourceBuilder->verify();
    }

    public function testUpdateNumberModifiedError()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollectionFactory::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toMongoArray())->once();
        $updateResult = Mockery::mock(UpdateResult::class);
        $updateResult->shouldReceive('getModifiedCount')->andReturn(2);
        $userCollection->shouldReceive('updateOne')->andReturn($updateResult)->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update User. This might be because "updatedAt" has changed.');
        $userUpdate = FixturesData::getUser();
        $userUpdate->name->first = 'Edited';
        $resource->update($userUpdate->toArray(), $user->id);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        /** @var UsersResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->delete(-1);
    }

    public function testDelete()
    {
        $user = FixturesData::getUser();
        $applicationsResource = Mockery::mock(ApplicationsResource::class);
        $applicationsResource->shouldReceive('deleteAll')->once();
        $userCollection = Mockery::mock(MongoCollectionFactory::class);
        $userCollection->shouldReceive('deleteOne')->with([ '_id' => $user->id ])->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withApplicationsResource($applicationsResource)
            ->withUserCollection($userCollection)
            ->build();

        $result = $resource->delete($user->id);

        $this->assertTrue($result);

        $resourceBuilder->verify();
    }
}