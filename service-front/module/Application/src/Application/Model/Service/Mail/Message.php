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
    
    /**
     * A timestamp indicating when to send the message.
     * If null, send immediately.
     */
    private $sendAt;

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
    
    /**
     * @return $sendAt
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }
    
    /**
     * @param number $sendAt
     */
    public function setSendAt($sendAt)
    {
        $this->sendAt = $sendAt;
    }

} // class
