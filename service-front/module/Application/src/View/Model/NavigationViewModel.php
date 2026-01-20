<?php

declare(strict_types=1);

namespace Application\View\Model;

final readonly class NavigationViewModel
{
    public function __construct(
        public bool $userLoggedIn,
        public string $name,
        public ?\DateTimeInterface $lastLoginAt,
        public string $route,
        public bool $hasOneOrMoreLPAs,
    ) {
    }
}
