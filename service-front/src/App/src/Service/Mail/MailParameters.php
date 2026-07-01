<?php

declare(strict_types=1);

namespace App\Service\Mail;

class MailParameters
{
    /** @var string[] */
    private array $toAddresses;
    private ?string $templateRef;
    private array $data;

    /**
     * @param string[]|string $toAddresses
     */
    public function __construct(
        string|array $toAddresses,
        ?string $templateRef = null,
        array $data = [],
    ) {
        if (!is_array($toAddresses)) {
            $toAddresses = [$toAddresses];
        }

        $this->toAddresses = $toAddresses;
        $this->templateRef = $templateRef;
        $this->data = $data;
    }

    /** @return string[] */
    public function getToAddresses(): array
    {
        return $this->toAddresses;
    }

    public function getTemplateRef(): ?string
    {
        return $this->templateRef;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
