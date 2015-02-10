<?php
namespace ApplicationTest\Form;
use Application\Form\Lpa\TypeForm;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeFormTest extends \PHPUnit_Framework_TestCase
{

    public function testTypeFormReceiveValidData ()
    {
        $typeForm = new TypeForm();
        
        
        $typeForm->setData(
                [
                        'secret'    => $typeForm->get('secret')->getValue(),
                        'type' => Document::LPA_TYPE_HW,
                ]);
        
        $this->assertEquals(1, $typeForm->isValid());
        $this->assertEquals([], $typeForm->getMessages());
        
    }
    
    public function testTypeFormReceiveInvalidData ()
    {
        $typeForm = new TypeForm();
        
        $typeForm->setData(
                [
                        'type' => 'invalid-lpa-type',
                ]);
        
        $this->assertEquals(false, $typeForm->isValid());
        $this->assertEquals(
                array(
                        'secret' => array(
                                'isEmpty' => "Value is required and can't be empty"
                        ),
                        'type' => array(
                                0 => 'allowed-values:property-and-financial,health-and-welfare'
                        )
                ), $typeForm->getMessages());
        
    }

    
    public function testTypeFormReceiveCrossSiteForgeryAttack ()
    {
        $typeForm = new TypeForm();
        
        $typeForm->setData(
                [
                        'secret'    => 'CSRF',
                        'type' => '',
                ]);
        
        $this->assertEquals(0, $typeForm->isValid());
        $this->assertEquals(
                array(
                        'secret' => array(
                                'notSame' => "The form submitted did not originate from the expected site"
                        ),
                        'type' => array(
                                0 => 'allowed-values:property-and-financial,health-and-welfare'
                        )
                ), $typeForm->getMessages());
    }
}
