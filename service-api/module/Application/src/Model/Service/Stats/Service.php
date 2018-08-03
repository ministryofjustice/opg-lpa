<?php

namespace Application\Model\Service\Stats;

use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;

class Service
{
    /**
     * @var ApiStatsLpasCollection
     */
    protected $apiStatsLpasCollection = null;

    /**
     * @var AuthUserCollection
     */
    protected $authUserCollection = null;

    /**
     * @param ApiStatsLpasCollection $apiStatsLpasCollection
     * @param AuthUserCollection $authUserCollection
     */
    public function __construct(ApiStatsLpasCollection $apiStatsLpasCollection, AuthUserCollection $authUserCollection)
    {
        $this->apiStatsLpasCollection = $apiStatsLpasCollection;
        $this->authUserCollection = $authUserCollection;
    }

    /**
     * @param $type
     * @return array|null|object
     */
    public function fetch($type)
    {
        $stats = $this->apiStatsLpasCollection->getStats();

        if (!isset($stats['generated']) || !is_string($stats['generated'])) {
            return [
                'generated' => false
            ];
        }

        //  Get the user stats
        $stats['users'] = [
            'total'                 => $this->authUserCollection->countAccounts(),
            'activated'             => $this->authUserCollection->countActivatedAccounts(),
            'activated-this-month'  => $this->authUserCollection->countActivatedAccounts(new \DateTime('first day of this month 00:00:00')),
            'deleted'               => $this->authUserCollection->countDeletedAccounts(),
        ];

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
