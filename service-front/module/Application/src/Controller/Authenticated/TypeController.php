<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Laminas\View\Model\ViewModel;
use RuntimeException;
use Application\Logging\LoggerTrait;

class TypeController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    public function indexAction()
    {
        $this->getLogger()->err(sprintf(
            "{TypeController:indexAction} starting"
        ));

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\TypeForm');

        if ($this->request->isPost()) {

            $this->getLogger()->err(sprintf(
                "{TypeController:indexAction} posted, about to setData"
            ));


            $form->setData($this->request->getPost());

            $this->getLogger()->err(sprintf(
                "{TypeController:indexAction} data set, about to run isValid"
            ));

            if ($form->isValid()) {

                $this->getLogger()->err(sprintf(
                    "{TypeController:indexAction} isValid == true"
                ));

                $lpa = $this->getLpaApplicationService()->createApplication();

                if (!$lpa instanceof Lpa) {

                    $this->getLogger()->err(sprintf(
                        "{TypeController:indexAction} not an LPA, redirecting to user/dashboard"
                    ));

                    $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');

                    return $this->redirect()->toRoute('user/dashboard');
                }

                $lpaType = $form->getData()['type'];


                $this->getLogger()->err(sprintf(
                    "{TypeController:indexAction} lpaType"
                ));

                if (!$this->getLpaApplicationService()->setType($lpa, $lpaType)) {
                    throw new RuntimeException('API client failed to set LPA type for id: ' . $lpa->id);
                }

                $formFlowChecker = new FormFlowChecker();
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                $nextRoute = $formFlowChecker->nextRoute($currentRouteName);

                $this->getLogger()->err(sprintf(
                    "{TypeController:indexAction} redirecting from %s to %s", $currentRouteName, $nextRoute
                ));


                return $this->redirect()->toRoute($nextRoute, ['lpa-id' => $lpa->id], $formFlowChecker->getRouteOptions($nextRoute));
            }
        }

        $this->getLogger()->err(sprintf(
            "{TypeController:indexAction} outside of isValid, rendering"
        ));

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
