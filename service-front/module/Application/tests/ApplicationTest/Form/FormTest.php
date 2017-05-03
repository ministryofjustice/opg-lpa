<?php
namespace ApplicationTest\Form;

use Opg\Lpa\DataModel\Lpa\Lpa;
use ApplicationTest\Bootstrap;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Form\FormElementManager;

class FormTest extends \PHPUnit_Framework_TestCase
{

    public function testAllFormsHaveCsrfCheck()
    {
        $lpa = new Lpa(file_get_contents(__DIR__.'/../fixtures/pf.json'));
        foreach(glob(__DIR__.'/../../../src/Application/Form/Lpa/*.php') as $filepath) {

            $pathInfo = pathinfo(realpath($filepath));

            if(strstr($pathInfo['filename'], 'Abstract')) continue;

            $class = "Application\\Form\\Lpa\\".$pathInfo['filename'];
            $form = new $class("name", ["lpa"=>$lpa]);

            if(method_exists($form, 'setServiceLocator')) {
                $mockServiceManager = $this->getMockBuilder(FormElementManager::class)->getMock();

                $mockServiceManager->method('getServiceLocator')
                    ->will($this->returnSelf());

                $mockServiceManager->method('get')
                    ->willReturn(['csrf' => ['salt' => 'Rando_Calrissian']]);

                $form->setServiceLocator($mockServiceManager);
            }
            $form->init();

            foreach ($form->getElements() as $key => $value) {
                if (strpos($key, 'secret') === 0) {
                    $secretKeys = $value;
                    break;
                }
            }
            $this->assertInstanceOf('Zend\Form\Element\Csrf', $secretKeys);
        }
    }
}
