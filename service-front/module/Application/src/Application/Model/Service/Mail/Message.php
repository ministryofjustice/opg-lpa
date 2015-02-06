<?php
namespace Application\Model\Service\Mail;

use Zend\Mail\Message as ZFMessage;

/**
 * Represents an email message to be sent. This should only be used for settings LPA specific default.
 *
 * Class Message
 * @package Application\Model\Service\Mail
 */
class Message extends ZFMessage {

    public function __construct(){

        // Default to UTF-8
        $this->setEncoding("UTF-8");

    }

} // class
