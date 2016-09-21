<?php
include 'vendor/autoload.php';

use Opg\Lpa\Api\Client\Client;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\DataModel\User\Name as UserName;
use Opg\Lpa\DataModel\User\Address as UserAddress;
use Opg\Lpa\DataModel\User\Dob as UserDob;
use Opg\Lpa\DataModel\User\EmailAddress as UserEmailAddress;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Opg\Lpa\DataModel\Lpa\Document\Document;

date_default_timezone_set('UTC');

const TEST_AUTH_EMAIL = 'phpSpecTestAccount2222512313@example.com';
const TEST_AUTH_PASSWORD = 'phpSpec2$Pa%ssword';

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

function getANewCompletedLpa($apiClient)
{
    destroyAndRecreateTestUser();
    
    $authResponse = $apiClient->authenticate(
        TEST_AUTH_EMAIL,
        TEST_AUTH_PASSWORD
    );
    
    $lpaId = $apiClient->createApplication();
    
    $apiClient->setType($lpaId, Document::LPA_TYPE_HW)->shouldBe(true);
    
    $apiClient->setDonor($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Donor'));
    
    $apiClient->addPrimaryAttorney(
        $lpaId,
        getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human')
    )->shouldBe(true);
    
    $apiClient->setPrimaryAttorneyDecisions(
        $lpaId,
        getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions')
    )->shouldBe(true);
    
    $apiClient->setCertificateProvider(
        $lpaId,
        getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider')
    )->shouldBe(true);
    
    $apiClient->setWhoIsRegistering($lpaId, 'donor')->shouldBe(true);
    
    $apiClient->setCorrespondent(
        $lpaId,
        getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Correspondence')
    )->shouldBe(true);
    
    $apiClient->setWhoAreYou(
        $lpaId,
        getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou')
    )->shouldBe(true);
    
    $apiClient->setPreferences($lpaId, 'I prefer...')->shouldBe(true);
    $apiClient->setInstructions($lpaId, 'I instruct...')->shouldBe(true);
    
    $apiClient->setPayment(
        $lpaId,
        getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Payment\Payment')
    )->shouldBe(true);
    
    $apiClient->lockLpa($lpaId);
    
    return $lpaId;
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
