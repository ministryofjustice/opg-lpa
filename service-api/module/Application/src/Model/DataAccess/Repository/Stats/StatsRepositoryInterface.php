<?php

namespace Application\Model\DataAccess\Repository\Stats;

interface StatsRepositoryInterface
{
    /**
     * Insert a new set of stats into the cache.
     *
     * @param array $stats
     */
    public function insert(array $stats): void;

    /**
     * Returns the current set of cached stats.
     *
     * @return array|null
     */
    public function getStats(): ?array;

    /**
     * Delete all previously cached stats.
     */
    public function delete(): void;
}
