<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class LifeSustainingController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\LifeSustainingForm', [
            'lpa' => $lpa,
        ]);

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            $form->setData($postData);

            if($form->isValid()) {

                $lpaId = $lpa->id;

                if($this->getLpa()->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $primaryAttorneyDecisions = $this->getLpa()->document->primaryAttorneyDecisions;
                }
                else {
                    $primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
                }

                $canSustainLife = (bool) $form->getData()['canSustainLife'];

                if($primaryAttorneyDecisions->canSustainLife !== $canSustainLife) {
                    $primaryAttorneyDecisions->canSustainLife = $canSustainLife;

                    // persist data
                    if(!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpaId, $primaryAttorneyDecisions)) {
                        throw new \RuntimeException('API client failed to set life sustaining for id: '.$lpaId);
                    }
                }

                return $this->moveToNextRoute();
            }
        }
        else {
            if($this->getLpa()->document->primaryAttorneyDecisions != null) {
                $form->bind($this->getLpa()->document->primaryAttorneyDecisions->flatten());
            }
        }

        return new ViewModel(['form'=>$form]);
    }
}
