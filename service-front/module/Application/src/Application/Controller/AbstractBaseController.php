<?php

namespace Application\Controller;

use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractActionController;

abstract class AbstractBaseController extends AbstractActionController
{
    use LoggerTrait;

    /**
     * Ensures cookies are enabled.
     *
     * If we're passed the session cookies, we know they're enabled, so all is good.
     *
     * If we're not passed a session cookie, this could be because:
     *  A - The session simply has not been started; or
     *  B - They do not have cookies enabled.
     *
     * To rule out A, we redirect they user back to 'this' page, adding ?cookie=1 to the URL to record the
     * redirect has happened. This ensures the session *should* have been started.
     *
     * Thus is the session cookies doesn't exist AND cookie=1, we can assume the client is not sending cookies.
     *
     * @param $routeName string The route name for the current page for if a redirect is needed.
     * @return bool|\Zend\Http\Response Iff bool true is returned, all is good. Otherwise the calling controller should return the response.
     */
    protected function checkCookie( $routeName ){

        // Only do a cookie check on GETs
        if( $this->getRequest()->getMethod() !== 'GET' ){
            return true;
        }

        //---

        // Get the cookie names used for the session
        $sessionCookieName = $this->getServiceLocator()->get('Config')['session']['native_settings']['name'];

        $cookies = $this->getRequest()->getCookie();

        // If there cookies...
        if( $cookies !== false ){
            // Check for the session cookie...
            $cookieExists = $cookies->offsetExists( $sessionCookieName );
        }

        //---

        if( !$cookies || !$cookieExists ){

            /*
             * Redirect them back to the same page, appending ?cookie=1 to the URL.
             *  A - If they have cookies enabled, they should now have the session cookie, so all is well.
             *  B - They still don't have the cookie, we can assume they have cookies disabled.
             */

            $cookieRedirect = (bool)$this->params()->fromQuery('cookie');

            if( !$cookieRedirect ){

                // Cannot see a cookie, so redirect them back to this page (which will set one), ready to check again.
                return $this->redirect()->toRoute($routeName, array(), [ 'query' => ['cookie' => '1'] ]);

            } else {

                // Cookie is not set even after we've done a redirect, so assume the client doesn't support cookies.
                return $this->redirect()->toRoute( 'enable-cookie' );

            }

        } // if

        return true;

    } // function

    /**
     * Checks if a user is logged in and redirects them to the dashboard if they are.
     *
     * This is used to prevent signed in users accessing pages they should not.
     *
     * e.g. login, register, etc.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function preventAuthenticatedUser(){

        $identity = $this->getServiceLocator()->get('AuthenticationService')->getIdentity();

        if( !is_null($identity) ){
            return $this->redirect()->toRoute( 'user/dashboard' );
        }

        return true;

    } // function

    /**
     * Returns the global config.
     *
     * @return array
     */
    protected function config(){
        return $this->getServiceLocator()->get('Config');
    }
    
    /**
     * Returns the cache object.
     *
     * @return array
     */
    protected function cache(){
        return $this->getServiceLocator()->get('Cache');
    }

} // class
