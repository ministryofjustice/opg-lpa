<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Zend\View\Model\ViewModel;

class InstructionsController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\InstructionsAndPreferencesForm', [
            'lpa' => $lpa,
        ]);

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                $data = $form->getData();
                $lpaId = $lpa->id;

                // persist data if it has changed
                if (is_null($lpa->document->instruction) || $data['instruction'] != $lpa->document->instruction) {
                    if (!$this->getLpaApplicationService()->setInstructions($lpaId, $data['instruction'])) {
                        throw new \RuntimeException('API client failed to set LPA instructions for id: ' . $lpaId);
                    }
                }

                if (is_null($lpa->document->preference) || $data['preference'] != $lpa->document->preference) {
                    if (!$this->getLpaApplicationService()->setPreferences($lpaId, $data['preference'])) {
                        throw new \RuntimeException('API client failed to set LPA preferences for id: ' . $lpaId);
                    }
                }

                if (!isset($lpa->metadata)
                    || !isset($lpa->metadata['instruction-confirmed'])
                    || $lpa->metadata['instruction-confirmed'] !== true) {

                    $this->getServiceLocator()->get('Metadata')->setInstructionConfirmed($this->getLpa());
                }

                return $this->moveToNextRoute();
            }
        } else {
            $form->bind($lpa->document->flatten());
        }

        return new ViewModel([
            'form'    => $form,
            'isPfLpa' => $lpa->document->type == Document::LPA_TYPE_PF,
        ]);
    }
}
