<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use Laminas\View\Model\ViewModel;

class HomeController extends AbstractBaseController
{
    public function indexAction()
    {
        $dockerTag = $this->config()['version']['tag'];

        return new ViewModel([
            'lpaFee' => Calculator::getFullFee(),
            'dockerTag' => $dockerTag,
        ]);
    }

    public function redirectAction()
    {
        return $this->redirectToUrl($this->config()['redirects']['index']);
    }

    public function enableCookieAction()
    {
        return new ViewModel();
    }

    public function termsAction()
    {
        return new ViewModel();
    }

    public function accessibilityAction()
    {
        return new ViewModel();
    }

    public function privacyAction()
    {
        return new ViewModel();
    }

    public function contactAction()
    {
        return new ViewModel();
    }
}
