<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractAuthenticatedController;
use Application\Listener\LpaLoaderTrait;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class WhenLpaStartsController extends AbstractAuthenticatedController
{
    use LoggerTrait;
    use LpaLoaderTrait;

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\WhenLpaStartsForm', [
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

                $whenToStart = $form->getData()['when'];

                if ($primaryAttorneyDecisions->when !== $whenToStart) {
                    $primaryAttorneyDecisions->when = $whenToStart;

                    // persist data
                    $setOk = $this->getLpaApplicationService()->setPrimaryAttorneyDecisions(
                        $lpa,
                        $primaryAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new RuntimeException('API client failed to set when LPA starts for id: ' . $lpa->id);
                    }
                }

                return $this->moveToNextRoute();
            }
        } else {
            if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $form->bind($primaryAttorneyDecisions->flatten());
            }
        }

        return new ViewModel(['form' => $form]);
    }
}
