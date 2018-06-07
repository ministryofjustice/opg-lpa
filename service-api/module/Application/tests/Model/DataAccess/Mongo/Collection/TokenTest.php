<?php

namespace ApplicationTest\Model\DataAccess\Mongo\Collection;

use Application\Model\DataAccess\Mongo\Collection\Token;
use DateTime;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\UTCDateTime;

class TokenTest extends MockeryTestCase
{
    public function testReturnDateFieldKeyNotFound()
    {
        $token = new Token([]);

        $this->assertEquals(false, $token->createdAt());
    }

    public function testReturnDateFieldDateTime()
    {
        $date = new DateTime();

        $token = new Token(['createdAt' => $date]);

        $this->assertEquals($date, $token->createdAt());
    }

    public function testReturnDateFieldUTCDateTime()
    {
        $date = new UTCDateTime();

        $token = new Token(['createdAt' => $date]);

        $this->assertEquals($date->toDateTime(), $token->createdAt());
    }

    public function testReturnDateFieldString()
    {
        $date = new DateTime();

        $token = new Token(['createdAt' => $date->format('U')]);

        $this->assertEquals(DateTime::createFromFormat('U', $date->format('U')), $token->createdAt());
    }

    public function testGets()
    {
        $date = new DateTime();

        $token = new Token([
            'token' => 'unit-test',
            'user' => 'unit@test.com',
            'expiresAt' => $date,
            'updatedAt' => $date,
            'createdAt' => $date
        ]);

        $this->assertEquals('unit-test', $token->id());
        $this->assertEquals('unit@test.com', $token->user());
        $this->assertEquals($date, $token->expiresAt());
        $this->assertEquals($date, $token->updatedAt());
        $this->assertEquals($date, $token->createdAt());
    }
}