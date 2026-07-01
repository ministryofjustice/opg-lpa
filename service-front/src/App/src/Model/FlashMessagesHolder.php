<?php

declare(strict_types=1);

namespace App\Model;

use Mezzio\Flash\FlashMessagesInterface;

/**
 * Per-request mutable holder for the Mezzio flash messages instance.
 *
 * Populated by FlashMessagesHolderMiddleware after FlashMessageMiddleware runs;
 * consumed by LegacyCompatExtension for the flashMessenger() Twig function.
 *
 * Registered as a shared container singleton so the same instance is injected
 * into both the middleware and the Twig extension.
 */
class FlashMessagesHolder
{
    private ?FlashMessagesInterface $flashMessages = null;

    public function set(FlashMessagesInterface $flashMessages): void
    {
        $this->flashMessages = $flashMessages;
    }

    public function get(): ?FlashMessagesInterface
    {
        return $this->flashMessages;
    }
}
