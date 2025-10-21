<?php

declare(strict_types=1);

namespace Laminas\I18n\View\Helper;

use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorInterface;

if (!\class_exists(AbstractTranslatorHelper::class)) {
    abstract class AbstractTranslatorHelper implements TranslatorAwareInterface
    {
        protected ?TranslatorInterface $translator = null;
        protected bool $translatorEnabled = true;
        protected string $translatorTextDomain = 'default';

        public function setTranslator(?TranslatorInterface $translator = null, ?string $textDomain = null): void
        {
            $this->translator = $translator;
            if ($textDomain !== null) {
                $this->translatorTextDomain = $textDomain;
            }
        }

        public function getTranslator(): ?TranslatorInterface
        {
            return $this->translator;
        }

        public function hasTranslator(): bool
        {
            return $this->translator !== null;
        }

        public function setTranslatorTextDomain(string $textDomain): void
        {
            $this->translatorTextDomain = $textDomain;
        }

        public function getTranslatorTextDomain(): string
        {
            return $this->translatorTextDomain;
        }

        public function setTranslatorEnabled(bool $enabled): void
        {
            $this->translatorEnabled = $enabled;
        }

        public function isTranslatorEnabled(): bool
        {
            return $this->translatorEnabled;
        }

        public function translate(string $message): string
        {
            return $message;
        }
    }
}
