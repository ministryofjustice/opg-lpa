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
}
