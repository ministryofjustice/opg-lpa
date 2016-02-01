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
    
    function it_exchanges_json_correctly_on_error()
    {
        $json = '{"failure_count": 1,"error_description": "resource owner credentials are invalid"}';
        
        $this->exchangeJson($json);
        
        $this->getFailureCount()->shouldReturn(1);
        $this->getErrorDescription()->shouldReturn("resource owner credentials are invalid");
    }
    
    function it_exchanges_json_correctly_on_success()
    {
        $json = '{"access_token": "3cc93de098b7217eeb36251ffe22ffb1","expires_in": 4500,"token_type": "bearer","last_login": 1420649745}';
    
        $this->exchangeJson($json);
    
        $this->getToken()->shouldReturn('3cc93de098b7217eeb36251ffe22ffb1');
        $this->getExpiresIn()->shouldReturn(4500);
        $this->getTokenType()->shouldReturn('bearer');
        $this->getLastLogin()->shouldReturn(1420649745);
    }
}
