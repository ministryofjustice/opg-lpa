<?php

namespace Application\Model\Service\Stats;

use Auth\Model\Service\StatsService as AuthStatsService;
use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;

class Service
{
    /**
     * @var Collection
     */
    protected $collection = null;

    /**
     * @var AuthStatsService
     */
    protected $authStatsService = null;

    /**
     * @param Collection $collection
     * @param AuthStatsService $authStatsService
     */
    public function __construct(Collection $collection, AuthStatsService $authStatsService)
    {
        $this->collection = $collection;
        $this->authStatsService = $authStatsService;
    }

    /**
     * @param $type
     * @return array|null|object
     */
    public function fetch($type)
    {
        // Return all the cached data.// Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        // Stats can (ideally) be pulled from a secondary.
        $stats = $this->collection->findOne([], $readPreference);

        if (!isset($stats['generated']) || !is_string($stats['generated'])) {
            return [
                'generated' => false
            ];
        }

        //  Get the user stats from the auth module
        $stats['users'] = $this->authStatsService->getStats();

        // Return specific subset of stats if requested
        switch ($type) {
            case 'lpas':
                $stats = $stats['lpas'];
                break;
            case 'users':
                $stats = $stats['users'];
                break;
            case 'lpasperuser':
                $stats = $stats['lpasPerUser'];
                break;
            case 'whoareyou':
                $stats = $stats['who'];
                break;
            case 'correspondence':
                $stats = $stats['correspondence'];
                break;
            case 'preferencesinstructions':
                $stats = $stats['preferencesInstructions'];
                break;
        }

        return $stats;
    }
}
