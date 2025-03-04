<?php

namespace Application\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;

interface MailTransportInterface
{
    /**
     * Send an email using MailParameters.
     * @throws InvalidArgumentException
     */
    public function send(MailParameters $mailParameters): void;

    /**
     * Health check the mail transport
     *
     * @return array with at least these keys:
     * ['ok' => bool, 'status' => Constants::STATUS_*]
     */
    public function healthcheck(): array;
}
