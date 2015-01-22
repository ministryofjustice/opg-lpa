<?php
namespace spec\Opg\Lpa\Api\Client;

include "spec/SpecHelper.php";

use PhpSpec\ObjectBehavior;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class ClientSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Opg\Lpa\Api\Client\Client');
    }
    
    function it_will_return_null_if_replacement_attorney_decisions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getReplacementAttorneyDecisions($lpaId)->shouldBe(null);
    }
    
    function skipped_it_can_set_and_get_the_replacement_attorney_decisions()
    {
        $decisions = data_replacementAttorneyDecisions();
        $decisions2 = clone($decisions);
        $decisions2->howDetails = 'Second Object';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setReplacementAttorneyDecisions($lpaId, $decisions)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions->toJson());
        $this->setReplacementAttorneyDecisions($lpaId, $decisions2)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions2->toJson());
    }
    
    function skipped_it_can_delete_the_replacement_attorney_decisions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setReplacementAttorneyDecisions($lpaId, data_replacementAttorneyDecisions());
        $this->deleteReplacementAttorneyDecisions($lpaId)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->shouldBe(null);
    }
    
    function it_will_return_null_if_primary_attorney_decisions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getPrimaryAttorneyDecisions($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_the_primary_attorney_decisions()
    {
        $decisions = data_primaryAttorneyDecisions();
        $decisions2 = clone($decisions);
        $decisions2->howDetails = 'Second Object';
        
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions)->shouldBe(true);
        $this->getPrimaryAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions->toJson());
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions2)->shouldBe(true);
        $this->getPrimaryAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions2->toJson());
    }
    
    function it_can_delete_the_primary_attorney_decisions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setPrimaryAttorneyDecisions($lpaId, data_primaryAttorneyDecisions());
        $this->deletePrimaryAttorneyDecisions($lpaId)->shouldBe(true);
        $this->getPrimaryAttorneyDecisions($lpaId)->shouldBe(null);
    }
    
    function it_can_lock_an_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->lockLpa($lpaId)->shouldBe(true);
    }
    
    function it_will_report_an_already_locked_lpa()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->lockLpa($lpaId)->shouldBe(true);
        $this->lockLpa($lpaId)->shouldBe(false);
        $this->getLastStatusCode()->shouldBe(403);
    }
    
    function it_will_return_null_if_instructions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getInstructions($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_the_lpa_instructions()
    {
        $prefString1 = 'These are my instructions';
        $prefString2 = 'These are my instructions too';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setInstructions($lpaId, $prefString1)->shouldBe(true);
        $this->getInstructions($lpaId)->shouldBe($prefString1);
        $this->setInstructions($lpaId, $prefString2)->shouldBe(true);
        $this->getInstructions($lpaId)->shouldBe($prefString2);
    }
    
    function it_can_delete_the_instructions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setInstructions($lpaId, 'some-dummy-string');
        $this->deleteInstructions($lpaId)->shouldBe(true);
        $this->getInstructions($lpaId)->shouldBe(null);
    }
    
    function it_will_return_null_if_preferences_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getPreferences($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_the_lpa_preferences()
    {
        $prefString1 = 'These are my preferences';
        $prefString2 = 'These are my preferences too';
        
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setPreferences($lpaId, $prefString1)->shouldBe(true);
        $this->getPreferences($lpaId)->shouldBe($prefString1);
        $this->setPreferences($lpaId, $prefString2)->shouldBe(true);
        $this->getPreferences($lpaId)->shouldBe($prefString2);
    }
    
    function it_can_delete_the_preferences()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setPreferences($lpaId, 'some-dummy-string');
        $this->deletePreferences($lpaId)->shouldBe(true);
        $this->getPreferences($lpaId)->shouldBe(null);
    }
    
    function it_will_return_null_if_type_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getType($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_the_lpa_type()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setType($lpaId, Document::LPA_TYPE_HW)->shouldBe(true);
        $this->getType($lpaId)->shouldBe(Document::LPA_TYPE_HW);
        $this->setType($lpaId, Document::LPA_TYPE_PF)->shouldBe(true);
        $this->getType($lpaId)->shouldBe(Document::LPA_TYPE_PF);
    }
    
    function it_will_fail_if_attempting_to_set_an_invalid_type()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setType($lpaId, 'this-is-not-valid')->shouldBe(false);
    }
    
    function it_can_delete_the_type()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setType($lpaId, Document::LPA_TYPE_PF);
        $this->deleteType($lpaId)->shouldBe(true);
        $this->getType($lpaId)->shouldBe(null);
    }
    
    function it_can_get_an_existing_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getApplication($lpaId)->get('id')->shouldBe($lpaId);
    }
    
    function it_will_return_false_if_trying_to_get_a_non_existent_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $this->getApplication(uniqid())->shouldBe(false);
    }
    
    function it_can_delete_an_existing_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getApplication($lpaId)->get('id')->shouldBe($lpaId);
        $this->deleteApplication($lpaId)->shouldBe(true);
        $this->deleteApplication($lpaId)->shouldBe(false);
        $this->getApplication($lpaId)->shouldBe(false);
    }
    
    function it_will_fail_if_attempting_to_delete_a_non_existent_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $this->deleteApplication(uniqid())->shouldBe(false);
        $this->getLastStatusCode()->shouldBe(404);
    }
    
    function it_will_succeed_if_attempting_to_delete_a_non_existent_application_when_passed_the_flag_to_do_so()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $this->deleteApplication(uniqid(), true)->shouldBe(true);
        $this->getLastStatusCode()->shouldBe(404);
    }
    
    function it_can_be_constructed_from_an_auth_token_then_create_an_application()
    {
        $this->beConstructedWith(getTestUserToken());
        $this->createApplication()->shouldBeAPositiveInteger();
    }

    function it_can_be_constructed_from_an_auth_token_then_update_an_account_email_on_the_auth_server()
    {
        $this->beConstructedWith(getTestUserToken());
        $newEmail = 'deleteme-' . uniqid() . '@example.com';
    
        $this->updateAuthEmail(
            $newEmail
        )->shouldBe(true);
    
        $this->authenticate($newEmail, TEST_AUTH_PASSWORD)->isAuthenticated()->shouldBe(true);
        
        /** Switch it back again **/
        $this->updateAuthEmail(
            TEST_AUTH_EMAIL
        )->shouldBe(true);
    }
    
    function it_can_create_a_new_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $this->createApplication()->shouldBeAPositiveInteger();
    }
    
    function it_can_retrieve_about_me_details()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $this->setAboutMe(data_User());
        
        $expectedAddressLine1 = data_User()->address->address1;
        $this->getAboutMe()->address->address1->shouldBe($expectedAddressLine1);
    }

    function it_can_set_about_me_details()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD)->isAuthenticated()->shouldBe(true);
        $this->setAboutMe(data_User())->shouldBe(true);
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
    
    function it_will_report_an_email_already_exists_error()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        
        $this->registerAccount(
            $email,
            'password' . uniqid()
        )->shouldBeAnActivationToken();
    
        $this->registerAccount(
            $email,
            'password' . uniqid()
        );
        
        $this->getLastStatusCode()->shouldBe(400);
        
        $this->getLastContent()->shouldBe([
            'error'=>'invalid_request',
            'error_description'=>'email address is already registered'
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
       
        $this->authenticate(
            $email,
            $password
        )->isAuthenticated()->shouldBe(true);
    }
    
    function it_can_update_an_account_email_on_the_auth_server()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $newEmail = 'deleteme-' . uniqid() . '@example.com';
        $password = 'password' . uniqid();
    
        $activationToken = $this->registerAccount(
            $email,
            $password
        );
    
        $this->activateAccount($activationToken)->shouldBe(true);
    
        $authResponse = $this->authenticate(
            $email,
            $password
        );
    
        $this->updateAuthEmail(
            $newEmail
        )->shouldBe(true);
    
        $this->authenticate($newEmail, $password)->isAuthenticated()->shouldBe(true);
    }
    
    function it_can_update_an_account_password_on_the_auth_server()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $newPassword = 'password' . uniqid();
        $password = 'password' . uniqid();
    
        $activationToken = $this->registerAccount(
            $email,
            $password
        );
    
        $this->activateAccount($activationToken)->shouldBe(true);
    
        $authResponse = $this->authenticate(
            $email,
            $password
        );
    
        $this->updateAuthPassword(
            $newPassword
        )->shouldBe(true);
    
        $this->authenticate($email, $newPassword)->isAuthenticated()->shouldBe(true);
    }
    
    function it_can_delete_an_account()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $password = uniqid();
    
        $activationToken = $this->registerAccount($email, $password);
    
        $this->activateAccount($activationToken)->shouldBe(true);
    
        $authToken = $this->authenticate(
            $email,
            $password
        )->getToken();
    
        $this->deleteUserAndAllTheirLpas($authToken)->shouldBe(true);
    
        $this->authenticate(
            $email,
            $password
        )->isAuthenticated()->shouldBe(false);
    
        $this->getLastStatusCode()->shouldBe(400);
        $this->getLastContent()->shouldBe([
            'error' => 'invalid_request',
            'error_description' => 'user not found'
        ]);
    }
    
    function it_will_return_the_username_when_given_a_valid_token()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $password = uniqid();
    
        $activationToken = $this->registerAccount($email, $password);
    
        $this->activateAccount($activationToken)->shouldBe(true);
        
        $authToken = $this->authenticate(
            $email,
            $password
        )->getToken();
        
        $this->getEmailFromToken($authToken)->shouldBe($email);
    }
    
    function it_can_get_a_list_of_applications()
    {
        $numApplications = 2;
        
        destroyAndRecreateTestUser();
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        for ($i=0; $i<$numApplications; $i++) {
            $this->createApplication();
        }
    
        $this->getApplicationList()->shouldBeAnArrayOfLpaObjects($numApplications);
    }
    
    /**
     * This one takes a long time - re-instate it to test pagination
     * from the API - perhaps by changing the pagination in the API to
     * something smaller and changing the number of applications
     * tested here.
     * 
     * To reinstate, remove the "skipped_" from the function name
     */
    function skipped_it_can_combine_several_pages_of_applications_into_a_single_array()
    {
        $numApplications = 900;
        
        destroyAndRecreateTestUser();
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        for ($i=0; $i<$numApplications; $i++) {
            $this->createApplication();
        }
    
        $this->getApplicationList()->shouldBeAnArrayOfLpaObjects($numApplications);
    }
       
    public function getMatchers()
    {
        return [
            'beAnActivationToken' => function($subject) {
                return preg_match('/^[a-z0-9]{32}$/', $subject) !== false;
            },
            'beAPositiveInteger' => function($subject) {
                return is_numeric($subject) && $subject > 0;
            },
            'beAnArrayOfLpaObjects' => function($subject, $count) {
                return 
                    is_array($subject) && 
                    count($subject) == $count && 
                    $subject[0] instanceof \Opg\Lpa\DataModel\Lpa\Lpa;
            },
        ];
    }
}
