<?php
namespace ApplicationTest\Form;
use Application\Form\Lpa\DonorForm;

class DonorFormTest extends \PHPUnit_Framework_TestCase
{

    public function testDonorFormCanBeCreated ()
    {
        $donorForm = new DonorForm();
        
        $donorForm->setData(
                [
                        'name-first' => 'first',
                        'name-last' => 'last',
                        'address-address1' => 'add1',
                        'address-postcode' => 'postcode',
                        'dob-date' => new \DateTime('1984-05-20'),
                        'canSign' => 0
                ]);
        
        $this->assertEquals(1, $donorForm->isValid());
        $this->assertEquals([], $donorForm->getMessages());
        
        $donorForm->setData(
                [
                        'name-first' => '',
                        'name-last' => '',
                        'address-address1' => 'add1',
                        'dob-date' => '',
                        'canSign' => 0
                ]);
        
        $this->assertEquals(0, $donorForm->isValid());
        $this->assertEquals(
                array(
                        'name-first' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'name-last' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'dob-date' => array(
                                0 => 'must-be-less-than-or-equal:'.Date('M j, Y', time()).' 12:00 AM',
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
