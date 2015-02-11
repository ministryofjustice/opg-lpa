<?php
namespace ApplicationTest\Form;

class DonorFormTest extends \PHPUnit_Framework_TestCase
{

    public function testAllFormsHaveCsrfCheck()
    {
        foreach(glob(__DIR__.'/../../../src/Application/Form/Lpa/*.php') as $filepath) {
        
            $pathInfo = pathinfo(realpath($filepath));
            
            if($pathInfo['filename'] == 'AbstractForm') continue;
            
            $class = "Application\\Form\\Lpa\\".$pathInfo['filename'];
                    
            $form = new $class;
            
            $this->assertInstanceOf('Zend\Form\Element\Csrf', $form->get('secret'));
        }
    }
}
