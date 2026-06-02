<?php

declare(strict_types=1);

namespace App\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface as LaminasAdapterInterface;

interface AdapterInterface extends LaminasAdapterInterface
{
    public function setEmail(#[\SensitiveParameter] mixed $email): static;

    public function setPassword(#[\SensitiveParameter] mixed $password): static;
}
