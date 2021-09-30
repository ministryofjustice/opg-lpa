<?php

namespace Application\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\MailParameters;

interface MailTransportInterface
{
    /**
     * Send an email using MailParameters.
     * @throws Laminas\Mail\Exception\ExceptionInterface
     */
    public function send(MailParameters $mailParameters): void;
}
