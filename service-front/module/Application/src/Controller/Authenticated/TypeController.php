<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Http\Response as HttpResponse;
use Laminas\View\Model\ViewModel;
use RuntimeException;

class TypeController extends AbstractAuthenticatedController
{
    /**
     * indexAction() is only supposed to return ViewModel
     * according to the Laminas API.
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return ViewModel|HttpResponse
     */
    public function indexAction()
    {
        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\TypeForm');

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $lpa = $this->getLpaApplicationService()->createApplication();

                if (!$lpa instanceof Lpa) {
                    /**
                     * psalm doesn't understand Laminas MVC plugins
                     * @psalm-suppress UndefinedMagicMethod
                     */
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

                return $this->redirect()->toRoute(
                    $nextRoute,
                    ['lpa-id' => $lpa->id],
                    $formFlowChecker->getRouteOptions($nextRoute)
                );
            }
        }

        $view = new ViewModel([
            'form'                => $form,
            'isChangeAllowed'     => true,
        ]);

        $view->setTemplate('application/authenticated/lpa/type/index.twig');

        return $view;
    }
}
