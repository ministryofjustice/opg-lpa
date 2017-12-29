<?php

namespace OpgTest\Lpa\DataModel\Common;

use DateTime;
use Opg\Lpa\DataModel\Common\Dob;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class DobTest extends TestCase
{
    public function testNonDatePropertyDoesNotMap()
    {
        $dob = new TestableDob();
        $mapped = $dob->testMap('notDate', 'date');

        $this->assertEquals('date', $mapped);
    }

    public function testNumberDoesNotMap()
    {
        $dob = new TestableDob();
        $mapped = $dob->testDateMap(23);

        $this->assertEquals('0', $mapped);
    }

    public function testDateIsReturned()
    {
        $dob = new TestableDob();
        $v = new \DateTime();
        $mapped = $dob->testDateMap($v);

        $this->assertEquals($v, $mapped);
        $this->assertTrue($v === $mapped);
    }

    public function testDayAndMonthDoesNotMap()
    {
        $dob = new TestableDob();
        $mapped = $dob->testDateMap('01-02');

        $this->assertEquals('0', $mapped);
    }

    public function testDateMap()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('26-10-1985 00:00:00');
        $mapped = $dob->testDateMap('1985-10-26');

        $this->assertEquals($expected, $mapped);
    }

    public function testDateMapNoZeros()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('01-02-1985 00:00:00');
        $mapped = $dob->testDateMap('1985-2-1');

        $this->assertEquals($expected, $mapped);
    }

    public function testDateTimeMap()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('26-10-1985 01:21:34');
        $mapped = $dob->testDateMap('1985-10-26T01:21:34.000000+0000');

        $this->assertEquals($expected, $mapped);
    }

    public function testDateTimeMapZeroTime()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('01-12-1982 00:00:00');
        $mapped = $dob->testDateMap('1982-12-01T00:00:00.000000+0000');

        $this->assertEquals($expected, $mapped);
    }

    public function testDateMapLeadingZeros()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('26-10-1985 00:00:00');
        $mapped = $dob->testDateMap('01985-010-026');

        $this->assertEquals($expected, $mapped);
    }

    public function testDateMapLeadingZerosNoZeros()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('01-02-1985 00:00:00');
        $mapped = $dob->testDateMap('01985-002-001');

        $this->assertEquals($expected, $mapped);
    }

    public function testMapIso8601()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('07-10-1948 00:00:00');
        $mapped = $dob->testDateMap('1948-10-07T00:00:00.000Z');

        $this->assertEquals($expected, $mapped);
    }

    public function testMapMalformedIso8601()
    {
        $dob = new TestableDob();
        $expected = new \DateTime('07-10-1948 00:00:00');
        $mapped = $dob->testDateMap('1948-10-07T00:00:00.000');

        $this->assertEquals($expected, $mapped);
    }

    public function testStringDateDoesNotMap()
    {
        $dob = new TestableDob();
        $mapped = $dob->testDateMap('1st-Feb-1997');

        $this->assertEquals('0', $mapped);
    }

    public function testValidation()
    {
        $dob = new TestableDob();
        $dob->set('date', new \DateTime('07-10-1948 00:00:00'));

        $validatorResponse = $dob->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $dob = new TestableDob();

        $validatorResponse = $dob->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['date']);
        $this->assertEquals('cannot-be-blank', $errors['date']['messages'][0]);
    }

    public function testValidationFailedInFuture()
    {
        $dob = new TestableDob();
        $dob->set('date', new \DateTime('2199-01-01'));

        $validatorResponse = $dob->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['date']);
        $this->assertEquals('must-be-less-than-or-equal-to-today', $errors['date']['messages'][0]);
    }

    public function testValidationFailedDateString()
    {
        $dob = new TestableDob();
        $dob->set('date', 'Monday');

        $validatorResponse = $dob->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['date']);
        $this->assertEquals('expected-type:DateTime', $errors['date']['messages'][0]);
    }

    public function testValidationFailedNotUtc()
    {
        $dob = new TestableDob();
        $dob->set('date', new \DateTime('07-10-1948 00:00:00', new \DateTimeZone('America/New_York')));

        $validatorResponse = $dob->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['date']);
        $this->assertEquals('timezone-not-utc', $errors['date']['messages'][0]);
    }

    public function testGetsAndSets()
    {
        $model = new Dob();

        $now = new DateTime();

        $model->setDate($now);

        $this->assertEquals($now, $model->getDate());
    }
}
