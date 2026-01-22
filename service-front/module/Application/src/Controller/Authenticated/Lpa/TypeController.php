<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractAuthenticatedController;
use Application\Listener\LpaLoaderTrait;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class TypeController extends AbstractAuthenticatedController
{
    use LoggerTrait;
    use LpaLoaderTrait;

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\TypeForm');

        $isChangeAllowed = true;

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $lpaType = $form->getData()['type'];

                if ($lpaType != $lpa->document->type) {
                    if (!$this->getLpaApplicationService()->setType($lpa, $lpaType)) {
                        throw new RuntimeException(
                            'API client failed to set LPA type for id: ' . $lpa->id
                        );
                    }
                }

                return $this->moveToNextRoute();
            }
        } elseif ($lpa->document instanceof Document) {
            $form->bind($lpa->document->flatten());

            if ($lpa->document->donor instanceof Donor) {
                $isChangeAllowed = false;
            }
        }

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        $nextUrl = $this->url()->fromRoute(
            $this->getFlowChecker()->nextRoute($currentRouteName),
            ['lpa-id' => $lpa->id]
        );

        $cloneUrl = $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id' => $lpa->id]);

        return new ViewModel([
            'form'                => $form,
            'cloneUrl'            => $cloneUrl,
            'nextUrl'             => $nextUrl,
            'isChangeAllowed'     => $isChangeAllowed,
        ]);
    }
}
