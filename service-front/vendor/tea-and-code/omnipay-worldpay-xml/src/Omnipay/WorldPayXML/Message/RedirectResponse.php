<?php

namespace Omnipay\WorldPayXML\Message;

use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * WorldPay XML Redirect Response
 */
class RedirectResponse extends Response implements RedirectResponseInterface
{
    /**
     * Get redirect cookie
     *
     * @access public
     * @return string
     */
    public function getRedirectCookie()
    {
        $cookieJar = $this->request->getCookiePlugin()->getCookieJar();

        foreach ($cookieJar->all() as $cookie) {
            if ($cookie->getName() == 'machine') {
                return $cookie->getValue();
            }
        }

        return '';
    }

    /**
     * Get redirect echo
     *
     * @access public
     * @return string
     */
    public function getRedirectEcho()
    {
        return $this->data->echoData;
    }

    /**
     * Get redirect data
     *
     * @access public
     * @return array
     */
    public function getRedirectData()
    {
        return array(
            'PaReq'   => $this->data->requestInfo->request3DSecure->paRequest,
            'TermUrl' => $this->request->getTermUrl()
        );
    }

    /**
     * Get redirect method
     *
     * @access public
     * @return string
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * Get redirect url
     *
     * @access public
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->data->requestInfo->request3DSecure->issuerURL;
    }
}
