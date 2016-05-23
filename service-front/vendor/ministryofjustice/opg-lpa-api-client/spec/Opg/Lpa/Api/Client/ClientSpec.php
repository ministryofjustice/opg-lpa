<?php
namespace spec\Opg\Lpa\Api\Client;

include "spec/SpecHelper.php";

use PhpSpec\ObjectBehavior;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use ZendPdf\PdfDocument;

class ClientSpec extends ObjectBehavior
{
    function it_can_create_an_account_through_the_auth_server()
    {
        $this->registerAccount(
            'deleteme-' . uniqid() . '@example.com',
            'P$ssword' . uniqid()
        )->shouldBeAToken();
    }
    
    function it_will_return_a_registration_error_on_bad_email()
    {
        $this->registerAccount(
            'deleteme-' . uniqid() . 'example.com',
            'P$ssword' . uniqid()
        );
         
        $this->getLastStatusCode()->shouldBe(400);
        $this->getLastContent()['detail']->shouldBe('invalid-username');
    }
    
    function it_will_report_an_email_already_exists_error()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
         
        $this->registerAccount(
            $email,
            'P$assword' . uniqid()
        )->shouldBeAToken();
         
        $this->registerAccount(
            $email,
            'P$assword' . uniqid()
        );
         
        $this->getLastStatusCode()->shouldBe(400);
         
