<?php

declare(strict_types=1);

namespace Application\Filter;

use HTMLPurifier;
use HTMLPurifier_Config;
use Laminas\Filter\FilterInterface;

/**
 * Sanitises input using HTML Purifier to remove dangerous HTML tags.
 * HTML Purifier encodes lone angle brackets as entities (&lt; &gt;);
 * we decode them back so the stored value contains the original characters.
 */
class StripTagsPreservingAngleBrackets implements FilterInterface
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // Strip all HTML tags — no tags are allowed through
        $config->set('HTML.Allowed', '');

        // Disable cache to avoid filesystem permission issues
        $config->set('Cache.DefinitionImpl', null);

        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $purified = $this->purifier->purify($value);

        // Decode HTML entities back to literal characters so that
        // stored data contains "<" not "&lt;". Output escaping in
        // templates (Twig auto-escape, FormTextarea) handles XSS.
        return html_entity_decode($purified, ENT_QUOTES, 'UTF-8');
    }
}
