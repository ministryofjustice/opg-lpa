<?php

namespace MakeSharedTest\DataModel;

use InvalidArgumentException;
use MakeShared\DataModel\Lpa\Lpa;
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
        $lpa = new Lpa();
        $lpa->set('metadata', [1]);
        $flattenedUser = $lpa->flatten('test');

        $this->assertEquals(1, $flattenedUser['testmetadata-0']);
    }

    public function testPopulateWithFlatArray()
    {
        $lpa = new Lpa();
        $lpa->set('metadata', [1]);
        $flattenedUser = $lpa->flatten('test');
        $unFlattenedUser = $lpa->populateWithFlatArray($flattenedUser);
        $this->assertEquals([1], $unFlattenedUser->get('metadata'));
    }

    public function testUnflatten()
    {
        $user = new User();
        $testArray = [ "foo" => "bar" ,
            "name-title" => "Mr" ,
            "name-first" => "Test" ,
            "name-last" => "User" ,
            "dob-date" => "1982-11-28" ,
            "address-address1" => "THE PUBLIC GUARDIAN" ,
            "address-address2" => "EMBANKMENT HOUSE" ,
            "address-address3" => "ELECTRIC AVENUE, NOTTINGHAM" ,
            "address-postcode" => "NG2 1AR",
            "id" => 1
        ];
        $unFlattenedUser = $user->populateWithFlatArray($testArray);
        $userName = $unFlattenedUser->getName();
        $userAddress = $unFlattenedUser->getAddress();
        $this->assertEquals("Mr", $userName->getTitle());
        $this->assertEquals("Test", $userName->getFirst());
        $this->assertEquals("User", $userName->getLast());
        $this->assertEquals("1982-11-28", $unFlattenedUser->getDob()->getDate()->format('Y-m-d'));
        $this->assertEquals("THE PUBLIC GUARDIAN", $userAddress->getAddress1());
        $this->assertEquals("EMBANKMENT HOUSE", $userAddress->getAddress2());
        $this->assertEquals("ELECTRIC AVENUE, NOTTINGHAM", $userAddress->getAddress3());
        $this->assertEquals("NG2 1AR", $userAddress->getPostcode());
        $this->assertEquals(1, $unFlattenedUser->getId());

        // Test that original array is unchanged.  Original unflatten function used pass-by-reference with potential side effects,
        // so prove this is no longer the case.
        $this->assertEquals($testArray["foo"], "bar");
        $this->assertEquals("Mr", $testArray["name-title"]);
        $this->assertEquals("Test", $testArray["name-first"]);
        $this->assertEquals("User", $testArray["name-last"]);
        $this->assertEquals("1982-11-28", $testArray["dob-date"]);
        $this->assertEquals("THE PUBLIC GUARDIAN", $testArray["address-address1"]);
        $this->assertEquals("EMBANKMENT HOUSE", $testArray["address-address2"]);
        $this->assertEquals("ELECTRIC AVENUE, NOTTINGHAM", $testArray["address-address3"]);
        $this->assertEquals("NG2 1AR", $testArray["address-postcode"]);
        $this->assertEquals(1, $testArray["id"]);
    }
}
