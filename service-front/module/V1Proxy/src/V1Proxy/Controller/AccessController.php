<?php
namespace V1Proxy\Controller;

use RuntimeException;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Session\Container;

class AccessController extends AbstractActionController {


    public function indexAction(){

        # TODO - check the user is singed in. If not, redirect to login

        $config = $this->getServiceLocator()->get('Config')['v1proxy'];

        //-----

        $client = $this->getServiceLocator()->get('ProxyClient');

        // Get the path the user is requesting...
        $path = $this->getRequest()->getUri()->getPath();

        $query = $this->getRequest()->getUri()->getQuery();

        if( !empty($query) ){
            $path = $path.'?'.$query;
        }

        # TODO - remove this.
        if( $path == '/old-dashboard' ){
            $path = '/user/dashboard';
        }

        // Prevent a user creating a new v1 LPA.
        if( $path == '/forward/newlpa' && !$config['allow-v1-laps-to-be-created'] ){
            throw new RuntimeException("Invalid path");
        }

        $options = array( 'headers' => array() );

        //---

        // Check to see if we have a v1 session cookie
        $session = new Container('V1Proxy');

        //---

        $token = $client->getDefaultOption( 'headers/X-AuthOne' );

        // If we have a cookie AND it was created with the current access token...
        if( isset($session->cookie) && isset($session->token) && $session->token == $token ){
            // set it.
            $options['headers']['Cookie'] = $session->cookie;
        }

        //---

        // Copy relevant headers across...
        $headers = $this->getRequest()->getHeaders();

        if( ($value = $headers->get('X-Requested-With')) != false ){
            $options['headers']['X-Requested-With'] = $value->getFieldValue();
        }

        //---

        if( $this->getRequest()->isPost() ){

            // This post may have changed data, so clear the cache.
            $this->getServiceLocator()->get('ProxyDashboard')->clearLpaCacheForUser();

            //---

            // Copy the body across...
            $options['body'] = $this->getRequest()->getContent();

            //---

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
            // Also store the token associated with the cookie, as the cookie becomes invalid if the token changes.
            $session->token = $client->getDefaultOption( 'headers/X-AuthOne' );
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

        // Copy the body across...
        $this->getResponse()->setContent( $body );

        return $this->getResponse();

    }

}
