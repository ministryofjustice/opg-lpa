<?php

declare(strict_types=1);

namespace App\View\Twig;

/**
 * Tiny stub returned by the `flashMessenger()` Twig function.
   Once real flash messaging is wired up in the Mezzio app this stub
 * will be replaced with a proper implementation.
 */
final class FlashMessengerStub
{
    public function __call(string $name, array $arguments): mixed
    {
        if ($name === 'render') {
            return '';
        }

        return $this;
    }
}
