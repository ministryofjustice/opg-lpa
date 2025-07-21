<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class LifeSustainingController extends AbstractLpaController
{
    use LoggerTrait;

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\LifeSustainingForm', [
                         'lpa' => $lpa,
                     ]);

        $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();

            $form->setData($postData);

            if ($form->isValid()) {
                if (!$primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
                    $lpa->document->primaryAttorneyDecisions = $primaryAttorneyDecisions;
                }

                $canSustainLife = (bool) $form->getData()['canSustainLife'];

                if ($primaryAttorneyDecisions->canSustainLife !== $canSustainLife) {
                    $primaryAttorneyDecisions->canSustainLife = $canSustainLife;

                    // persist data
                    $setOk = $this->getLpaApplicationService()->setPrimaryAttorneyDecisions(
                        $lpa,
                        $primaryAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set life sustaining for id: ' . $lpa->id
                        );
                    }
                }

                return $this->moveToNextRoute();
            }
        } else {
            if ($lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $form->bind($lpa->document->primaryAttorneyDecisions->flatten());
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
