<?php

declare(strict_types=1);

namespace Application\Model\Service\Session;

use Laminas\Session\SaveHandler\SaveHandlerInterface;

final class NativeSessionConfig
{
    public function __construct(
        private readonly array $settings,
        private readonly SaveHandlerInterface $saveHandler
    ) {
    }

    public function configure(): void
    {
        if (!empty($this->settings['name'])) {
            session_name($this->settings['name']);
        }
        if (isset($this->settings['cookie_secure'])) {
            ini_set('session.cookie_secure', (string)(int)$this->settings['cookie_secure']);
        }
        if (isset($this->settings['cookie_httponly'])) {
            ini_set('session.cookie_httponly', (string)(int)$this->settings['cookie_httponly']);
        }
        if (isset($this->settings['gc_probability'])) {
            ini_set('session.gc_probability', (string)(int)$this->settings['gc_probability']);
            ini_set('session.gc_divisor', '100');
        }
        // reasonable default if not already set
        if ('' === (string) ini_get('session.cookie_samesite')) {
            ini_set('session.cookie_samesite', 'Lax');
        }

        session_set_save_handler($this->saveHandler, true);
    }

    public function startIfNeeded(): void
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
        }
    }
}
