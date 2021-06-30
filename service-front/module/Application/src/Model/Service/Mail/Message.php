<?php

namespace Application\Model\Service\Mail;

use Laminas\Mail\Message as LaminasMessage;

/**
 * Represents an email message to be sent.
 *
 * Class Message
 * @package Application\Model\Service\Mail
 */
class Message extends LaminasMessage
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

    /**
     * @return static
     */
    public function addCategory($category): self
    {
        $this->categories[] = $category;

        return $this;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param false|int $sendAt
     *
     * @return static
     */
    public function setSendAt($sendAt): self
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    public function getSendAt()
    {
        return $this->sendAt;
    }
}
