<?php

namespace MakeSharedTest\DataModel;

use InvalidArgumentException;
use MakeShared\DataModel\User\User;
use PHPUnit\Framework\TestCase;

/**
 * Using User as a proxy for AbstractData as User extends it
 *
 * Class AbstractDataTest
 * @package MakeSharedTest\DataModel
 */
class AbstractDataTest extends TestCase
{
    public function testConstructorNullData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON passed to constructor');
        new User('<HTML></HTML>');
    }

    public function testConstructorInvalidData()
    {
        $this->expectException(InvalidArgumentException::class);
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NullProperty is not a valid property');
        $user = new User();
        $user->get('NullProperty');
    }

    public function testSetInvalidProperty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NullProperty is not a valid property');
        $user = new User();
        $user->set('NullProperty', null);
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
        $this->assertStringContainsString('  ', $json);
    }

    public function testToJsonNotPretty()
    {
        $user = new User();
        $json = $user->toJson(false);
        $this->assertStringStartsWith('{', $json);
        $this->assertStringEndsWith('}', $json);
        $this->assertStringNotContainsString('  ', $json);
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
