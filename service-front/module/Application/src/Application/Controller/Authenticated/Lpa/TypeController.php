<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Zend\View\Model\ViewModel;
use RuntimeException;

class TypeController extends AbstractLpaController
{
    public function indexAction()
    {
        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\TypeForm');

        $isChangeAllowed = true;

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $lpaId = $this->getLpa()->id;
                $lpaType = $form->getData()['type'];

                if ($lpaType != $this->getLpa()->document->type) {
                    if (!$this->getLpaApplicationService()->setType($lpaId, $lpaType)) {
                        throw new RuntimeException('API client failed to set LPA type for id: ' . $lpaId);
                    }
                }

                return $this->moveToNextRoute();
            }
        } elseif ($this->getLpa()->document instanceof Document) {
            $form->bind($this->getLpa()->document->flatten());

            if ($this->getLpa()->document->donor instanceof Donor) {
                $isChangeAllowed = false;
            }
        }

        $analyticsDimensions = [];

        if (empty($this->getLpa()->document->type)) {
            $analyticsDimensions = [
                'dimension2' => date('Y-m-d'),
                'dimension3' => 0,
            ];
        }

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $nextUrl = $this->url()->fromRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $this->getLpa()->id]);

        return new ViewModel([
            'form'                => $form,
            'cloneUrl'            => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id' => $this->getLpa()->id]),
            'nextUrl'             => $nextUrl,
            'isChangeAllowed'     => $isChangeAllowed,
            'analyticsDimensions' => $analyticsDimensions,
        ]);
    }

}