        $this->getLastContent()['detail']->shouldBe('username-already-exists');
    }
    
    function it_can_activate_a_registered_account()
    {
        $activationToken = $this->registerAccount(
            'deleteme-' . uniqid() . '@example.com',
            'P$ssword' . uniqid()
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
    
        $this->getLastStatusCode()->shouldBe(400);
    }
    
    /**
     * This one takes a long time - re-instate it to test pagination
     * from the API - perhaps by changing the pagination in the API to
     * something smaller and changing the number of applications
     * tested here.
     *
     * To reinstate, remove the "skipped" from the function name
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
    
    function skipped_it_will_eventually_find_a_pdf_ready_for_download()
    {
        $lpaId = getANewCompletedLpa($this);
    
        $pdfType = 'lpa120';
    
        $this->getPdfDetails($lpaId, $pdfType)['status']->shouldBe('in-queue');
    
        // now we simulate the polling mechanism
        $startTime = time();
        while ($this->getPdfDetails($lpaId, $pdfType)['status'] == 'in-queue') {
            $this->getPdfDetails($lpaId, $pdfType)['status']->shouldBeEitherReadyOrQueued();
        } while (time() - $startTime < 60 && $this->getPdfDetails($lpaId, $pdfType)['status'] == 'in-queue');
    
        $this->getPdfDetails($lpaId, $pdfType)['status']->shouldBe('ready');
        $this->getPdfDetails($lpaId, $pdfType)['type']->shouldBe($pdfType);
    
        $stream = $this->getPdf($lpaId, $pdfType)->shouldBeAPdfStream();
    }
    
    function skipped_it_can_get_auth_server_stats()
    {
        $stats = $this->getAuthStats()->shouldBeTheAuthStatsArray();
    }
    
    function it_will_get_an_email_update_token()
    {
        $this->beConstructedWith(getTestUserToken());
        $newEmail = 'deleteme-' . uniqid() . '@example.com';
    
        $this->requestEmailUpdate($newEmail)->shouldBeAToken();
    }
    
    function it_will_update_an_account_email_on_the_auth_server()
    {
        $this->beConstructedWith(getTestUserToken());
    
        $newEmail = 'deleteme-' . uniqid() . '@example.com';
         
        $token = $this->requestEmailUpdate($newEmail);
    
        $this->updateAuthEmail($token)->shouldBe(true);
         
        $this->authenticate($newEmail, TEST_AUTH_PASSWORD)->isAuthenticated()->shouldBe(true);
    
        destroyAndRecreateTestUser();
    }
    
    function it_will_update_a_password_for_an_authenticated_user()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        
        $password = 'Test$N3wTestPassword';
        
        $this->updateAuthPassword(TEST_AUTH_PASSWORD, $password)->shouldBeAToken();
        
        // Check we can login with the new password.
        $this->authenticate(TEST_AUTH_EMAIL, $password)->isAuthenticated()->shouldBe(true);
        
        // Delete the new account (as it now has the 'wrong' password)
        $this->deleteUserAndAllTheirLpas( $this->getToken() );
        
        destroyAndRecreateTestUser();
        
    }
    
    function it_will_return_the_username_when_given_a_valid_token()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $password = 'Test$' . uniqid();
         
        $activationToken = $this->registerAccount($email, $password);
         
        $this->activateAccount($activationToken)->shouldBe(true);
         
        $authToken = $this->authenticate(
            $email,
            $password
        )->getToken();
         
        $this->getEmailFromToken($authToken)->shouldBe($email);
    }
     
    function it_can_delete_an_account()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $password = 'Test$' . uniqid();
         
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
         
        $this->getLastStatusCode()->shouldBe(401);
    }
    
    function it_can_authenticate_against_the_auth_server()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
        $password = 'Test$' . uniqid();
         
        $activationToken = $this->registerAccount($email, $password);
    
        $this->activateAccount($activationToken)->shouldBe(true);
    
        $this->authenticate(
            $email,
            $password
        )->isAuthenticated()->shouldBe(true);
    }
    
    function it_can_create_a_new_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $this->createApplication()->shouldBeAPositiveInteger();
    }
    
    function it_can_set_and_get_the_lpa_seed()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        $lpaId = $this->createApplication();
        $seed1 = $this->createApplication();
        $seed2 = $this->createApplication();
    
        $this->setSeed($lpaId, $seed1)->shouldBe(true);
        $this->getSeedDetails($lpaId)['seed']->shouldBe($seed1);
        $this->setSeed($lpaId, $seed2)->shouldBe(true);
        $this->getSeedDetails($lpaId)['seed']->shouldBe($seed2);
    }
    
    function it_can_set_and_get_and_delete_metadata()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        $lpaId = $this->createApplication();
    
        $this->setMetaData($lpaId, ['test-meta' => 'data'])->shouldBe(true);
        $this->getMetaData($lpaId)['test-meta']->shouldBe('data');
        $this->deleteMetaData($lpaId)->shouldBe(true);
        $this->getMetaData($lpaId)->shouldBe(null);
    }
    
    function it_can_get_a_list_of_pdfs()
    {
        $numApplications = 3;
         
        destroyAndRecreateTestUser();
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        $lpaIds = [];
        for ($i=0; $i<$numApplications; $i++) {
            $lpaId[] = $this->createApplication();
        }
         
        $this->getPdfList($lpaId[0])->shouldHaveCount(3);
        $this->getPdfList($lpaId[1])[0]->shouldHaveCount(4);
        $this->getPdfList($lpaId[2])[0]['type']->shouldBe('lpa120');
    }
    
    function it_can_be_constructed_from_an_auth_token_then_create_an_application()
    {
        $this->beConstructedWith(getTestUserToken());
        $this->createApplication()->shouldBeAPositiveInteger();
    }
    
    function it_will_return_null_if_repeatCaseNumber_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getRepeatCaseNumber($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_the_lpa_repeatCaseNumber()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setRepeatCaseNumber($lpaId, 1)->shouldBe(true);
        $this->getRepeatCaseNumber($lpaId)->shouldBe(1);
        $this->setRepeatCaseNumber($lpaId, 23123)->shouldBe(true);
        $this->getRepeatCaseNumber($lpaId)->shouldBe(23123);
    }
    
    function it_will_fail_if_attempting_to_set_an_invalid_repeatCaseNumber()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setRepeatCaseNumber($lpaId, 'this-is-not-valid')->shouldBe(false);
    }
    
    function it_can_delete_the_repeatCaseNumber()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setRepeatCaseNumber($lpaId, 3243254);
        $this->deleteRepeatCaseNumber($lpaId)->shouldBe(true);
        $this->getRepeatCaseNumber($lpaId)->shouldBe(null);
    }
     
    function it_will_delete_all_lpas_of_an_account()
    {
        $numApplications = 2;
         
        destroyAndRecreateTestUser();
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
         
        for ($i=0; $i<$numApplications; $i++) {
            $this->createApplication();
        }
         
        $this->getApplicationList()->shouldBeAnArrayOfLpaObjects($numApplications);
         
        $this->deleteAllLpas()->shouldBe(true);
         
        $this->getApplicationList()->shouldBe([]);
         
        destroyAndRecreateTestUser();
    }
     
    function it_will_delete_an_account_and_all_lpas()
    {
        $numApplications = 2;
         
        destroyAndRecreateTestUser();
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
         
        for ($i=0; $i<$numApplications; $i++) {
            $this->createApplication();
        }
         
        $this->getApplicationList()->shouldBeAnArrayOfLpaObjects($numApplications);
         
        $this->deleteUserAndAllTheirLpas()->shouldBe(true);
         
        $this->getApplicationList()->shouldBe(false);
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD)->isAuthenticated()->shouldBe(false);
         
        destroyAndRecreateTestUser();
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
     
    function it_will_return_an_empty_array_when_no_applications_exist()
    {
        $numApplications = 2;
         
        destroyAndRecreateTestUser();
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        $this->getApplicationList()->shouldBe([]);
    }
     
    //--------------------------------------------------------------
    // Replacement Attorneys
     
    function it_can_delete_a_replacement_attorney()
    {
        $replacementAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney3 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2->name->first = 'Jane';
        $replacementAttorney3->name->first = 'John';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addReplacementAttorney($lpaId, $replacementAttorney1)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney2)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney3)->shouldBe(true);
         
        $this->deleteReplacementAttorney($lpaId, 3)->shouldBe(true);
        $this->deleteReplacementAttorney($lpaId, 1)->shouldBe(true);
        $this->getReplacementAttorneys($lpaId)->shouldBeAnArrayOfAttorneys(1);
        $this->getReplacementAttorneys($lpaId)[0]->name->first->shouldBe('Jane');
    }
     
    function it_can_return_a_list_of_replacement_attorneys()
    {
        $replacementAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2->name->first = 'Jane';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addReplacementAttorney($lpaId, $replacementAttorney1)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney2)->shouldBe(true);
         
        $this->getReplacementAttorneys($lpaId)->shouldBeAnArrayOfAttorneys(2);
        $this->getReplacementAttorneys($lpaId)[0]->name->first->shouldBe('John');
        $this->getReplacementAttorneys($lpaId)[1]->name->first->shouldBe('Jane');
    }
     
    function it_will_return_an_empty_array_if_no_replacement_attorneys_have_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getReplacementAttorneys($lpaId)->shouldBe([]);
    }
     
    function it_can_add_and_update_a_replacement_attorney()
    {
        $replacementAttorney = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addReplacementAttorney($lpaId, $replacementAttorney)->shouldBe(true);
        $this->getReplacementAttorney($lpaId, 1)->name->first->shouldBe('John');
        $replacementAttorney->name->first = 'Henry';
        $this->setReplacementAttorney($lpaId, $replacementAttorney, 1)->shouldBe(true);
        $this->getReplacementAttorney($lpaId, 1)->name->first->shouldBe('Henry');
    }
     
    function it_can_add_and_update_multiple_replacement_attorneys()
    {
        $replacementAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addReplacementAttorney($lpaId, $replacementAttorney1)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney2)->shouldBe(true);
        $this->getReplacementAttorney($lpaId, 1)->name->first->shouldBe('John');
        $this->getReplacementAttorney($lpaId, 2)->name->first->shouldBe('Sally');
        $replacementAttorney1->name->first = 'Henry';
        $replacementAttorney2->name->first = 'Beth';
        $this->setReplacementAttorney($lpaId, $replacementAttorney1, 1)->shouldBe(true);
        $this->setReplacementAttorney($lpaId, $replacementAttorney2, 2)->shouldBe(true);
        $this->getReplacementAttorney($lpaId, 1)->name->first->shouldBe('Henry');
        $this->getReplacementAttorney($lpaId, 2)->name->first->shouldBe('Beth');
    }
    
    function it_will_return_a_password_reset_token(){
        $this->requestPasswordReset( TEST_AUTH_EMAIL )->shouldBeString();
        destroyAndRecreateTestUser();
    }
    
    function it_will_set_a_new_auth_password_using_a_password_reset_token(){
    
        $token = $this->requestPasswordReset( TEST_AUTH_EMAIL );
    
        $password = 'Test$N3wTestPassword';
    
        $this->updateAuthPasswordWithToken( $token, $password )->shouldBe(true);
    
        // Check we can login with the new password.
        $this->authenticate(TEST_AUTH_EMAIL, $password)->isAuthenticated()->shouldBe(true);
    
        // Delete the new account (as it now has the 'wrong' password)
        $this->deleteUserAndAllTheirLpas( $this->getToken() );
    
        destroyAndRecreateTestUser();
    
    }
    
    //--------------------------------------------------------------
    // Who Is Registering
    
    function it_will_return_null_if_who_is_registering_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getWhoIsRegistering($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_the_lpa_who_is_registering_when_it_is_a_donor()
    {
        $who = 'donor';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
    
        $this->setWhoIsRegistering($lpaId, $who)->shouldBe(true);
        $this->getWhoIsRegistering($lpaId)->shouldBe($who);
    
    }
    
    
    function it_can_set_and_get_the_lpa_who_is_registering_when_they_are_attorneys()
    {
    
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney3 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation');
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney3)->shouldBe(true);
    
        //---
    
        $who = [ 1, 3 ];
    
        $this->setWhoIsRegistering($lpaId, $who)->shouldBe(true);
        $result = $this->getWhoIsRegistering($lpaId);
        $result->shouldHaveCount(2);
    
        $result[0]->shouldBeAnInstanceOf( '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human' );
        $result[0]->id->shouldBe( array_shift($who) );
    
        $result[1]->shouldBeAnInstanceOf( '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation' );
        $result[1]->id->shouldBe( array_shift($who) );
    
    }
    
    function it_can_delete_who_is_registering()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
    
    
        $this->setWhoIsRegistering($lpaId, 'donor')->shouldBe(true);
        $this->deleteWhoIsRegistering($lpaId)->shouldBe(true);
        $this->getWhoIsRegistering($lpaId)->shouldBe(null);
    
    }
    
    //--------------------------------------------------------------
    
    function it_can_delete_a_primary_attorney()
    {
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation');
        $primaryAttorney3 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2->name = 'Solicitors Limited';
        $primaryAttorney3->name->first = 'John';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney3)->shouldBe(true);
         
        $this->deletePrimaryAttorney($lpaId, 3)->shouldBe(true);
        $this->deletePrimaryAttorney($lpaId, 1)->shouldBe(true);
        $this->getPrimaryAttorneys($lpaId)->shouldBeAnArrayOfAttorneys(1);
        $this->getPrimaryAttorneys($lpaId)[0]->name->shouldBe('Solicitors Limited');
    }
     
    function it_can_return_a_list_of_primary_attorneys()
    {
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation');
        $primaryAttorney2->name = 'Solicitors Limited';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
         
        $this->getPrimaryAttorneys($lpaId)->shouldBeAnArrayOfAttorneys(2);
        $this->getPrimaryAttorneys($lpaId)[0]->name->first->shouldBe('John');
        $this->getPrimaryAttorneys($lpaId)[1]->name->shouldBe('Solicitors Limited');
    }
     
    function it_will_return_an_empty_array_if_no_primary_attorneys_have_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getPrimaryAttorneys($lpaId)->shouldBe([]);
    }
     
    function it_can_add_and_update_a_primary_attorney()
    {
        $primaryAttorney = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addPrimaryAttorney($lpaId, $primaryAttorney)->shouldBe(true);
        $this->getPrimaryAttorney($lpaId, 1)->name->first->shouldBe('John');
        $primaryAttorney->name->first = 'Henry';
        $this->setPrimaryAttorney($lpaId, $primaryAttorney, 1)->shouldBe(true);
        $this->getPrimaryAttorney($lpaId, 1)->name->first->shouldBe('Henry');
    }
     
    function it_can_add_and_update_multiple_primary_attorneys()
    {
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
        $this->getPrimaryAttorney($lpaId, 1)->name->first->shouldBe('John');
        $this->getPrimaryAttorney($lpaId, 2)->name->first->shouldBe('Sally');
        $primaryAttorney1->name->first = 'Henry';
        $primaryAttorney2->name->first = 'Beth';
        $this->setPrimaryAttorney($lpaId, $primaryAttorney1, 1)->shouldBe(true);
        $this->setPrimaryAttorney($lpaId, $primaryAttorney2, 2)->shouldBe(true);
        $this->getPrimaryAttorney($lpaId, 1)->name->first->shouldBe('Henry');
        $this->getPrimaryAttorney($lpaId, 2)->name->first->shouldBe('Beth');
    }
     
    function it_will_return_null_if_seed_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getSeedDetails($lpaId)->shouldBe(null);
    }
     
    function it_will_correctly_report_if_the_who_are_details_are_set()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->isWhoAreYouSet($lpaId, $whoAreYou)->shouldBe(false);
    }
     
    function it_will_correctly_report_if_the_who_are_details_are_not_set()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(true);
        $this->isWhoAreYouSet($lpaId, $whoAreYou)->shouldBe(true);
    }
     
    function it_can_set_the_who_are_you_details()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(true);
    }
     
    function it_will_raise_an_error_if_setting_who_are_you_details_after_they_have_already_been_set()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(true);
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(false);
    }
     
    function it_will_return_the_lock_status_when_locked()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->lockLpa($lpaId)->shouldBe(true);
        $this->isLpaLocked($lpaId)->shouldBe(true);
    }
     
    function it_will_return_the_lock_status_when_not_locked()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->isLpaLocked($lpaId)->shouldBe(false);
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
     
    function it_will_fail_if_attempting_to_set_an_invalid_seed()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setSeed($lpaId, 'this-is-not-valid')->shouldBe(false);
    }
     
    function it_can_delete_the_seed()
    {
        $seed1 = uniqid();
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setSeed($lpaId, $seed1);
        $this->deleteSeed($lpaId)->shouldBe(true);
        $this->getSeedDetails($lpaId)->shouldBe(null);
    }
     
    function it_can_delete_a_notified_person()
    {
        $notifiedPerson1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson3 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2->name->first = 'Sally';
        $notifiedPerson3->name->first = 'John';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addNotifiedPerson($lpaId, $notifiedPerson1)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson2)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson3)->shouldBe(true);
         
        $this->deleteNotifiedPerson($lpaId, 3)->shouldBe(true);
        $this->deleteNotifiedPerson($lpaId, 1)->shouldBe(true);
        $this->getNotifiedPersons($lpaId)->shouldBeAnArrayOfNotifiedPeople(1);
        $this->getNotifiedPersons($lpaId)[0]->name->first->shouldBe('Sally');
    }
     
    function it_can_return_a_list_of_notified_people()
    {
        $notifiedPerson1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addNotifiedPerson($lpaId, $notifiedPerson1)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson2)->shouldBe(true);
         
        $this->getNotifiedPersons($lpaId)->shouldBeAnArrayOfNotifiedPeople(2);
        $this->getNotifiedPersons($lpaId)[0]->name->first->shouldBe('Bob');
        $this->getNotifiedPersons($lpaId)[1]->name->first->shouldBe('Sally');
    }
     
    function it_will_return_an_empty_array_if_no_notified_people_have_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getNotifiedPersons($lpaId)->shouldBe([]);
    }
     
    function it_can_add_and_update_a_notified_person()
    {
        $notifiedPerson = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addNotifiedPerson($lpaId, $notifiedPerson)->shouldBe(true);
        $this->getNotifiedPerson($lpaId, 1)->name->first->shouldBe('Bob');
        $notifiedPerson->name->first = 'Henry';
        $this->setNotifiedPerson($lpaId, $notifiedPerson, 1)->shouldBe(true);
        $this->getNotifiedPerson($lpaId, 1)->name->first->shouldBe('Henry');
    }
     
    function it_can_add_and_update_multiple_notified_people()
    {
        $notifiedPerson1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addNotifiedPerson($lpaId, $notifiedPerson1)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson2)->shouldBe(true);
        $this->getNotifiedPerson($lpaId, 1)->name->first->shouldBe('Bob');
        $this->getNotifiedPerson($lpaId, 2)->name->first->shouldBe('Sally');
        $notifiedPerson1->name->first = 'Henry';
        $notifiedPerson2->name->first = 'Beth';
        $this->setNotifiedPerson($lpaId, $notifiedPerson1, 1)->shouldBe(true);
        $this->setNotifiedPerson($lpaId, $notifiedPerson2, 2)->shouldBe(true);
        $this->getNotifiedPerson($lpaId, 1)->name->first->shouldBe('Henry');
        $this->getNotifiedPerson($lpaId, 2)->name->first->shouldBe('Beth');
    }
     
    function it_will_return_null_if_the_certificate_provider_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getDonor($lpaId)->shouldBe(null);
    }
     
    function it_can_set_and_get_the_certificate_provider()
    {
        $certificateProvider = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider');
        $certificateProvider2 = clone($certificateProvider);
        $certificateProvider2->name->first = 'Jane';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setCertificateProvider($lpaId, $certificateProvider)->shouldBe(true);
        $this->getCertificateProvider($lpaId)->toJson()->shouldBe($certificateProvider->toJson());
        $this->getCertificateProvider($lpaId)->address->address1->shouldBe('Line 1');
        $this->setCertificateProvider($lpaId, $certificateProvider2)->shouldBe(true);
        $this->getCertificateProvider($lpaId)->toJson()->shouldBe($certificateProvider2->toJson());
        $this->getCertificateProvider($lpaId)->address->address1->shouldBe('Line 1');
    }
     
    function it_can_delete_the_certificate_provider()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setCertificateProvider($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider'));
        $this->deleteCertificateProvider($lpaId)->shouldBe(true);
        $this->getCertificateProvider($lpaId)->shouldBe(null);
    }
     
    function it_will_return_null_if_replacement_attorney_decisions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getReplacementAttorneyDecisions($lpaId)->shouldBe(null);
    }
     
    function it_can_set_and_get_and_update_the_replacement_attorney_decisions()
    {
        $decisions = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions');
        $decisions2 = clone($decisions);
        $decisions3 = clone($decisions);
        $decisions2->howDetails = 'Second Object';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setReplacementAttorneyDecisions($lpaId, $decisions)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions->toJson());
        $this->setReplacementAttorneyDecisions($lpaId, $decisions2)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions2->toJson());
    
        $decisions3->when = $decisions3::LPA_DECISION_WHEN_FIRST;
        $this->setReplacementAttorneyDecisions($lpaId, $decisions3)->shouldBe(true);
        $this->updateReplacementAttorneyDecisions($lpaId, ['when'=>$decisions3::LPA_DECISION_WHEN_LAST])->shouldBe(true);
    
        $decisions3->when = $decisions3::LPA_DECISION_WHEN_LAST;
        $this->getReplacementAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions3->toJson());
    }
     
    function it_can_delete_the_replacement_attorney_decisions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setReplacementAttorneyDecisions($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions'));
        $this->deleteReplacementAttorneyDecisions($lpaId)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->shouldBe(null);
    }
     
    function it_will_return_null_if_primary_attorney_decisions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getPrimaryAttorneyDecisions($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_and_update_the_primary_attorney_decisions()
    {
        $decisions = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions');
        $decisions2 = clone($decisions);
        $decisions3 = clone($decisions);
        $decisions2->howDetails = 'Second Object';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
    
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions)->shouldBe(true);
        $this->getPrimaryAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions->toJson());
    
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions2)->shouldBe(true);
        $this->getPrimaryAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions2->toJson());
    
        $decisions3->canSustainLife = true;
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions3)->shouldBe(true);

    }
     
    function it_can_delete_the_primary_attorney_decisions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setPrimaryAttorneyDecisions($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions'));
        $this->deletePrimaryAttorneyDecisions($lpaId)->shouldBe(true);
        $this->getPrimaryAttorneyDecisions($lpaId)->shouldBe(null);
    }
     
    function it_will_return_null_if_donor_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getDonor($lpaId)->shouldBe(null);
    }
     
    function it_can_set_and_get_the_donor()
    {
        $donor = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Donor');
        $donor2 = clone($donor);
        $donor2->canSign = !$donor->canSign;
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setDonor($lpaId, $donor)->shouldBe(true);
        $this->getDonor($lpaId)->toJson()->shouldBe($donor->toJson());
        $this->getDonor($lpaId)->otherNames->shouldBe('Fred');
        $this->setDonor($lpaId, $donor2)->shouldBe(true);
        $this->getDonor($lpaId)->toJson()->shouldBe($donor2->toJson());
        $this->getDonor($lpaId)->otherNames->shouldBe('Fred');
    }
     
    function it_can_delete_the_donor()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setDonor($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Donor'));
        $this->deleteDonor($lpaId)->shouldBe(true);
        $this->getDonor($lpaId)->shouldBe(null);
    }
     
    function it_will_return_null_if_correspondent_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getCorrespondent($lpaId)->shouldBe(null);
    }
    
    function it_can_set_and_get_the_correspondent()
    {
        $correspondent = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Correspondence');
        $correspondent2 = clone($correspondent);
        $correspondent2->contactByPost = !$correspondent->contactByPost;
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setCorrespondent($lpaId, $correspondent)->shouldBe(true);
        $this->getCorrespondent($lpaId)->toJson()->shouldBe($correspondent->toJson());
        $this->getCorrespondent($lpaId)->who->shouldBe('other');
        $this->setCorrespondent($lpaId, $correspondent2)->shouldBe(true);
        $this->getCorrespondent($lpaId)->toJson()->shouldBe($correspondent2->toJson());
    }
     
    function it_can_delete_the_correspondent()
    {
        $correspondent = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Correspondence');
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setCorrespondent($lpaId, $correspondent);
        $this->deleteCorrespondent($lpaId)->shouldBe(true);
        $this->getCorrespondent($lpaId)->shouldBe(null);
    }
     
    function it_will_return_null_if_payment_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getPayment($lpaId)->shouldBe(null);
    }
     
    function it_can_set_and_get_the_payment()
    {
        $payment = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Payment\Payment');
        $payment2 = clone($payment);
        $payment2->amount = 101;
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setPayment($lpaId, $payment)->shouldBe(true);
        $this->getPayment($lpaId)->toJson()->shouldBe($payment->toJson());
        $this->setPayment($lpaId, $payment2)->shouldBe(true);
        $this->getPayment($lpaId)->toJson()->shouldBe($payment2->toJson());
    }
     
    function it_can_delete_the_payment()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setPayment($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Payment\Payment'));
        $this->deletePayment($lpaId)->shouldBe(true);
        $this->getPayment($lpaId)->shouldBe(null);
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
     
    public function getMatchers()
    {
        return [
            'beAToken' => function($subject) {
                return is_string($subject) && strlen($subject) > 0;
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
            'beAnArrayOfNotifiedPeople' => function($subject, $count) {
                return 
                    is_array($subject) && 
                    count($subject) == $count && 
                    $subject[0] instanceof \Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
            },
            'beAnArrayOfAttorneys' => function($subject, $count) {
                return
                    is_array($subject) &&
                    count($subject) == $count &&
                    $subject[0] instanceof \Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
            },
            'beEitherReadyOrQueued' => function($subject) {
                return $subject == 'in-queue' || $subject == 'ready';
            },
            'beAPdfStream' => function($subject) {
            
                if (strlen($subject) < 4) {
                    return false;
                }
                
                if (substr($subject, 0, 4) != '%PDF') {
                    return false;
                }
            
                return true;
            },
            'beTheAuthStatsArray' => function($subject) {
                
                if (is_array($subject) && count($subject) == 3) {
                    return (
                        isset($subject['total']) && 
                        isset($subject['activated']) && 
                        isset($subject['activated-this-month'])
                    );
                }
                
                return false;
            },
        ];
    }
    
}
