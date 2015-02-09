<?php
namespace Application\Model\Service\Mail;

use Zend\Mail\Message as ZFMessage;

/**
 * Represents an email message to be sent.
 *
 * Class Message
 * @package Application\Model\Service\Mail
 */
class Message extends ZFMessage {

    private $categories = array();

    public function __construct(){

        // Default to UTF-8
        $this->setEncoding("UTF-8");

    }

    //----------------------------

    public function addCategory( $category ){
        $this->categories[] = $category;
        return $this;
    }

    public function getCategories(){
        return $this->categories;
    }

} // class
