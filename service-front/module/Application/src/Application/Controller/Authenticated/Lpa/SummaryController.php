<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class SummaryController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $fromPage = $this->params()->fromRoute('from-page');

        /*
        switch ($fromPage) {
            case 'instructions':
                $returnRoute = 'lpa/instructions';
                break;
            default:
                throw new \Exception('Invalid return route provided for summary page');
        }
        */

        $returnRoute = 'lpa/instructions';

        $viewParams = [
            'returnRoute' => $returnRoute,
        ];

        
        return new ViewModel($viewParams);
    }
}
