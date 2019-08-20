<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use DateTime;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\DataAccess\Postgres\TokenModel as Token;

class TokenModelTest extends MockeryTestCase
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

    public function testReturnDateFieldString()
    {
        $date = new DateTime();

        $token = new Token(['createdAt' => $date->format('c')]);

        // We check the timestamps to avoid issues comparing milliseconds.
        $this->assertEquals($date->getTimestamp(), $token->createdAt()->getTimestamp());
    }

    public function testGets()
    {
        $date = new DateTime();

        $token = new Token([
            'token' => 'unit-test',
            'expiresAt' => $date,
            'updatedAt' => $date,
            'createdAt' => $date
        ]);

        $this->assertEquals('unit-test', $token->id());
        $this->assertEquals($date, $token->expiresAt());
        $this->assertEquals($date, $token->updatedAt());
        $this->assertEquals($date, $token->createdAt());
    }
}