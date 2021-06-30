<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
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

    public function redirectAction(): \Laminas\Http\Response
    {
        return $this->redirect()->toUrl( $this->config()['redirects']['index'] );
    }

    public function enableCookieAction(): ViewModel{
        return new ViewModel();
    }

    public function termsAction(): ViewModel{
        return new ViewModel();
    }

    public function accessibilityAction(): ViewModel{
        return new ViewModel();
    }

    public function privacyAction(): ViewModel{
        return new ViewModel();
    }

    public function contactAction(): ViewModel{
        return new ViewModel();
    }

}
