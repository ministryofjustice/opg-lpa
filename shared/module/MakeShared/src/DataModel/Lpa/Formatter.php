<?php

namespace MakeShared\DataModel\Lpa;

use InvalidArgumentException;

/**
 * Static classes used for formatting values, ready for the end user.
 *
 * Class Formatter
 * @package MakeShared\DataModel\Lpa
 */
class Formatter
{
    /**
     * Number of characters that fit on a single row, in an instructions or preferences box.
     */
    public const INSTRUCTIONS_PREFERENCES_ROW_WIDTH = 84;

    /**
     * Number of rows of characters that fit in the (non-continuation) instructions or preferences box.
     */
    public const INSTRUCTIONS_PREFERENCES_ROW_COUNT = 6;


    /**
     * Formats either a set of passed instructions or preferences, ready to be output into the PDF.
     *
     * @param string|null $text The text to be formatted
     * @return string
     */
    public static function flattenInstructionsOrPreferences(?string $text): string
    {
        /* Early return as null is permitted */
        if (is_null($text)) {
            return '';
        }

        $content = '';

        foreach (explode("\r\n", trim($text)) as $contentLine) {
            $content .= wordwrap($contentLine, self::INSTRUCTIONS_PREFERENCES_ROW_WIDTH, "\r\n", false);
            $content .= "\r\n";
        }

        $paragraphs = explode("\r\n", $content);

        $paraCount = count($paragraphs);
        for ($i = 0; $i < $paraCount; $i++) {
            $paragraphs[$i] = trim($paragraphs[$i]);

            if (strlen($paragraphs[$i]) == 0) {
                // ignore empty paragraphs
                unset($paragraphs[$i]);
            } else {
                // calculate how many space chars to be appended to replace the new line in this paragraph.
                $paragraphs[$i] .= str_repeat(" ", self::INSTRUCTIONS_PREFERENCES_ROW_WIDTH - strlen($paragraphs[$i]));
            }
        }

        return implode("\r\n", $paragraphs);
    }


    /**
     * Formats the id as an A, followed by 11 digits, split into 3 blocks of 4 characters.
     *
     * For example: 'A000 1234 5678'
     *
     * @param int $value The LPA's id.
     * @return string The formatted value.
     */
    public static function id($value)
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException('The passed value must be an integer.');
        }

        return trim(chunk_split('A' . sprintf("%011d", $value), 4, ' '));
    }
}
