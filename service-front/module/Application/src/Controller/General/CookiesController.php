<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Laminas\Form\Element\Radio;
use Laminas\Http\Header\SetCookie;
use Laminas\Stdlib\RequestInterface;
use Laminas\View\Model\ViewModel;

class CookiesController extends AbstractBaseController
{
    const COOKIE_POLICY_NAME = 'cookie_policy';
    const SEEN_COOKIE_NAME = 'seen_cookie_message';

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

                //remove any GA cookies present
                setcookie('_ga', null, -1, '/');
                setcookie('_gid', null, -1, '/');
                setcookie('_gat', null, -1, '/');
            }

            $newCookiePolicy = new SetCookie(self::COOKIE_POLICY_NAME);
            $newCookiePolicy->setValue(json_encode($cookiePolicy))
                ->setHttponly(false)
                ->setSecure(true)
                ->setPath('/')
                ->setExpires(new \DateTime('+365 days'));
            $this->getResponse()->getHeaders()->addHeaderLine($newCookiePolicy->getFieldName(), $newCookiePolicy->getFieldValue());

            $seenCookie = new SetCookie(self::SEEN_COOKIE_NAME);
            $seenCookie->setValue('true')
                ->setHttponly(false)
                ->setSecure(true)
                ->setPath('/')
                ->setExpires(new \DateTime('+365 days'));
            $this->getResponse()->getHeaders()->addHeaderLine($seenCookie->getFieldName(), $seenCookie->getFieldValue());

            return $this->redirect()->toRoute('cookies');
        }

        if (!is_null($cookiePolicy)) {
            /** @var Radio $ucElement */
            $ucElement = $form->get('usageCookies');
            $ucElement->setValue($cookiePolicy['usage'] ? "yes" : "no");
        }

        return new ViewModel(['form' => $form]);
    }

    private function fetchPolicyCookie(RequestInterface $request) : ?array
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
