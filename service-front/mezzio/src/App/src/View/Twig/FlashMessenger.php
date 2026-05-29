<?php

declare(strict_types=1);

namespace App\View\Twig;

use App\Model\FlashMessagesHolder;

/**
 * Returned by the flashMessenger() Twig function.
 * Reads messages from the Mezzio FlashMessagesInterface and renders them as HTML.
 */
final class FlashMessenger
{
    public const string ERROR   = 'flash_error';
    public const string SUCCESS = 'flash_success';
    public const string WARNING = 'flash_warning';
    public const string INFO    = 'flash_info';
    public const string DEFAULT = 'flash_default';

    /** Maps flash type names (used in templates) to session keys used in handlers */
    private const array TYPE_KEY_MAP = [
        'error'   => self::ERROR,
        'success' => self::SUCCESS,
        'warning' => self::WARNING,
        'info'    => self::INFO,
        'default' => self::DEFAULT,
    ];

    public function __construct(
        private readonly FlashMessagesHolder $holder,
    ) {
    }

    /**
     * Returns the flash messages for a given type as an array of strings.
     *
     * @return string[]
     */
    public function getMessages(string $type): array
    {
        $flash = $this->holder->get();
        if ($flash === null) {
            return [];
        }

        $key = self::TYPE_KEY_MAP[$type] ?? ('flash_' . $type);
        $value = $flash->getFlash($key);

        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        return [(string) $value];
    }

    /**
     * Renders flash messages of the given type as an HTML alert panel.
     *
     * @param string $type      Flash type: 'error', 'success', 'warning', 'info', 'default'
     * @param string $class     CSS class for the outer div (e.g. 'alert-error')
     * @param string $iconClass CSS class for the icon (e.g. 'icon-cross')
     */
    public function render(string $type, string $class = '', string $iconClass = ''): string
    {
        $messages = $this->getMessages($type);
        if ($messages === []) {
            return '';
        }

        $messageHtml = implode('</p><p>', array_map(
            static fn(string $m) => htmlspecialchars($m, ENT_QUOTES | ENT_SUBSTITUTE),
            $messages,
        ));

        return sprintf(
            '<div class="alert panel text %s" role="alert">'
            . '<i class="icon %s" role="presentation"></i>'
            . '<div class="alert-message"><p>%s</p></div>'
            . '</div>',
            htmlspecialchars($class, ENT_QUOTES),
            htmlspecialchars($iconClass, ENT_QUOTES),
            $messageHtml,
        );
    }
}
