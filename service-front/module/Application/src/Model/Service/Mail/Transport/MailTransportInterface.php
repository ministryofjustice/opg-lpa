<?php

namespace Application\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\MailParameters;
use Laminas\Mail\Transport\Exception\InvalidArgumentException as TransportInvalidArgumentException;

interface MailTransportInterface
{
    /**
     * Send an email using MailParameters.
     * @throws TransportInvalidArgumentException
     */
    public function send(MailParameters $mailParameters): void;
}
