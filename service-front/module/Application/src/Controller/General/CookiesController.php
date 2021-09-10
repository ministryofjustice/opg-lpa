<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Laminas\Form\Element\Radio;
use Laminas\Http\Header\SetCookie;
use Laminas\Stdlib\RequestInterface;
use Laminas\View\Model\ViewModel;

class CookiesController extends AbstractBaseController
{
    public const COOKIE_POLICY_NAME = 'cookie_policy';
    public const SEEN_COOKIE_NAME = 'seen_cookie_message';

    public function indexAction()
    {
        $form = $this->getFormElementManager()->get('Application\Form\General\CookieConsentForm');
        $form->setAttribute('action', $this->url()->fromRoute('cookies'));

        $request = $this->getRequest();
        $cookiePolicy = $this->fetchPolicyCookie($request);

        if ($request->isPost()) {
            $this->flashMessenger()->addSuccessMessage('You’ve set your cookie preferences.');
            $form->setData($request->getPost());

            if ($form->get('usageCookies')->getValue() === 'yes') {
                $cookiePolicy['usage'] = true;
            } else {
                $cookiePolicy['usage'] = false;

                // remove any GA cookies present. Making any additions or removals of cookies, will require
                // manual testing; see moj.cookie-functions.js also
                $domain = $request->getUri()->getHost();

                $this->removeCookie('_ga', $domain);
                $this->removeCookie('_gid', $domain);
                $this->removeCookie('_gat', $domain);
            }

            $dateTime = new \DateTime('+365 days');

            $this->addCookie(self::COOKIE_POLICY_NAME, json_encode($cookiePolicy, true), $dateTime);
            $this->addCookie(self::SEEN_COOKIE_NAME, 'true', $dateTime);
        }

        if (!is_null($cookiePolicy)) {
            /** @var Radio $ucElement */
            $ucElement = $form->get('usageCookies');
            $ucElement->setValue($cookiePolicy['usage'] ? "yes" : "no");
        }

        return new ViewModel(['form' => $form]);
    }

    private function fetchPolicyCookie(RequestInterface $request): ?array
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

    private function addCookie(String $cookieName, $value, \DateTimeInterface $dateTime)
    {
        $cookie = new SetCookie($cookieName);
        $cookie->setValue($value)
            ->setHttponly(false)
            ->setSecure(true)
            ->setPath('/')
            ->setExpires($dateTime);
        $this->getResponse()->getHeaders()->addHeaderLine(
            $cookie->getFieldName(),
            $cookie->getFieldValue()
        );
    }

    private function removeCookie(String $cookieName, String $domain)
    {
        $cookie = new SetCookie($cookieName);
        $cookie->setValue('')
            ->setHttponly(false)
            ->setSecure(false)
            ->setPath('/')
            ->setExpires(new \DateTime('-1 day'));

        if ($domain !== 'localhost') {
            $cookie->setDomain('.' . $domain);
        }

        $this->getResponse()->getHeaders()->addHeaderLine(
            $cookie->getFieldName(),
            $cookie->getFieldValue()
        );
    }
}
