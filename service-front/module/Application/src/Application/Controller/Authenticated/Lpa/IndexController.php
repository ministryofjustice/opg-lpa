<?php
namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;

class IndexController extends AbstractLpaController
{
    
    public function indexAction()
    {
        $seedId = $this->getLpa()->seed;
        if($seedId) {
            $this->resetSessionCloneData($seedId);
        }
        
        $destinationRoute = $this->getFlowChecker()->backToForm('lpa/view-docs');
        $this->redirect()->toRoute($destinationRoute, ['lpa-id'=>$this->getLpa()->id]);
    }
}
