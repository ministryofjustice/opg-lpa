<?php

declare(strict_types=1);

namespace Laminas\I18n\Translator;

interface TranslatorAwareInterface
{
    public function setTranslator(?TranslatorInterface $translator = null, ?string $textDomain = null): void;
    public function getTranslator(): ?TranslatorInterface;
    public function hasTranslator(): bool;
    public function setTranslatorTextDomain(string $textDomain): void;
    public function getTranslatorTextDomain(): string;
    public function setTranslatorEnabled(bool $enabled): void;
    public function isTranslatorEnabled(): bool;
}
