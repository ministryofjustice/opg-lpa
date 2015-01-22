<?php
include 'vendor/autoload.php';

use Opg\Lpa\Api\Client\Client;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\DataModel\User\Name;
use Opg\Lpa\DataModel\User\Address as UserAddress;
use Opg\Lpa\DataModel\User\Dob as UserDob;
use Opg\Lpa\DataModel\User\EmailAddress as UserEmailAddress;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

date_default_timezone_set('UTC');

const TEST_AUTH_EMAIL = 'phpSpecTestAccount2@example.com';
const TEST_AUTH_PASSWORD = 'phpSpec$12Password';

destroyAndRecreateTestUser();

function destroyAndRecreateTestUser()
{
    $apiClient = new Client();
    
    $authResponse = $apiClient->authenticate(
        TEST_AUTH_EMAIL,
        TEST_AUTH_PASSWORD
    );
    
    if ($authResponse->isAuthenticated()) {
        $apiClient->deleteUserAndAllTheirLpas($authResponse->getToken());
    }
    
    $activationToken = $apiClient->registerAccount(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    $apiClient->activateAccount($activationToken);
    
    $authResponse = $apiClient->authenticate(
        TEST_AUTH_EMAIL,
        TEST_AUTH_PASSWORD
    ); 
}

function getTestUserToken()
{
    $apiClient = new Client();
    
    $authResponse = $apiClient->authenticate(
        TEST_AUTH_EMAIL,
        TEST_AUTH_PASSWORD
    );
    
    return $authResponse->getToken();
}

function data_User()
{
    $user = new User();
    $name = new Name();
    $address = new UserAddress();
    $dob = new UserDob();
    $date = new DateTime('2010-12-17T00:00:00.000000+0000');
    $dob->date = $date;
    $email = new UserEmailAddress();
    $email->address = 'chris@example.com';
    
    $name->title = 'Mr';
    $name->first = 'Chris';
    $name->last = 'Smith';
    
    $address->address1 = '1 Kingston Hill';
    $address->address2 = 'Bastown';
    $address->postcode = 'KN12 2PL';
    $address->country = 'GB';
    
    $user->dob = $dob;
    $user->name = $name;
    $user->address = $address;
    $user->email = $email;
    $user->updatedAt = $date;
    $user->createdAt = $date;
    $user->id = substr(uniqid() . uniqid() . uniqid(), 0, 32);
    
    if ($user->validate()->hasErrors()) {
        echo 'ERRORS!';
    }
    
    return $user;
}

function data_primaryAttorneyDecisions()
{
    $decisions = new PrimaryAttorneyDecisions();
    
    $decisions->how = 'depends';
    $decisions->when = 'no-capacity';
    $decisions->canSustainLife = true;
    $decisions->howDetails = 'Extra details';
    
    return $decisions;
}

function data_replacementAttorneyDecisions()
{
    $decisions = new ReplacementAttorneyDecisions();

    $decisions->how = 'depends';
    $decisions->when = 'no-capacity';
    $decisions->howDetails = 'How details';
    $decisions->whenDetails = 'When details';
    
    return $decisions;
}

