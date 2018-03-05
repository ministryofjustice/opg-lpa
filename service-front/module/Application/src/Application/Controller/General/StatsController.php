<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\AuthClient\AuthClientAwareInterface;
use Application\Model\Service\AuthClient\AuthClientTrait;
use Zend\View\Model\ViewModel;

class StatsController extends AbstractBaseController implements ApiClientAwareInterface, AuthClientAwareInterface
{
    use ApiClientTrait;
    use AuthClientTrait;

    public function indexAction()
    {
        $userStats = false;

        $response = $this->authClient->httpGet('/v1/stats');

        if ($response->getStatusCode() == 200) {
            $userStats = json_decode($response->getBody(), true);
        }

        //  Get the API stats
        $stats = $this->apiClient->getApiStats();

        //  Set the auth stats in the API stats
        $stats['users'] = $userStats;

        return new ViewModel($stats);
    }
}
