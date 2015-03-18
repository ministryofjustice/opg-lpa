<?php
include 'vendor/autoload.php';

use Opg\Lpa\Api\Client\Client;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\DataModel\User\Name as UserName;
use Opg\Lpa\DataModel\User\Address as UserAddress;
use Opg\Lpa\DataModel\User\Dob as UserDob;
use Opg\Lpa\DataModel\User\EmailAddress as UserEmailAddress;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;

date_default_timezone_set('UTC');

const TEST_AUTH_EMAIL = 'phpSpecTestAccount7000@example.com';
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
        $success = $apiClient->deleteUserAndAllTheirLpas();
        
        if (!$success) {
            echo PHP_EOL . 'Cannot run test suite - failed to set up user' . PHP_EOL . PHP_EOL;
            die;
        }
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

function getPopulatedEntity($entityName)
{
    $parts = explode('\\', $entityName);
    
    $filename = $parts[count($parts)-1];
    
    return new $entityName(file_get_contents('spec/data/' . $filename . '.json'));
}

function data_User()
{
    $user = new User();
    $name = new UserName();
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
