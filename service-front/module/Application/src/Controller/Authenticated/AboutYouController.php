<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Model\Service\Session\ContainerNamespace;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class AboutYouController extends AbstractAuthenticatedController
{
    use LoggerTrait;


    /**
     * Flag to indicate if complete user details are required when accessing this controller
     */
    protected bool $requireCompleteUserDetails = false;

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @return RedirectResponse|ViewModel
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

        $request = $this->convertRequest();

        // Get any existing data for the user
        $userDetails = $this->getUser();
        $userDetailsArr = $userDetails->flatten();

        if ($request->isPost()) {
            // Merge any existing data - this is required for the datamodel validation that will execute in the form
            $data = $request->getPost()->toArray();
            $existingData = array_intersect_key($userDetailsArr, array_flip(['id', 'createdAt', 'updatedAt']));

            // Validate the new data with the existing data that doesn't change in the form
            $form->setData(array_merge($data, $existingData));

            if ($form->isValid()) {
                $userService = $this->getUserService();
                $userService->updateAllDetails($form->getData());

                // Clear the old details out the session.
                $this->sessionUtility->unsetInMvc(ContainerNamespace::USER_DETAILS, 'user');

                // Saved successful so return to dashboard with message if required
                if (!$isNew) {
                    /**
                     * psalm doesn't understand Laminas MVC plugins
                     * @psalm-suppress UndefinedMagicMethod
                     */
                    $this->flashMessenger()->addSuccessMessage('Your details have been updated.');
                }

                return $this->redirectToRoute('user/dashboard');
            }
        } else {
            // if the user is new then ensure they are accessing the new route only
            if (!$isNew && is_null($userDetails->name)) {
                return new RedirectResponse('/user/about-you/new');
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

        $cancelUrl = '/user/dashboard';
        return new ViewModel(compact('form', 'isNew', 'cancelUrl'));
    }
}
