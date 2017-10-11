<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Zend\View\Model\ViewModel;

class AboutYouController extends AbstractAuthenticatedController
{
    /**
     * Allow access to this controller before About You details are set.
     *
     * @var bool
     */
    protected $excludeFromAboutYouCheck = true;

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function indexAction()
    {
        $isNew = !is_null($this->params()->fromRoute('new', null));

        //  Set up the about you form
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\User\AboutYou');
        $actionTarget = $this->url()->fromRoute('user/about-you', $isNew ? [
            'new' => 'new',
        ] : []);

        $form->setAttribute('action', $actionTarget);

        $request = $this->getRequest();
        $aboutYouService = $this->getServiceLocator()->get('AboutYouDetails');

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $aboutYouService->updateAllDetails($form);

                // Clear the old details out the session.
                // They will be reloaded the next time the the AbstractAuthenticatedController is called.
                $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
                unset($detailsContainer->user);

                //  Saved successful so return to dashboard with message if required
                if (!$isNew) {
                    $this->flashMessenger()->addSuccessMessage('Your details have been updated.');
                }

                return $this->redirect()->toRoute('user/dashboard');
            }
        } else {
            //  Get any existing data for the user
            $userDetails = $aboutYouService->load();

            //  if the user is new then ensure they are accessing the new route only
            if (!$isNew && is_null($userDetails->name)) {
                return $this->redirect()->toUrl('/user/about-you/new');
            }

            $form->setData($userDetails->flatten());
        }

        return new ViewModel([
            'form'  => $form,
            'isNew' => $isNew,
        ]);
    }
}
