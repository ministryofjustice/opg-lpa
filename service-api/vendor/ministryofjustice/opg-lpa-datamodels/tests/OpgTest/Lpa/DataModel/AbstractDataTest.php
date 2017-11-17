<?php

namespace OpgTest\Lpa\DataModel;

use InvalidArgumentException;
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
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid JSON passed to constructor
     */
    public function testConstructorNullData()
    {
        new User('<HTML></HTML>');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid argument passed to constructor
     */
    public function testConstructorInvalidData()
    {
        new User(new \DateTime());
    }

    public function testIsset()
    {
        $user = new User();
        $this->assertFalse(isset($user->NullProperty));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage NullProperty is not a valid property
     */
    public function testGetInvalidProperty()
    {
        $user = new User();
        $user->get('NullProperty');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage NullProperty is not a valid property
     */
    public function testSetInvalidProperty()
    {
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
