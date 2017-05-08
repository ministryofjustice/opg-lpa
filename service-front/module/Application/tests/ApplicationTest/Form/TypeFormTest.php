<?php
namespace ApplicationTest\Form;
use Application\Form\Lpa\TypeForm;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Form\FormElementManager;

class TypeFormTest extends \PHPUnit_Framework_TestCase
{

    public function testTypeFormReceiveValidData ()
    {
        $typeForm = new TypeForm('name', []);

        $this->addMockServiceLocatorTo($typeForm);

        $typeForm->setData(
                [
                        'type' => Document::LPA_TYPE_HW,
                ]);

        $this->assertEquals(1, $typeForm->isValid());
        $this->assertEquals([], $typeForm->getMessages());

    }

    public function testTypeFormReceiveInvalidData ()
    {
        $typeForm = new TypeForm('name', []);

        $this->addMockServiceLocatorTo($typeForm);

        $typeForm->setData(
                [
                        'type' => 'invalid-lpa-type',
                ]);

        $this->assertEquals(false, $typeForm->isValid());
        $this->assertEquals(
                array(
                        'type' => array(
                                0 => 'allowed-values:property-and-financial,health-and-welfare'
                        )
                ), $typeForm->getMessages());

    }


    public function testTypeFormReceiveCrossSiteForgeryAttack ()
    {
        $typeForm = new TypeForm('name', []);

        $this->addMockServiceLocatorTo($typeForm);

        $typeForm->setData(
                [
                        'type' => '',
                ]);

        $this->assertEquals(0, $typeForm->isValid());
        $this->assertEquals(
                array(
                        'type' => array(
                                0 => 'allowed-values:property-and-financial,health-and-welfare'
                        )
                ), $typeForm->getMessages());
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
