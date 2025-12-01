<?php

declare(strict_types=1);

namespace Application\Model\Service\Session;

use Laminas\Http\PhpEnvironment\Request;

final class WritePolicy
{
    public function __construct(private readonly ?Request $request = null)
    {
    }

    public function allowsWrite(): bool
    {
        /*
         * If the Laminas X-SessionReadOnly header is present, do not allow session writes.
         */
        if ($this->request?->getHeaders()?->has('X-SessionReadOnly')) {
            return false;
        }

        /*
         * Mezzio does not have Laminas\Http\PhpEnvironment\Request, so also check the global $_SERVER array
         * for the presence of the HTTP_X_SESSIONREADONLY key.
         */
        if (!empty($_SERVER['HTTP_X_SESSIONREADONLY'])) {
            return false;
        }

        return true;
    }
}
