<?php

declare(strict_types=1);

namespace App\Filter;

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
        $config->set('HTML.Allowed', '');
        $config->set('Cache.DefinitionImpl', null);
        $this->purifier = new HTMLPurifier($config);
    }

    public function filter($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $purified = $this->purifier->purify($value);
        $decoded = html_entity_decode($purified, ENT_QUOTES, 'UTF-8');
        $decoded = str_replace("\r\n", "\n", $decoded);
        $decoded = str_replace("\n", "\r\n", $decoded);

        return $decoded;
    }
}
