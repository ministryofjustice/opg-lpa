<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Laminas\View\Model\ViewModel;
use RuntimeException;

class TypeController extends AbstractAuthenticatedController
{
    /**
     * @return ViewModel|\Laminas\Http\Response
     */
    public function indexAction()
    {
        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\TypeForm');

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $lpa = $this->getLpaApplicationService()->createApplication();

                if (!$lpa instanceof Lpa) {
                    $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');

                    return $this->redirect()->toRoute('user/dashboard');
                }

                $lpaType = $form->getData()['type'];

                if (!$this->getLpaApplicationService()->setType($lpa, $lpaType)) {
                    throw new RuntimeException('API client failed to set LPA type for id: ' . $lpa->id);
                }

                $formFlowChecker = new FormFlowChecker();
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                $nextRoute = $formFlowChecker->nextRoute($currentRouteName);

                return $this->redirect()->toRoute($nextRoute, ['lpa-id' => $lpa->id], $formFlowChecker->getRouteOptions($nextRoute));
            }
        }

        $analyticsDimensions = [
            'dimension2' => date('Y-m-d'),
            'dimension3' => 0,
        ];

        $view = new ViewModel([
            'form'                => $form,
            'isChangeAllowed'     => true,
            'analyticsDimensions' => $analyticsDimensions,
        ]);

        $view->setTemplate('application/authenticated/lpa/type/index.twig');

        return $view;
    }
}
