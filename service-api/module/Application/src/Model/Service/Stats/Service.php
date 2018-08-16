<?php

namespace Application\Model\Service\Stats;

use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollectionTrait;
use Application\Model\Service\AbstractService;

class Service extends AbstractService
{
    use ApiStatsLpasCollectionTrait;
    use UserRepositoryTrait;

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
            'total'                 => $this->getUserRepository()->countAccounts(),
            'activated'             => $this->getUserRepository()->countActivatedAccounts(),
            'activated-this-month'  => $this->getUserRepository()->countActivatedAccounts(new \DateTime('first day of this month 00:00:00')),
            'deleted'               => $this->getUserRepository()->countDeletedAccounts(),
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
