<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Laminas\Form\Element\Radio;
use Laminas\Http\Header\SetCookie;
use Laminas\Http\Request as HttpRequest;
use Laminas\Stdlib\RequestInterface;
use Laminas\View\Model\ViewModel;

class CookiesController extends AbstractBaseController
{
    public const COOKIE_POLICY_NAME = 'cookie_policy';

    public function indexAction()
    {
        $form = $this->getFormElementManager()->get('Application\Form\General\CookieConsentForm');
        $form->setAttribute('action', $this->url()->fromRoute('cookies'));

        /** @var HttpRequest */
        $request = $this->getRequest();

        $cookiePolicy = $this->fetchPolicyCookie($request);

        if (!is_null($cookiePolicy)) {
            /** @var Radio $ucElement */
            $ucElement = $form->get('usageCookies');
            $ucElement->setValue($cookiePolicy['usage'] ? "yes" : "no");
        }

        return new ViewModel(['form' => $form]);
    }

    private function fetchPolicyCookie(HttpRequest $request): ?array
    {
        $cookies = $request->getCookie();
        if ($cookies !== false && $cookies->offsetExists(self::COOKIE_POLICY_NAME)) {
            $cookiePolicy = json_decode($cookies[self::COOKIE_POLICY_NAME], true);

            if (is_array($cookiePolicy)) {
                return $cookiePolicy;
            }
        }

        return null;
    }
}
