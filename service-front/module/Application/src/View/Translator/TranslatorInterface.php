<?php

declare(strict_types=1);

namespace Laminas\I18n\Translator;

interface TranslatorInterface
{
    public function translate(string $message): string;
}
