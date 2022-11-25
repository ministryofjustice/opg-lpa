<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Laminas\View\Model\ViewModel;

class InstructionsController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\InstructionsAndPreferencesForm', [
            'lpa' => $lpa,
        ]);

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $postData = $this->getData();

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                $data = $form->getData();
                $lpaId = $lpa->id;

                // persist data if it has changed

                if (
                    is_null($lpa->document->instruction) ||
                    $data['instruction'] != $lpa->document->instruction
                ) {
                    $setOk = $this->getLpaApplicationService()->setInstructions($lpa, $data['instruction']);

                    if (!$setOk) {
                        throw new \RuntimeException(
                            'API client failed to set LPA instructions for id: ' . $lpaId
                        );
                    }
                }

                if (is_null($lpa->document->preference) || $data['preference'] != $lpa->document->preference) {
                    $setOk = $this->getLpaApplicationService()->setPreferences($lpa, $data['preference']);

                    if (!$setOk) {
                        throw new \RuntimeException(
                            'API client failed to set LPA preferences for id: ' . $lpaId
                        );
                    }
                }

                if (
                    !isset($lpa->metadata)
                    || !isset($lpa->metadata['instruction-confirmed'])
                    || $lpa->metadata['instruction-confirmed'] !== true
                ) {
                    $this->getMetadata()->setInstructionConfirmed($this->getLpa());
                }

                return $this->moveToNextRoute();
            }
        } else {
            $form->bind($lpa->document->flatten());
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
