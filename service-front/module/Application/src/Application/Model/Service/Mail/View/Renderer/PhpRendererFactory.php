<?php
namespace Application\Model\Service\Mail\View\Renderer;

use Zend\Mail\Exception\RuntimeException;

use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplatePathStack;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Creates an instance of PhpRenderer configured to access the view/email (by default) views.
 *
 * Class PhpRendererFactory
 * @package Application\Model\Service\Mail\View\Renderer
 */
class PhpRendererFactory implements FactoryInterface {

    public function createService(ServiceLocatorInterface $serviceLocator){

        $config = $serviceLocator->get('Config');

        if( !isset($config['email_view_manager']) || !isset($config['email_view_manager']['template_path_stack']) ){
            throw new RuntimeException('No configuration for Email PhpRenderer found');
        }

        $templatePath = $config['email_view_manager']['template_path_stack'];

        //---

        $stack = new TemplatePathStack( array( 'script_paths'=>$templatePath ) );

        //---

        $renderer = new PhpRenderer();

        $renderer->setResolver($stack);

        //---

        return $renderer;

    } // function

} // class