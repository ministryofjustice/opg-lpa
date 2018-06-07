<?php

namespace Auth\Model\Service;

class StatsService extends AbstractService
{
    public function getStats()
    {
        $dataSource = $this->getAuthUserCollection();

        return [
            'total' => $dataSource->countAccounts(),
            'activated' => $dataSource->countActivatedAccounts(),
            'activated-this-month' => $dataSource->countActivatedAccounts(new \DateTime('first day of this month 00:00:00')),
            'deleted' => $dataSource->countDeletedAccounts(),
        ];
    }
}
