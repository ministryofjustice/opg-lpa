<?php

namespace OpgTest\Lpa\DataModel;

use MongoDB\BSON\UTCDateTime as MongoDate;
use Opg\Lpa\DataModel\User\User;
use PHPUnit\Framework\TestCase;

/**
 * Using User as a proxy for AbstractData as User extends it
 *
 * Class AbstractDataTest
 * @package OpgTest\Lpa\DataModel
 */
class AbstractDataTest extends TestCase
{
    public function testConstructorNullData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON passed to constructor');
        new User('<HTML></HTML>');
    }

    public function testConstructorInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument passed to constructor');
        new User(new \DateTime());
    }

    public function testIsset()
    {
        $user = new User();
        $this->assertFalse(isset($user->NullProperty));
    }

    public function testGetInvalidProperty()
    {
        $user = new User();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NullProperty is not a valid property');
        $user->get('NullProperty');
    }

    public function testSetInvalidProperty()
    {
        $user = new User();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NullProperty is not a valid property');
        $user->set('NullProperty', null);
    }

    public function testSetMongoDate()
    {
        $user = new User();
        $mongoDate = new MongoDate();
        $user->set('createdAt', $mongoDate);
        $this->assertEquals($mongoDate->toDateTime(), $user->get('createdAt'));
    }

    public function testValidationAllGroups()
    {
        $user = FixturesData::getUser();

        $validatorResponse = $user->validateAllGroups();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationForApi()
    {
        $user = FixturesData::getUser();

        $validatorResponse = $user->validateForApi();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationSpecificProperty()
    {
        $name = new User();

        $validatorResponse = $name->validate(['id']);
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['id']);
    }

    public function testToArrayDateFormat()
    {
        $user = FixturesData::getUser();
        $userArray = $user->toArray('default');
        $this->assertTrue($userArray['createdAt'] instanceof \DateTime);
    }

    public function testGetArrayCopy()
    {
        $user = new User();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Is this used anywhere? If not I am going to remove it.');
        $user->getArrayCopy();
    }

    public function testJsonSerialize()
    {
        $user = new User();
        $json = $user->jsonSerialize();
        $this->assertTrue(is_array($json));
    }

    public function testToJson()
    {
        $user = new User();
        $json = $user->toJson();
        $this->assertStringStartsWith('{', $json);
        $this->assertStringEndsWith('}', $json);
        $this->assertContains('  ', $json);
    }

    public function testToJsonNotPretty()
    {
        $user = new User();
        $json = $user->toJson(false);
        $this->assertStringStartsWith('{', $json);
        $this->assertStringEndsWith('}', $json);
        $this->assertNotContains('  ', $json);
    }

    public function testFlatten()
    {
        $user = new User();
        $user->set('id', [1]);
        $flattened = $user->flatten('test');
        $this->assertEquals(1, $flattened['testid-0']);
    }

    public function testPopulateWithFlatArray()
    {
        $user = new User();
        $user->set('id', [1]);
        $flattened = $user->flatten('test');
        $unFlattened = $user->populateWithFlatArray($flattened);
        $this->assertEquals([1], $unFlattened->get('id'));
    }
}
