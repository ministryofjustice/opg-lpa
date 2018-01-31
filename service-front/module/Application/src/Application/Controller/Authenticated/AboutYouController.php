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
        $form = $this->getFormElementManager()->get('Application\Form\User\AboutYou');
        $actionTarget = $this->url()->fromRoute('user/about-you', $isNew ? [
            'new' => 'new',
        ] : []);

        $form->setAttribute('action', $actionTarget);

        $request = $this->getRequest();
        $aboutYouService = $this->getAboutYouDetails();

        //  Get any existing data for the user
        $userDetails = $aboutYouService->load();
        $userDetailsArr = $userDetails->flatten();

        if ($request->isPost()) {
            //  Merge any existing data - this is required for the datamodel validation that will execute in the form
            $data = $request->getPost()->toArray();
            $existingData = array_intersect_key($userDetailsArr, array_flip(['id', 'createdAt', 'updatedAt']));

            //  Validate the new data with the existing data that doesn't change in the form
            $form->setData(array_merge($data, $existingData));

            if ($form->isValid()) {
                $aboutYouService->updateAllDetails($form);

                // Clear the old details out the session.
                // They will be reloaded the next time the the AbstractAuthenticatedController is called.
                $detailsContainer = $this->getUserDetailsSession();
                unset($detailsContainer->user);

                //  Saved successful so return to dashboard with message if required
                if (!$isNew) {
                    $this->flashMessenger()->addSuccessMessage('Your details have been updated.');
                }

                return $this->redirect()->toRoute('user/dashboard');
            }
        } else {
            //  if the user is new then ensure they are accessing the new route only
            if (!$isNew && is_null($userDetails->name)) {
                return $this->redirect()->toUrl('/user/about-you/new');
            }

            if (!is_null($userDetails->dob)) {
                $dob = $userDetails->dob->date;

                $userDetailsArr['dob-date'] = [
                    'day'   => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year'  => $dob->format('Y'),
                ];
            }

            $form->bind($userDetailsArr);
        }

        return new ViewModel([
            'form'  => $form,
            'isNew' => $isNew,
        ]);
    }
}
