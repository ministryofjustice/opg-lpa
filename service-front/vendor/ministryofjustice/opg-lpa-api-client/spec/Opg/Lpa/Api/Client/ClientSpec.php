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
        $result = $this->registerAccount(
            'deleteme-' . uniqid() . 'example.com',
            'P$ssword' . uniqid()
        );

        $result->shouldImplement('Opg\Lpa\Api\Client\Exception\ResponseException');
        $result->getDetail()->shouldBe('invalid-username');
    }
    
    function it_will_report_an_email_already_exists_error()
    {
        $email = 'deleteme-' . uniqid() . '@example.com';
         
        $this->registerAccount(
            $email,
            'P$assword' . uniqid()
        )->shouldBeAToken();

        $result = $this->registerAccount(
            $email,
            'P$assword' . uniqid()
        );

        $result->shouldImplement('Opg\Lpa\Api\Client\Exception\ResponseException');
        $result->getDetail()->shouldBe('username-already-exists');
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
        $result = $this->activateAccount('IAmABadToken');

        $result->shouldImplement('Opg\Lpa\Api\Client\Exception\ResponseException');
        $result->getDetail()->shouldBe('account-not-found');

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
            $this->createApplication()->get('id');
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
        $result = $this->createApplication();

        $result->shouldBeAnInstanceOf('Opg\Lpa\Api\Client\Response\Lpa');
        $result->get('id')->shouldBeAPositiveInteger();

    }
    
    function it_can_set_and_get_the_lpa_seed()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        $lpaId = $this->createApplication()->get('id');
        $seed1 = $this->createApplication()->get('id');
        $seed2 = $this->createApplication()->get('id');
    
        $this->setSeed($lpaId, $seed1)->shouldBe(true);
        $this->getSeedDetails($lpaId)['seed']->shouldBe($seed1);
        $this->setSeed($lpaId, $seed2)->shouldBe(true);
        $this->getSeedDetails($lpaId)['seed']->shouldBe($seed2);
    }
    
    function it_can_set_and_get_and_delete_metadata()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
    
        $lpaId = $this->createApplication()->get('id');
    
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
            $lpaId[] = $this->createApplication()->get('id');
        }
         
        $this->getPdfList($lpaId[0])->shouldHaveCount(3);
        $this->getPdfList($lpaId[1])[0]->shouldHaveCount(4);
        $this->getPdfList($lpaId[2])[0]['type']->shouldBe('lpa120');
    }
    
    function it_can_be_constructed_from_an_auth_token_then_create_an_application()
    {
        $this->beConstructedWith(getTestUserToken());

        $result = $this->createApplication();
        $result->shouldBeAnInstanceOf('Opg\Lpa\Api\Client\Response\Lpa');
        $result->get('id')->shouldBeAPositiveInteger();
    }
    
    function it_will_return_null_if_repeatCaseNumber_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->repeatCaseNumber->shouldBe(null);
    }
    
    function it_can_set_and_get_the_lpa_repeatCaseNumber()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setRepeatCaseNumber($lpaId, 1)->shouldBe(true);
        $this->getApplication($lpaId)->repeatCaseNumber->shouldBe(1);
        $this->setRepeatCaseNumber($lpaId, 23123)->shouldBe(true);
        $this->getApplication($lpaId)->repeatCaseNumber->shouldBe(23123);
    }
    
    function it_will_fail_if_attempting_to_set_an_invalid_repeatCaseNumber()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setRepeatCaseNumber($lpaId, 'this-is-not-valid')->shouldBe(false);
    }
    
    function it_can_delete_the_repeatCaseNumber()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setRepeatCaseNumber($lpaId, 3243254);
        $this->deleteRepeatCaseNumber($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->repeatCaseNumber->shouldBe(null);
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
            $this->createApplication()->get('id');
        }
         
        $this->getApplicationList()->shouldBeAnArrayOfLpaObjects($numApplications);
         
        $this->deleteUserAndAllTheirLpas()->shouldBe(true);

        // Won't be able to authenticate, thus we get an exception.
        $this->shouldThrow('\Opg\Lpa\Api\Client\Exception\ResponseException')->duringGetApplicationList();

        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD)->isAuthenticated()->shouldBe(false);
         
        destroyAndRecreateTestUser();
    }
     
    function it_can_get_a_list_of_applications()
    {
        $numApplications = 2;
         
        destroyAndRecreateTestUser();
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
         
        for ($i=0; $i<$numApplications; $i++) {
            $this->createApplication()->get('id');
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
        $lpaId = $this->createApplication()->get('id');
        $this->addReplacementAttorney($lpaId, $replacementAttorney1)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney2)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney3)->shouldBe(true);
         
        $this->deleteReplacementAttorney($lpaId, 3)->shouldBe(true);
        $this->deleteReplacementAttorney($lpaId, 1)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldBeAnArrayOfAttorneys(1);
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'Jane' );
    }
     
    function it_can_return_a_list_of_replacement_attorneys()
    {
        $replacementAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2->name->first = 'Jane';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addReplacementAttorney($lpaId, $replacementAttorney1)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney2)->shouldBe(true);

        $this->getApplication($lpaId)->document->replacementAttorneys->shouldBeAnArrayOfAttorneys(2);
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'John' );
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 1, 'name-first', 'Jane' );
    }
     
    function it_will_return_an_empty_array_if_no_replacement_attorneys_have_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldBe([]);
    }
     
    function it_can_add_and_update_a_replacement_attorney()
    {
        $replacementAttorney = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addReplacementAttorney($lpaId, $replacementAttorney)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'John' );

        $replacementAttorney->name->first = 'Henry';
        $this->setReplacementAttorney($lpaId, $replacementAttorney, 1)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'Henry' );
    }
     
    function it_can_add_and_update_multiple_replacement_attorneys()
    {
        $replacementAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $replacementAttorney2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addReplacementAttorney($lpaId, $replacementAttorney1)->shouldBe(true);
        $this->addReplacementAttorney($lpaId, $replacementAttorney2)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'John' );
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 1, 'name-first', 'Sally' );

        $replacementAttorney1->name->first = 'Henry';
        $replacementAttorney2->name->first = 'Beth';
        $this->setReplacementAttorney($lpaId, $replacementAttorney1, 1)->shouldBe(true);
        $this->setReplacementAttorney($lpaId, $replacementAttorney2, 2)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'Henry' );
        $this->getApplication($lpaId)->document->replacementAttorneys->shouldHaveValueInArrayItem( 1, 'name-first', 'Beth' );
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
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->whoIsRegistering->shouldBe(null);
    }
    
    function it_can_set_and_get_the_lpa_who_is_registering_when_it_is_a_donor()
    {
        $who = 'donor';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
    
        $this->setWhoIsRegistering($lpaId, $who)->shouldBe(true);
        $this->getApplication($lpaId)->document->whoIsRegistering->shouldBe($who);
    }


    function it_can_set_and_get_the_lpa_who_is_registering_when_they_are_attorneys()
    {

        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney3 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation');

        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney3)->shouldBe(true);

        //---

        $who = [ 1, 3 ];

        $this->setWhoIsRegistering($lpaId, $who)->shouldBe(true);

        $result = $this->getApplication($lpaId)->document->whoIsRegistering;
        $result->shouldHaveCount(2);

    }

    function it_can_delete_who_is_registering()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
    
    
        $this->setWhoIsRegistering($lpaId, 'donor')->shouldBe(true);
        $this->deleteWhoIsRegistering($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->whoIsRegistering->shouldBe(null);
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
        $lpaId = $this->createApplication()->get('id');
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney3)->shouldBe(true);
         
        $this->deletePrimaryAttorney($lpaId, 3)->shouldBe(true);
        $this->deletePrimaryAttorney($lpaId, 1)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldBeAnArrayOfAttorneys(1);
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 0, 'name', 'Solicitors Limited' );
    }
     
    function it_can_return_a_list_of_primary_attorneys()
    {
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation');
        $primaryAttorney2->name = 'Solicitors Limited';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);

        $this->getApplication($lpaId)->document->primaryAttorneys->shouldBeAnArrayOfAttorneys(2);
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'John' );
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 1, 'name', 'Solicitors Limited' );
    }
     
    function it_will_return_an_empty_array_if_no_primary_attorneys_have_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldBe([]);
    }

     
    function it_can_add_and_update_a_primary_attorney()
    {
        $primaryAttorney = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addPrimaryAttorney($lpaId, $primaryAttorney)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'John' );
        
        $primaryAttorney->name->first = 'Henry';
        $this->setPrimaryAttorney($lpaId, $primaryAttorney, 1)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'Henry' );
    }
     
    function it_can_add_and_update_multiple_primary_attorneys()
    {
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'John' );
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 1, 'name-first', 'Sally' );

        $primaryAttorney1->name->first = 'Henry';
        $primaryAttorney2->name->first = 'Beth';
        $this->setPrimaryAttorney($lpaId, $primaryAttorney1, 1)->shouldBe(true);
        $this->setPrimaryAttorney($lpaId, $primaryAttorney2, 2)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 0, 'name-first', 'Henry' );
        $this->getApplication($lpaId)->document->primaryAttorneys->shouldHaveValueInArrayItem( 1, 'name-first', 'Beth' );
    }
     
    function it_will_return_null_if_seed_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getSeedDetails($lpaId)->shouldBe(null);
    }

     
    function it_will_correctly_report_if_the_who_are_details_are_set()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->whoAreYouAnswered->shouldBe(false);
    }
     
    function it_will_correctly_report_if_the_who_are_details_are_not_set()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(true);
        $this->getApplication($lpaId)->whoAreYouAnswered->shouldBe(true);
    }
     
    function it_can_set_the_who_are_you_details()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(true);
    }
     
    function it_will_raise_an_error_if_setting_who_are_you_details_after_they_have_already_been_set()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(true);
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(false);
    }


    function it_will_return_the_lock_status_when_locked()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->lockLpa($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->locked->shouldBe(true);
    }
     
    function it_will_return_the_lock_status_when_not_locked()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->locked->shouldBe(false);
    }
     
    function it_can_lock_an_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->lockLpa($lpaId)->shouldBe(true);
    }
     
    function it_will_report_an_already_locked_lpa()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->lockLpa($lpaId)->shouldBe(true);
        $this->lockLpa($lpaId)->shouldBe(false);
        $this->getLastStatusCode()->shouldBe(403);
    }
     
    function it_will_fail_if_attempting_to_set_an_invalid_seed()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setSeed($lpaId, 'this-is-not-valid')->shouldBe(false);
    }
     
    function it_can_delete_the_seed()
    {
        $seed1 = uniqid();
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
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
        $lpaId = $this->createApplication()->get('id');
        $this->addNotifiedPerson($lpaId, $notifiedPerson1)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson2)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson3)->shouldBe(true);
         
        $this->deleteNotifiedPerson($lpaId, 3)->shouldBe(true);
        $this->deleteNotifiedPerson($lpaId, 1)->shouldBe(true);

        $this->getApplication($lpaId)->document->peopleToNotify->shouldBeAnArrayOfNotifiedPeople(1);
        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 0, 'name-first', 'Sally' );
    }
     
    function it_can_return_a_list_of_notified_people()
    {
        $notifiedPerson1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addNotifiedPerson($lpaId, $notifiedPerson1)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson2)->shouldBe(true);

        $this->getApplication($lpaId)->document->peopleToNotify->shouldBeAnArrayOfNotifiedPeople(2);
        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 0, 'name-first', 'Bob' );
        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 1, 'name-first', 'Sally' );
    }
     
    function it_will_return_an_empty_array_if_no_notified_people_have_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->peopleToNotify->shouldBe([]);
    }

     
    function it_can_add_and_update_a_notified_person()
    {
        $notifiedPerson = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addNotifiedPerson($lpaId, $notifiedPerson)->shouldBe(true);
        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 0, 'name-first', 'Bob' );

        $notifiedPerson->name->first = 'Henry';
        $this->setNotifiedPerson($lpaId, $notifiedPerson, 1)->shouldBe(true);
        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 0, 'name-first', 'Henry' );
    }

     
    function it_can_add_and_update_multiple_notified_people()
    {
        $notifiedPerson1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson');
        $notifiedPerson2->name->first = 'Sally';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->addNotifiedPerson($lpaId, $notifiedPerson1)->shouldBe(true);
        $this->addNotifiedPerson($lpaId, $notifiedPerson2)->shouldBe(true);

        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 0, 'name-first', 'Bob' );
        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 1, 'name-first', 'Sally' );

        $notifiedPerson1->name->first = 'Henry';
        $notifiedPerson2->name->first = 'Beth';
        $this->setNotifiedPerson($lpaId, $notifiedPerson1, 1)->shouldBe(true);
        $this->setNotifiedPerson($lpaId, $notifiedPerson2, 2)->shouldBe(true);

        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 0, 'name-first', 'Henry' );
        $this->getApplication($lpaId)->document->peopleToNotify->shouldHaveValueInArrayItem( 1, 'name-first', 'Beth' );
    }
     
    function it_will_return_null_if_the_certificate_provider_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->donor->shouldBe(null);
    }
     
    function it_can_set_and_get_the_certificate_provider()
    {
        $certificateProvider = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider');
        $certificateProvider2 = clone($certificateProvider);
        $certificateProvider2->name->first = 'Jane';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setCertificateProvider($lpaId, $certificateProvider)->shouldBe(true);
        $this->getApplication($lpaId)->document->certificateProvider->toJson()->shouldBe($certificateProvider->toJson());
        $this->getApplication($lpaId)->document->certificateProvider->address->address1->shouldBe('Line 1');

        $this->setCertificateProvider($lpaId, $certificateProvider2)->shouldBe(true);
        $this->getApplication($lpaId)->document->certificateProvider->toJson()->shouldBe($certificateProvider2->toJson());
        $this->getApplication($lpaId)->document->certificateProvider->address->address1->shouldBe('Line 1');
    }
     
    function it_can_delete_the_certificate_provider()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setCertificateProvider($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\CertificateProvider'));
        $this->deleteCertificateProvider($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->certificateProvider->shouldBe(null);
    }
     
    function it_will_return_null_if_replacement_attorney_decisions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->replacementAttorneyDecisions->shouldBe(null);
    }
     
    function it_can_set_and_get_and_update_the_replacement_attorney_decisions()
    {
        $decisions = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions');
        $decisions2 = clone($decisions);
        $decisions3 = clone($decisions);
        $decisions2->howDetails = 'Second Object';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setReplacementAttorneyDecisions($lpaId, $decisions)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneyDecisions->toJson()->shouldBe($decisions->toJson());

        $this->setReplacementAttorneyDecisions($lpaId, $decisions2)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneyDecisions->toJson()->shouldBe($decisions2->toJson());
    
        $decisions3->when = $decisions3::LPA_DECISION_WHEN_FIRST;
        $this->setReplacementAttorneyDecisions($lpaId, $decisions3)->shouldBe(true);
        $this->updateReplacementAttorneyDecisions($lpaId, ['when'=>$decisions3::LPA_DECISION_WHEN_LAST])->shouldBe(true);
    
        $decisions3->when = $decisions3::LPA_DECISION_WHEN_LAST;
        $this->getApplication($lpaId)->document->replacementAttorneyDecisions->toJson()->shouldBe($decisions3->toJson());
    }
     
    function it_can_delete_the_replacement_attorney_decisions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setReplacementAttorneyDecisions($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions'));
        $this->deleteReplacementAttorneyDecisions($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->replacementAttorneyDecisions->shouldBe(null);
    }
     
    function it_will_return_null_if_primary_attorney_decisions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->primaryAttorneyDecisions->shouldBe(null);
    }
    
    function it_can_set_and_get_and_update_the_primary_attorney_decisions()
    {
        $decisions = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions');
        $decisions2 = clone($decisions);
        $decisions3 = clone($decisions);
        $decisions2->howDetails = 'Second Object';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
    
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneyDecisions->toJson()->shouldBe($decisions->toJson());
    
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions2)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneyDecisions->toJson()->shouldBe($decisions2->toJson());
    
        $decisions3->canSustainLife = true;
        $this->setPrimaryAttorneyDecisions($lpaId, $decisions3)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneyDecisions->toJson()->shouldBe($decisions3->toJson());

    }
     
    function it_can_delete_the_primary_attorney_decisions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setPrimaryAttorneyDecisions($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions'));
        $this->deletePrimaryAttorneyDecisions($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->primaryAttorneyDecisions->shouldBe(null);
    }
     
    function it_will_return_null_if_donor_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->donor->shouldBe(null);
    }
     
    function it_can_set_and_get_the_donor()
    {
        $donor = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Donor');
        $donor2 = clone($donor);
        $donor2->canSign = !$donor->canSign;
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setDonor($lpaId, $donor)->shouldBe(true);
        $this->getApplication($lpaId)->document->donor->toJson()->shouldBe($donor->toJson());
        $this->getApplication($lpaId)->document->donor->otherNames->shouldBe('Fred');

        $this->setDonor($lpaId, $donor2)->shouldBe(true);
        $this->getApplication($lpaId)->document->donor->toJson()->shouldBe($donor2->toJson());
        $this->getApplication($lpaId)->document->donor->otherNames->shouldBe('Fred');
    }
     
    function it_can_delete_the_donor()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setDonor($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Donor'));
        $this->deleteDonor($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->donor->shouldBe(null);
    }
     
    function it_will_return_null_if_correspondent_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->correspondent->shouldBe(null);
    }
    
    function it_can_set_and_get_the_correspondent()
    {
        $correspondent = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Correspondence');
        $correspondent2 = clone($correspondent);
        $correspondent2->contactByPost = !$correspondent->contactByPost;
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setCorrespondent($lpaId, $correspondent)->shouldBe(true);
        $this->getApplication($lpaId)->document->correspondent->toJson()->shouldBe($correspondent->toJson());
        $this->getApplication($lpaId)->document->correspondent->who->shouldBe('other');

        $this->setCorrespondent($lpaId, $correspondent2)->shouldBe(true);
        $this->getApplication($lpaId)->document->correspondent->toJson()->shouldBe($correspondent2->toJson());
    }
     
    function it_can_delete_the_correspondent()
    {
        $correspondent = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Correspondence');
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setCorrespondent($lpaId, $correspondent);
        $this->deleteCorrespondent($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->correspondent->shouldBe(null);
    }
     
    function it_will_return_null_if_payment_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->payment->shouldBe(null);
    }
     
    function it_can_set_and_get_the_payment()
    {
        $payment = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Payment\Payment');
        $payment2 = clone($payment);
        $payment2->amount = 101;
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setPayment($lpaId, $payment)->shouldBe(true);
        $this->getApplication($lpaId)->payment->toJson()->shouldBe($payment->toJson());
        $this->setPayment($lpaId, $payment2)->shouldBe(true);
        $this->getApplication($lpaId)->payment->toJson()->shouldBe($payment2->toJson());
    }
     
    function it_can_delete_the_payment()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setPayment($lpaId, getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Payment\Payment'));
        $this->deletePayment($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->payment->shouldBe(null);
    }
    
    function it_will_return_null_if_instructions_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->instruction->shouldBe(null);
    }
     
    function it_can_set_and_get_the_lpa_instructions()
    {
        $prefString1 = 'These are my instructions';
        $prefString2 = 'These are my instructions too';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setInstructions($lpaId, $prefString1)->shouldBe(true);
        $this->getApplication($lpaId)->document->instruction->shouldBe($prefString1);
        $this->setInstructions($lpaId, $prefString2)->shouldBe(true);
        $this->getApplication($lpaId)->document->instruction->shouldBe($prefString2);
    }
     
    function it_can_delete_the_instructions()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setInstructions($lpaId, 'some-dummy-string');
        $this->deleteInstructions($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->instruction->shouldBe(null);
    }
     
    function it_will_return_null_if_preferences_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->preference->shouldBe(null);
    }
     
    function it_can_set_and_get_the_lpa_preferences()
    {
        $prefString1 = 'These are my preferences';
        $prefString2 = 'These are my preferences too';
         
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setPreferences($lpaId, $prefString1)->shouldBe(true);
        $this->getApplication($lpaId)->document->preference->shouldBe($prefString1);
        $this->setPreferences($lpaId, $prefString2)->shouldBe(true);
        $this->getApplication($lpaId)->document->preference->shouldBe($prefString2);
    }
     
    function it_can_delete_the_preferences()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setPreferences($lpaId, 'some-dummy-string');
        $this->deletePreferences($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->preference->shouldBe(null);
    }
     
    function it_will_return_null_if_type_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->getApplication($lpaId)->document->type->shouldBe(null);
    }
     
    function it_can_set_and_get_the_lpa_type()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setType($lpaId, Document::LPA_TYPE_HW)->shouldBe(true);
        $this->getApplication($lpaId)->document->type->shouldBe(Document::LPA_TYPE_HW);
        $this->setType($lpaId, Document::LPA_TYPE_PF)->shouldBe(true);
        $this->getApplication($lpaId)->document->type->shouldBe(Document::LPA_TYPE_PF);
    }
     
    function it_will_fail_if_attempting_to_set_an_invalid_type()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setType($lpaId, 'this-is-not-valid')->shouldBe(false);
    }
     
    function it_can_delete_the_type()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
        $this->setType($lpaId, Document::LPA_TYPE_PF);
        $this->deleteType($lpaId)->shouldBe(true);
        $this->getApplication($lpaId)->document->type->shouldBe(null);
    }
     
    function it_can_get_an_existing_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication()->get('id');
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
        $lpaId = $this->createApplication()->get('id');

        $this->getApplication($lpaId)->get('id')->shouldBe($lpaId);
        $this->deleteApplication($lpaId)->shouldBe(true);

        // Having deleted the LPA, calling it again will throw and exception.
        $this->shouldThrow('\Opg\Lpa\Api\Client\Exception\ResponseException')->duringDeleteApplication($lpaId);

        $this->getApplication($lpaId)->shouldBe(false);
    }
     
    function it_will_fail_if_attempting_to_delete_a_non_existent_application()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $this->shouldThrow('\Opg\Lpa\Api\Client\Exception\ResponseException')->duringDeleteApplication(uniqid());
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
            'beAnArrayOfAttorneys' => function($subject, $count) {
                return
                    is_array($subject) &&
                    count($subject) == $count &&
                    array_shift($subject) instanceof \Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
            },
            'beAnArrayOfLpaObjects' => function($subject, $count) {
                return 
                    is_array($subject) && 
                    count($subject) == $count &&
                    array_shift($subject) instanceof \Opg\Lpa\DataModel\Lpa\Lpa;
            },
            'beAnArrayOfNotifiedPeople' => function($subject, $count) {
                return 
                    is_array($subject) && 
                    count($subject) == $count &&
                    array_shift($subject) instanceof \Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
            },
            'haveValueInArrayItem' => function($subject, $nthItem, $path, $value) {

                if( !is_array($subject) || count($subject) < 1 ){
                    return false;
                }

                $subject = array_values($subject);

                if( !isset($subject[$nthItem]) ){
                    return false;
                }

                $subject = $subject[$nthItem];

                if( !$subject instanceof \Opg\Lpa\DataModel\AbstractData ){
                    return false;
                }

                $subject = $subject->flatten();

                if( !isset($subject[$path]) || $subject[$path] !== $value ){
                    return false;
                }

                return true;

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
