<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class InstructionsController extends AbstractLpaController
{

    protected $contentHeader = 'creation-partial.phtml';

    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\InstructionsAndPreferencesForm');

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            if($form->isValid()) {

                // persist data
                if(!$this->getLpaApplicationService()->setInstructions($lpaId, $form->getData()['instruction'])) {
                    throw new \RuntimeException('API client failed to set LPA instructions for id: '.$lpaId);
                }

                if(!$this->getLpaApplicationService()->setPreferences($lpaId, $form->getData()['preference'])) {
                    throw new \RuntimeException('API client failed to set LPA preferences for id: '.$lpaId);
                }

                $this->getLpaApplicationService()->setMetaData( $lpaId, [ 'instruction-confirmed'=>true ] );

                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            $form->bind($this->getLpa()->document->flatten());
        }

        return new ViewModel(['form'=>$form]);
    }
}
