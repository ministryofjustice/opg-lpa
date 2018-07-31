<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\Controller\Plugin;

use Zend\Mvc\Console\View\ViewModel as ConsoleModel;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class CreateConsoleNotFoundModel extends AbstractPlugin
{
    /**
     * Create a console view model representing a "not found" action
     *
     * @return ConsoleModel
     */
    public function __invoke()
    {
        $viewModel = new ConsoleModel();

        $viewModel->setErrorLevel(1);
        $viewModel->setResult('Page not found');

        return $viewModel;
    }
}
