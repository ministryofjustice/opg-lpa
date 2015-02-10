<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageTitle extends AbstractHelper
{
    public function __invoke()
    {
        # TODO - remove.
        // This helper is Deprecated.
        return '';

        switch ($this->view->routeName()) {
            case 'login': return 'Sign in';
            case 'enable-cookie': return 'Enable Cookies';
            case 'register': return 'Create an account';
            case 'home' : return 'Make a lasting power of attorney';
            case 'user/dashboard' : return 'Your LPAs'; 
            case 'forgot-password': return 'Reset your password';
            default: return '@Todo - page title unknown';
        }
    }
}