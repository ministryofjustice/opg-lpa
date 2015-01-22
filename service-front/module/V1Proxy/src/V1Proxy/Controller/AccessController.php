<?php
namespace V1Proxy\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Session\Container;

class AccessController extends AbstractActionController {


    public function indexAction(){

        # TODO - check the user is singed in. If not, redirect to login

        //-----

        $client = $this->getServiceLocator()->get('ProxyClient');

        // Get the path the user is requesting...
        $path = $this->getRequest()->getUri()->getPath();

        // Map the /static path back to the original.
        //$path = str_replace( '/old-static/', '/static/', $path );

        if( $path == '/old-dashboard' ){
            $path = '/user/dashboard';
        }

        $options = array( 'headers' => array() );

        //---

        // Check to see if we have a v1 session cookie
        $session = new Container('V1Proxy');

        if( $session->cookie ){
            // if so, set it...
            $options['headers']['Cookie'] = $session->cookie;
        }

        //---

        if( $this->getRequest()->isPost() ){

            // This post may have changed data, so clear the cache.
            $this->getServiceLocator()->get('ProxyDashboard')->clearLpaCacheForUser();

            //---

            // Copy the body across...
            $options['body'] = $this->getRequest()->getContent();

            //---

            // Copy relevant headers across...
            $headers = $this->getRequest()->getHeaders();

            if( ($value = $headers->get('Content-Length')) != false ){
                $options['headers']['Content-Length'] = $value->getFieldValue();
            }

            if( ($value = $headers->get('Content-Type')) != false ){
                $options['headers']['Content-Type'] = $value->getFieldValue();
            }

            //---

            $response = $client->post( 'http://front.local' . $path, $options );

        } else {

            // otherwise assume GET. No others methods are allowed.
            $response = $client->get( 'http://front.local' . $path, $options );

        }

        //------------------------------------
        // Build out response from v1's

        $headers = $response->getHeaders();

        // If there was a cookie set in the response...
        if( isset($headers['Set-Cookie']) ){
            // store a copy
            $session->cookie = array_pop($headers['Set-Cookie']);
        }

        // Define what headers we want to be relayed across...
        $headers = array_intersect_key( $headers, array_flip([
            'Location',
            'Pragma',
            'Cache-Control',
            'Expires',
            'Content-Type',
            'Content-Length',
        ]) );

        // Bring the array back to only 1 level deep...
        $headers = array_map( function ($v){ return array_pop($v); }, $headers );

        // Add the headers will relaying across...
        foreach( $headers as $k => $v ){
            $this->getResponse()->getHeaders()->addHeaderLine("{$k}: $v");
        }

        // Bring the response code across...
        $this->getResponse()->setStatusCode( $response->getStatusCode() );

        // Get the body from v1
        $body = $response->getBody();

        $body = str_replace( '/static/', '/old-static/', $body );

        // Copy the body across...
        $this->getResponse()->setContent( $body );

        return $this->getResponse();

    }

}
