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
    
    function it_will_return_null_if_seed_has_not_been_set()
    {
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->getSeedDetails($lpaId)->shouldBe(null);
    }
    
    function skipped_it_can_set_and_get_the_lpa_seed()
    {
        $seed1 = uniqid();
        $seed2 = uniqid();
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setSeed($lpaId, $seed1)->shouldBe(true);
        $this->getSeedDetails($lpaId)->shouldBe($seed1);
        $this->setSeed($lpaId, $seed2)->shouldBe(true);
        $this->getSeedDetails($lpaId)->shouldBe($seed2);
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
    
    function skipped_it_can_set_the_who_are_you_details()
    {
        $whoAreYou = getPopulatedEntity('\Opg\Lpa\DataModel\WhoAreYou\WhoAreYou');
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setWhoAreYou($lpaId, $whoAreYou)->shouldBe(true);
    }
    
    function it_can_delete_a_primary_attorney()
    {
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney3 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2->name->first = 'Sally';
        $primaryAttorney3->name->first = 'John';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney3)->shouldBe(true);
    
        $this->deletePrimaryAttorney($lpaId, 3)->shouldBe(true);
        $this->deletePrimaryAttorney($lpaId, 1)->shouldBe(true);
        $this->getPrimaryAttorneys($lpaId)->shouldBeAnArrayOfAttorneys(1);
        $this->getPrimaryAttorneys($lpaId)[0]->name->first->shouldBe('Sally');
    }
    
    function it_can_return_a_list_of_primary_attorneys()
    {
        $primaryAttorney1 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2 = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human');
        $primaryAttorney2->name->first = 'Sally';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->addPrimaryAttorney($lpaId, $primaryAttorney1)->shouldBe(true);
        $this->addPrimaryAttorney($lpaId, $primaryAttorney2)->shouldBe(true);
    
        $this->getPrimaryAttorneys($lpaId)->shouldBeAnArrayOfAttorneys(2);
        $this->getPrimaryAttorneys($lpaId)[0]->name->first->shouldBe('John');
        $this->getPrimaryAttorneys($lpaId)[1]->name->first->shouldBe('Sally');
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
    
    function it_can_set_and_get_the_replacement_attorney_decisions()
    {
        $decisions = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions');
        $decisions2 = clone($decisions);
        $decisions2->howDetails = 'Second Object';
    
        $this->authenticate(TEST_AUTH_EMAIL, TEST_AUTH_PASSWORD);
        $lpaId = $this->createApplication();
        $this->setReplacementAttorneyDecisions($lpaId, $decisions)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions->toJson());
        $this->setReplacementAttorneyDecisions($lpaId, $decisions2)->shouldBe(true);
        $this->getReplacementAttorneyDecisions($lpaId)->toJson()->shouldBe($decisions2->toJson());
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
    
    function it_can_set_and_get_the_primary_attorney_decisions()
    {
        $decisions = getPopulatedEntity('\Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions');
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
        ];
    }
}
