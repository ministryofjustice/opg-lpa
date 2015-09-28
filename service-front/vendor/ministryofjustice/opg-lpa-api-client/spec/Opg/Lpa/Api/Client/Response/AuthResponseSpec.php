<?php

namespace spec\Opg\Lpa\Api\Client\Response;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AuthResponseSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Opg\Lpa\Api\Client\Response\AuthResponse');
    }
    
    function it_exchanges_json_correctly_on_success()
    {
        $json = '
        {
  "userId": "3f4e575332eb82eeece2312141a24add",
  "username": "test@example.com",
  "last_login": "2015-09-15T14:32:55+0000",
  "token": "d57fa9a990c29612eb4029519bb1c572bc4341d0a1a03f5bf7564b678e4aca61",
  "expiresIn": 4500,
  "expiresAt": "2015-09-15T16:00:38+0000"
}';
    
        $this->exchangeJson($json);
    
        $this->getUserId()->shouldReturn('3f4e575332eb82eeece2312141a24add');
        $this->getToken()->shouldReturn('d57fa9a990c29612eb4029519bb1c572bc4341d0a1a03f5bf7564b678e4aca61');
        $this->getExpiresIn()->shouldReturn(4500);
        $this->getUsername()->shouldReturn('test@example.com');
        $this->getLastLogin()->shouldReturn("2015-09-15T14:32:55+0000");
        $this->getExpiresAt()->shouldReturn('2015-09-15T16:00:38+0000');
    }
}
