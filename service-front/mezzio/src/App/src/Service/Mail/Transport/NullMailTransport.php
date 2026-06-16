<?php

declare(strict_types=1);

namespace App\Service\Mail\Transport;

use App\Service\Mail\MailParameters;

final class NullMailTransport implements MailTransportInterface
{
    public function send(MailParameters $mailParameters): void
    {
    }

    public function healthcheck(): array
    {
        return ['ok' => true, 'status' => 'ok'];
    }
}
