<?php

namespace ApplicationTest\Library\Random;

use Application\Library\Random\Csprng;
use PHPUnit\Framework\TestCase;

class CsprngTest extends TestCase
{
    /**
     * @var Csprng
     */
    private $csprng;

    public function setUp() : void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->csprng = new Csprng();
    }

    public function testGetBytes() : void
    {
        $result = $this->csprng->GetBytes(10);

        $this->assertTrue(is_string($result));
        $this->assertEquals(10, strlen($result));
    }

    public function testGenerateToken() : void
    {
        $result = $this->csprng->GenerateToken();

        $this->assertTrue(is_string($result));
        $this->assertEquals(128, strlen($result));
    }

    public function testGetInt() : void
    {
        $results = [];

        for($i = 0; $i < 40; $i++) {
            $results[] = $this->csprng->GetInt(-1, 2);

            $this->lessThan(3);
            $this->greaterThan(-2);
        }

        $this->assertContains(-1, $results);
        $this->assertContains(0, $results);
        $this->assertContains(1, $results);
        $this->assertContains(2, $results);
    }

    public function testGenerateString() : void
    {
        $result = $this->csprng->GenerateString();

        $this->assertTrue(is_string($result));
        $this->assertEquals(32, strlen($result));

        for($i = 0; $i < strlen($result); $i++) {
            $c = $result[$i];

            $this->assertContains($c, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
        }
    }
}
