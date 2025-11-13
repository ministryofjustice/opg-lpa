<?php

declare(strict_types=1);

namespace Application\Model\Service;

trait TokenGenerationTrait
{
    private bool $useHashTokens = false;

    /**
     * @psalm-suppress PossiblyUnusedMethod Called dynamically in ServiceAbstractFactory
     */
    public function setUseHashTokens(bool $useHashTokens): void
    {
        $this->useHashTokens = $useHashTokens;
    }

    protected function makeToken(string $username): string
    {
        if ($this->useHashTokens) {
            return sha1($username);
        } else {
            return make_token();
        }
    }
}
