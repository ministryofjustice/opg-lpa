<?php

declare(strict_types=1);

namespace App\Service;

class Paginator
{
    private int $perPage = 0;
    private int $page = 1;
    private int $total = 0;

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod - used in templates
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod - used in templates
     */
    public function getNextPage(): ?int
    {
        return $this->page * $this->perPage < $this->total ? $this->page + 1 : null;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod - used in templates
     */
    public function getPreviousPage(): ?int
    {
        return $this->page > 1 ? $this->page - 1 : null;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod - used in templates
     */
    public function getOffsetUpper(): int
    {
        return min(($this->page * $this->perPage), $this->total);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod - used in templates
     */
    public function getOffsetLower(): int
    {
        return ($this->page - 1) * $this->perPage + 1;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod - used in templates
     */
    public function getPages(): array
    {
        return range(1, (int) ceil($this->total / $this->perPage));
    }
}
