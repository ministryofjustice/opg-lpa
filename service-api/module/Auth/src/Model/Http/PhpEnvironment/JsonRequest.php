<?php
namespace Application\Model\Http\PhpEnvironment;

use Zend\Json\Json;
use Zend\Stdlib\Parameters;
use Zend\Http\PhpEnvironment\Request as HttpRequest;

/**
 * Extension to HttpRequest the support returning parameters from JSON
 * (as well as the default x-www-form-urlencoded).
 *
 * Class JsonRequest
 * @package Application\Model\Http\PhpEnvironment
 */
class JsonRequest extends HttpRequest {

    public function __construct($allowCustomMethods = true){

        // let the normal happen first...
        parent::__construct( $allowCustomMethods );

        // Then check for a JSON POST or PATCH...


        // If we have a content type...
        if( $type = $this->getHeaders('Content-Type', false) ){

            // and it's JSON...
            if( $type->match( 'application/json' ) ) {

                $data = Json::decode( $this->getContent(), Json::TYPE_ARRAY );

                if( is_array( $data ) ){
                    $this->setPost(new Parameters( $data ));
                }

            }

        }

    }

} // class
