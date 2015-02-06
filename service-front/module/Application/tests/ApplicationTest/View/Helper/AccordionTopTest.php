<?php
namespace ApplicationTest\View\Helper;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplatePathStack;

/**
 * AccordionTop test case.
 */
class AccordionTopTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->lpa = null;
        
        parent::tearDown();
    }

    /**
     * Test
     */
    public function testAccordionTop ()
    {
        $accordion = $this->getMockBuilder('Application\View\Helper\AccordionTop')
                    ->setMethods(array('getRouteName', 'getView'))
                    ->getMock();
        
        $view = new PhpRenderer();
        
        $resolver = new TemplatePathStack([
            'script_paths' => [
                __DIR__.'/../../../../view'
            ]
        ]);
        
        $view->setResolver($resolver);
        $view->viewModel()->setTemplate('aaa');
        
        $accordion->method('getRouteName')->willReturn('lpa/donor');
        $accordion->method('getView')->willReturn($view);
        
        $json = file_get_contents(__DIR__.'/hw.json');
        $lpa = new Lpa($json);
        $lpa->user = 4678234672;
        $lpa->id = 75095661466;
        $lpa->completedAt = new \DateTime();
        print_r($accordion->__invoke($lpa));
        
    }
}

