<?php

namespace Application\Model\Service\Mail;

use Laminas\Mail\Message as ZFMessage;

/**
 * Represents an email message to be sent.
 *
 * Class Message
 * @package Application\Model\Service\Mail
 */
class Message extends ZFMessage
{
    private $categories = [];

    /**
     * A timestamp indicating when to send the message.
     * If null, send immediately.
     */
    private $sendAt;

    public function __construct()
    {
        $this->setEncoding("UTF-8");
    }

    public function addCategory($category)
    {
        $this->categories[] = $category;

        return $this;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function setSendAt($sendAt)
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    public function getSendAt()
    {
        return $this->sendAt;
    }
}
