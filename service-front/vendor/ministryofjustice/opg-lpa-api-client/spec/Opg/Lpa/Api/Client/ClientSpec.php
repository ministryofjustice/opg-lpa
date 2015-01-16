<?php

namespace spec\Opg\Lpa\Api\Client;

use PhpSpec\ObjectBehavior;

class ClientSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Opg\Lpa\Api\Client\Client');
    }
    
    function it_can_create_an_account_through_the_auth_server()
    {
        $this->registerAccount(
            'deleteme-' . uniqid() . '@example.com',
            'password' . uniqid()
        )->shouldBeAnActivationToken();
    }
    
    function it_will_return_a_registration_error_on_bad_email()
    {
        $this->registerAccount(
            'deleteme-' . uniqid() . 'example.com',
            'password' . uniqid()
        );
        
        $this->getLastStatusCode()->shouldBe(400);
        $this->getLastContent()->shouldBe([
            'error'=>'invalid_request',
            'error_description'=>'username is not a valid email address'
        ]);
    }
    
    function it_can_activate_a_registered_account()
    {
        $activationToken = $this->registerAccount(
            'deleteme-' . uniqid() . '@example.com',
            'password' . uniqid()
        );
    
        $this->activateAccount($activationToken)->shouldBe(true);
    }
    
    function it_will_not_activate_when_given_a_bad_token()
    {
        $this->activateAccount('IAmABadToken')->shouldBe(false);
    }
    
    function it_will_log_an_activation_failure()
    {
        $this->activateAccount('IAmABadToken')->shouldBe(false);
        
        // @todo - this is what the auth server currently returns on a bad token
        // we should investigate what it should return - is the auth server
        // working correctly? 
        $this->getLastStatusCode()->shouldBe(500);
        $this->getLastContent()->shouldBe('An error occurred during execution; please try again later.');
    }
    
    function it_can_authenticate_against_the_auth_server()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $password = uniqid();
        
        $activationToken = $this->registerAccount($email, $password);
        
        $this->activateAccount($activationToken)->shouldBe(true);
        
        $authResponse = $this->authenticate(
            $email,
            $password
        )->isAuthenticated()->shouldBe(true);
    }
    
    public function getMatchers()
    {
        return [
            'beAnActivationToken' => function($subject) {
                return preg_match('/^[a-z0-9]{32}$/', $subject) !== false;
            },
        ];
    }
}
