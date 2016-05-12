<?php

namespace Omnipay\WorldPayXML;

use Omnipay\Common\AbstractGateway;

/**
 * WorldPay XML Class
 *
 * @link http://www.worldpay.com/support/bg/xml/kb/dxml_inv.pdf
 */
class Gateway extends AbstractGateway
{
    /**
     * Get name
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return 'WorldPayXML';
    }

    /**
     * Get default parameters
     *
     * @access public
     * @return array
     */
    public function getDefaultParameters()
    {
        return array(
            'installation' => '',
            'merchant'     => '',
            'password'     => '',
            'testMode'     => false,
        );
    }

    /**
     * Get accept header
     *
     * @access public
     * @return string
     */
    public function getAcceptHeader()
    {
        return $this->getParameter('acceptHeader');
    }

    /**
     * Set accept header
     *
     * @param string $value Accept header value
     *
     * @access public
     * @return void
     */
    public function setAcceptHeader($value)
    {
        return $this->setParameter('acceptHeader', $value);
    }

    /**
     * Get installation
     *
     * @access public
     * @return string
     */
    public function getInstallation()
    {
        return $this->getParameter('installation');
    }

    /**
     * Set installation
     *
     * @param string $value Installation value
     *
     * @access public
     * @return void
     */
    public function setInstallation($value)
    {
        return $this->setParameter('installation', $value);
    }

    /**
     * Get merchant
     *
     * @access public
     * @return string
     */
    public function getMerchant()
    {
        return $this->getParameter('merchant');
    }

    /**
     * Set merchant
     *
     * @param string $value Merchant value
     *
     * @access public
     * @return void
     */
    public function setMerchant($value)
    {
        return $this->setParameter('merchant', $value);
    }

    /**
     * Get pa response
     *
     * @access public
     * @return string
     */
    public function getPaResponse()
    {
        return $this->getParameter('pa_response');
    }

    /**
     * Set pa response
     *
     * @param string $value Pa response value
     *
     * @access public
     * @return void
     */
    public function setPaResponse($value)
    {
        return $this->setParameter('pa_response', $value);
    }

    /**
     * Get password
     *
     * @access public
     * @return string
     */
    public function getPassword()
    {
        return $this->getParameter('password');
    }

    /**
     * Set password
     *
     * @param string $value Password value
     *
     * @access public
     * @return void
     */
    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    /**
     * Get redirect cookie
     *
     * @access public
     * @return string
     */
    public function getRedirectCookie()
    {
        return $this->getParameter('redirect_cookie');
    }

    /**
     * Set redirect cookie
     *
     * @param string $value Redirect cookie value
     *
     * @access public
     * @return void
     */
    public function setRedirectCookie($value)
    {
        return $this->setParameter('redirect_cookie', $value);
    }

    /**
     * Get redirect echo
     *
     * @access public
     * @return string
     */
    public function getRedirectEcho()
    {
        return $this->getParameter('redirect_echo');
    }

    /**
     * Set redirect echo
     *
     * @param string $value Redirect echo value
     *
     * @access public
     * @return void
     */
    public function setRedirectEcho($value)
    {
        return $this->setParameter('redirect_echo', $value);
    }

    /**
     * Get session
     *
     * @access public
     * @return string
     */
    public function getSession()
    {
        return $this->getParameter('session');
    }

    /**
     * Set session
     *
     * @param string $value Session value
     *
     * @access public
     * @return void
     */
    public function setSession($value)
    {
        return $this->setParameter('session', $value);
    }

    /**
     * Get user agent header
     *
     * @access public
     * @return string
     */
    public function getUserAgentHeader()
    {
        return $this->getParameter('userAgentHeader');
    }

    /**
     * Set user agent header
     *
     * @param string $value User agent header value
     *
     * @access public
     * @return void
     */
    public function setUserAgentHeader($value)
    {
        return $this->setParameter('userAgentHeader', $value);
    }

    /**
     * Get user ip
     *
     * @access public
     * @return string
     */
    public function getUserIP()
    {
        return $this->getParameter('userIP');
    }

    /**
     * Set user ip
     *
     * @param string $value User ip value
     *
     * @access public
     * @return void
     */
    public function setUserIP($value)
    {
        return $this->setParameter('userIP', $value);
    }

    /**
     * Purchase
     *
     * @param array $parameters Parameters
     *
     * @access public
     * @return \Omnipay\WorldPayXML\Message\PurchaseRequest
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest(
            '\Omnipay\WorldPayXML\Message\PurchaseRequest',
            $parameters
        );
    }
}
