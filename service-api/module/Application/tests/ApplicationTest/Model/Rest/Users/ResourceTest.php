<?php

namespace ApplicationTest\Model\Rest\Users;

use Application\DataAccess\Mongo\DateCallback;
use Application\DataAccess\UserDal;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Authorization\UnauthorizedException;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Users\Entity;
use Application\Model\Rest\Users\Resource as UsersResource;
use Application\Model\Rest\Applications\Resource as ApplicationsResource;
use ApplicationTest\AbstractResourceTest;
use Mockery;
use MongoDB\Collection as MongoCollection;
use MongoDB\UpdateResult;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var UsersResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new UsersResource($this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('userId', $this->resource->getIdentifier());
    }

    public function testGetName()
    {
        $this->assertEquals('users', $this->resource->getName());
    }

    public function testGetType()
    {
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $this->resource->getType());
    }

    public function testFetchCheckAccess()
    {
        $this->authorizationService->shouldReceive('isGranted')
            ->withArgs(['authenticated'])->times(1)
            ->andReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this resource');

        $this->resource->fetch(-1);
    }

    public function testFetchDoesNotExist()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn(null)->once();
        $userCollection->shouldReceive('insertOne')->once();
        $userDal = Mockery::mock(UserDal::class);
        $userDal->shouldReceive('findById')->andReturn(null)->once();
        $userDal->shouldReceive('injectEmailAddressFromIdentity')->andReturn($user->getEmail())->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->withUserDal($userDal)->build();

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
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldNotReceive('insertOne');
        $userDal = Mockery::mock(UserDal::class);
        $userDal->shouldReceive('findById')->andReturn($user)->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withUserCollection($userCollection)->withUserDal($userDal)->build();

        $entity = $resource->fetch($user->id);

        $this->assertEquals(new Entity($user), $entity);

        $resourceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        $this->authorizationService->shouldReceive('isGranted')
            ->withArgs(['authenticated'])->times(1)
            ->andReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this resource');

        $this->resource->update(null, -1);
    }

    public function testUpdateNotFound()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
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
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toArray(new DateCallback()))->once();
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
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toArray(new DateCallback()))->once();
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
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toArray(new DateCallback()))->once();
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
        $this->authorizationService->shouldReceive('isGranted')
            ->withArgs(['authenticated'])->times(1)
            ->andReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this resource');

        $this->resource->delete(-1);
    }

    public function testDelete()
    {
        $user = FixturesData::getUser();
        $applicationsResource = Mockery::mock(ApplicationsResource::class);
        $applicationsResource->shouldReceive('deleteAll')->once();
        $userCollection = Mockery::mock(MongoCollection::class);
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