<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\AbstractCsrfForm;
use Application\Form\Lpa\AbstractLpaForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\DataModel\Lpa\Lpa;
use ReflectionClass;
use function PHPUnit\Framework\assertEquals;

class FormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    public function testAllFormsHaveCsrfCheck()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));

        $csrfNames = [];

        foreach (glob(__DIR__ . '/../../../src/Form/Lpa/*.php') as $filepath) {
            $pathInfo = pathinfo(realpath($filepath));
            $className = 'Application\\Form\\Lpa\\' . $pathInfo['filename'];
            $reflectionClass = new ReflectionClass($className);

            if (class_exists($className) && !$reflectionClass->isAbstract() && $reflectionClass->isSubclassOf(AbstractLpaForm::class)) {
                //  Instantiate the form object and test
                $form = new $className('name', [
                    'lpa' => $lpa
                ]);

                /** @var AbstractCsrfForm $form */
                $this->setUpForm($form);

                $secretKeys = null;

                foreach ($form->getElements() as $key => $value) {
                    if (strpos($key, 'secret') === 0) {
                        $secretKeys = $value;
                        break;
                    }
                }

                $this->assertInstanceOf('Laminas\Form\Element\Csrf', $secretKeys);
                array_push($csrfNames, $secretKeys->getName());
            }
        }

        // ensure no duplicates in csrfs
        assertEquals($csrfNames, array_unique($csrfNames));
    }
}
