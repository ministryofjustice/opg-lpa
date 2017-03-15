<?php
namespace ApplicationTest\Form;

use Opg\Lpa\DataModel\Lpa\Lpa;
class FormTest extends \PHPUnit_Framework_TestCase
{

    public function testAllFormsHaveCsrfCheck()
    {
        $lpa = new Lpa(file_get_contents(__DIR__.'/../fixtures/pf.json'));
        foreach(glob(__DIR__.'/../../../src/Application/Form/Lpa/*.php') as $filepath) {

            $pathInfo = pathinfo(realpath($filepath));

            if(strstr($pathInfo['filename'], 'Abstract')) continue;

            $class = "Application\\Form\\Lpa\\".$pathInfo['filename'];
            if(in_array($pathInfo['filename'], ['ApplicantForm', 'ApplicantForm', 'FeeForm'])) {
                $form = new $class($lpa);
            }
            else {
                $form = new $class();
            }

            $this->assertInstanceOf('Zend\Form\Element\Csrf', $form->get('secret'));
        }
    }
}
