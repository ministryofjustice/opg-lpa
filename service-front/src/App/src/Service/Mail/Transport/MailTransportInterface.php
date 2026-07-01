<?php

declare(strict_types=1);

namespace App\Service\Mail\Transport;

use App\Service\Mail\Exception\InvalidArgumentException;
use App\Service\Mail\MailParameters;

interface MailTransportInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function send(MailParameters $mailParameters): void;

    /**
     * @return array{ok: bool, status: string}
     */
    public function healthcheck(): array;
}
