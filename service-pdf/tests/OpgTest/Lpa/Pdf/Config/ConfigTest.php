<?php

namespace OpgTest\Lpa\Pdf\Config;

use Opg\Lpa\Pdf\Config\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetInstance()
    {
        $configObj1 = Config::getInstance();
        $configObj2 = Config::getInstance();

        $this->assertEquals(spl_object_hash($configObj1), spl_object_hash($configObj2));
    }

    public function testOffsetSetOffsetGet()
    {
        $some = [
            'new' => [
                'config' => true,
            ]
        ];

        $configObj = Config::getInstance();

        $configObj->offsetSet('some-new-config', $some);

        $this->assertEquals($configObj->offsetGet('some-new-config'), $some);
    }

    public function testOffsetSetNoKey()
    {
        $some = [
            'other' => [
                'config' => false,
            ]
        ];

        $configObj = Config::getInstance();

        $configObj->offsetSet(null, $some);

        $this->assertEquals($configObj->offsetGet(0), $some);
    }

    public function testOffsetExists()
    {
        $configObj = Config::getInstance();

        $this->assertFalse($configObj->offsetExists('ghi'));

        $configObj->offsetSet('ghi', 'some-text-string');

        $this->assertTrue($configObj->offsetExists('ghi'));
    }

    public function testOffsetUnset()
    {
        $configObj = Config::getInstance();

        $this->assertFalse($configObj->offsetExists('jkl'));

        $configObj->offsetSet('jkl', 12345);

        $this->assertTrue($configObj->offsetExists('jkl'));

        $configObj->offsetUnset('jkl');

        $this->assertFalse($configObj->offsetExists('jkl'));
    }

    public function testCount()
    {
        $configObj = Config::getInstance();
        $configObjCount = $configObj->count();

        $configObj->offsetSet('abc', true);
        $configObj->offsetSet('def', false);

        $this->assertEquals($configObj->count(), $configObjCount + 2);
    }

    public function testMerge()
    {
        $data1 = [
            'data1' => true,
            'data2' => null,
            'data3' => false,
            5       => 'something',
        ];

        $data2 = [
            'data1' => true,
            'data2' => 'abc',
            'data4' => 987,
            5       => 'else',
        ];

        $combinedData = Config::merge($data1, $data2);

        $this->assertSame($combinedData, [
            'data1' => true,
            'data2' => 'abc',
            'data3' => false,
            5       => 'something',
            'data4' => 987,
            6       => 'else',
        ]);
    }

    public function tearDown()
    {
        Config::destroy();
    }
}
