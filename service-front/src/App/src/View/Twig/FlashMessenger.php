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
     * Renders flash messages of the given type as a GOV.UK notification banner.
     *
     * @param string $type      Flash type: 'error', 'success', 'warning', 'info', 'default'
     * @param string $class     Unused — kept for backwards-compatible call sites
     * @param string $iconClass Unused — kept for backwards-compatible call sites
     */
    public function render(string $type, string $class = '', string $iconClass = ''): string
    {
        $messages = $this->getMessages($type);
        if ($messages === []) {
            return '';
        }

        $messageHtml = implode('</p><p class="govuk-body">', array_map(
            static fn(string $m) => htmlspecialchars($m, ENT_QUOTES | ENT_SUBSTITUTE),
            $messages,
        ));

        $isSuccess = ($type === 'success');
        $bannerClass = $isSuccess
            ? 'govuk-notification-banner govuk-notification-banner--success'
            : 'govuk-notification-banner';
        $role = $isSuccess ? 'alert' : 'region';
        $title = match ($type) {
            'success' => 'Success',
            'error'   => 'There is a problem',
            default   => 'Important',
        };
        $titleId = 'govuk-notification-banner-title-' . htmlspecialchars($type, ENT_QUOTES);
        $ariaAttr = $isSuccess ? '' : sprintf(' aria-labelledby="%s"', $titleId);
        $idAttr   = $isSuccess ? '' : sprintf(' id="%s"', $titleId);

        return sprintf(
            '<div class="%s" role="%s"%s data-module="govuk-notification-banner">'
            . '<div class="govuk-notification-banner__header">'
            . '<h2 class="govuk-notification-banner__title"%s>%s</h2>'
            . '</div>'
            . '<div class="govuk-notification-banner__content">'
            . '<p class="govuk-body">%s</p>'
            . '</div>'
            . '</div>',
            $bannerClass,
            $role,
            $ariaAttr,
            $idAttr,
            htmlspecialchars($title, ENT_QUOTES),
            $messageHtml,
        );
    }
}
