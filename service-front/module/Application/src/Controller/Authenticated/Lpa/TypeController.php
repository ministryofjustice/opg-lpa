<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Laminas\View\Model\ViewModel;
use RuntimeException;

class TypeController extends AbstractLpaController
{
    /**
     * @return ViewModel|\Laminas\Http\Response
     */
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\TypeForm');

        $isChangeAllowed = true;

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $lpaType = $form->getData()['type'];

                if ($lpaType != $lpa->document->type) {
                    if (!$this->getLpaApplicationService()->setType($lpa, $lpaType)) {
                        throw new RuntimeException('API client failed to set LPA type for id: ' . $lpa->id);
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

        $analyticsDimensions = [];

        if (empty($lpa->document->type)) {
            $analyticsDimensions = [
                'dimension2' => date('Y-m-d'),
                'dimension3' => 0,
            ];
        }

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $nextUrl = $this->url()->fromRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);

        return new ViewModel([
            'form'                => $form,
            'cloneUrl'            => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id' => $lpa->id]),
            'nextUrl'             => $nextUrl,
            'isChangeAllowed'     => $isChangeAllowed,
            'analyticsDimensions' => $analyticsDimensions,
        ]);
    }
}
