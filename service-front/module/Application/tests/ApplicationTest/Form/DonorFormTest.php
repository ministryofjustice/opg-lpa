<?php
namespace ApplicationTest\Form;
use Application\Form\Lpa\DonorForm;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Form\FormElementManager;

class DonorFormTest extends \PHPUnit_Framework_TestCase
{

    public function testDonorFormReceiveValidData ()
    {
        $donorForm = new DonorForm('name', []);

        $this->addMockServiceLocatorTo($donorForm);

        $donorForm->setData(
                [
                        'name-first' => 'first',
                        'name-last' => 'last',
                        'email-address' => '',
                        'address-address1' => 'add1',
                        'address-postcode' => 'postcode',
                        'dob-date' => ['year' => '1984', 'month' => '05', 'day' => '20'],
                        'canSign' => false
                ]);

        $this->assertEquals(true, $donorForm->isValid());
        $this->assertEquals([], $donorForm->getMessages());
    }

    public function testDonorFormReceiveInvalidData ()
    {
        $donorForm = new DonorForm('name', []);

        $this->addMockServiceLocatorTo($donorForm);

        $donorForm->setData(
                [
                        'name-first' => '',
                        'name-last' => '',
                        'address-address1' => 'add1',
                        'email-address' => 'inv@lid@mail.address',
                        'dob-date' => ['year' => '1984', 'month' => '05', 'day' => '20'],
                        'canSign' => 0
                ]);

        $this->assertEquals(false, $donorForm->isValid());


        $this->assertEquals(
                array(
                        'name-first' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'name-last' => array(
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
                        ),
                        'canSign' => array (
                            0 => 'expected-type:bool'
                        ),
                ), $donorForm->getMessages());
    }


    public function testDonorFormReceiveCrossSiteForgeryAttack ()
    {
        $donorForm = new DonorForm('name', []);

        $this->addMockServiceLocatorTo($donorForm);

        $donorForm->setData(
                [
                        'name-first' => '',
                        'name-last' => '',
                        'address-address1' => 'add1',
                        'email-address' => 'inv@lid@mail.address',
                        'dob-date' => ['year' => '1984', 'month' => '05', 'day' => '20'],
                        'canSign' => 0
                ]);

        $this->assertEquals(false, $donorForm->isValid());
        $this->assertEquals(
                array(
                        'name-first' => array(
                                0 => 'cannot-be-blank'
                        ),
                        'name-last' => array(
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
                        ),
                        'canSign' => array (
                            0 => 'expected-type:bool'
                        ),
                ), $donorForm->getMessages());
    }

    private function addMockServiceLocatorTo($form)
    {
        $mockServiceManager = $this->getMockBuilder(FormElementManager::class)->getMock();

        $mockServiceManager->method('getServiceLocator')
            ->will($this->returnSelf());

        $mockServiceManager->method('get')
            ->willReturn(['csrf' => ['salt' => 'Rando_Calrissian']]);

        $form->setServiceLocator($mockServiceManager);
    }
}
