<?php
namespace ApplicationTest\Form;
use Application\Form\Lpa\DonorForm;

class DonorFormTest extends \PHPUnit_Framework_TestCase
{

    public function testDonorFormReceiveValidData ()
    {
        $donorForm = new DonorForm();
        
        
        $donorForm->setData(
                [
                        'secret'    => $donorForm->get('secret')->getValue(),
                        'name-first' => 'first',
                        'name-last' => 'last',
                        'email-address' => '',
                        'address-address1' => 'add1',
                        'address-postcode' => 'postcode',
                        'dob-date' => '1984-05-20',
                        'canSign' => 0
                ]);
        
        $this->assertEquals(true, $donorForm->isValid());
        $this->assertEquals([], $donorForm->getMessages());
        
    }
    
    public function testDonorFormReceiveInvalidData ()
    {
        $donorForm = new DonorForm();
        
        $donorForm->setData(
                [
                        'name-first' => '',
                        'name-last' => '',
                        'address-address1' => 'add1',
                        'email-address' => 'inv@lid@mail.address',
                        'dob-date' => '',
                        'canSign' => 0
                ]);
        
        $this->assertEquals(false, $donorForm->isValid());
        $this->assertEquals(
                array(
                        'secret' => array(
                                'isEmpty' => "Value is required and can't be empty"
                        ),
                        'name-first' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'name-last' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'dob-date' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'email-address' => array(
                                0 => 'invalid-email-address'
                        ),
                        'address-address2' => array(
                                0 => 'linked-1-cannot-be-null'
                        ),
                        'address-postcode' => array(
                                0 => 'linked-1-cannot-be-null'
                        )
                ), $donorForm->getMessages());
        
    }

    
    public function testDonorFormReceiveCrossSiteForgeryAttack ()
    {
        $donorForm = new DonorForm();
        
        $donorForm->setData(
                [
                        'secret'    => 'CSRF',
                        'name-first' => '',
                        'name-last' => '',
                        'address-address1' => 'add1',
                        'email-address' => 'inv@lid@mail.address',
                        'dob-date' => '',
                        'canSign' => 0
                ]);
        
        $this->assertEquals(false, $donorForm->isValid());
        $this->assertEquals(
                array(
                        'secret' => array(
                                'notSame' => "The form submitted did not originate from the expected site"
                        ),
                        'name-first' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'name-last' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'dob-date' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'email-address' => array(
                                0 => 'invalid-email-address'
                        ),
                        'address-address2' => array(
                                0 => 'linked-1-cannot-be-null'
                        ),
                        'address-postcode' => array(
                                0 => 'linked-1-cannot-be-null'
                        )
                ), $donorForm->getMessages());
    }
}
