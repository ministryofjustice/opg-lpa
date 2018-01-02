<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Form\Admin\PaymentSwitch;
use Application\Form\Admin\SystemMessageForm;
use Application\Form\Admin\UserSearchForm;
use Application\Model\Service\Admin\Admin as AdminService;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractAuthenticatedController
{
    /**
     * Ensure user is allowed to access admin functions
     *
     * @param  MvcEvent $event
     * @return mixed
     */
    public function onDispatch(MvcEvent $event)
    {
        $userEmail = (string)$this->getUserDetails()->email;

        if ($userEmail != '') {
            $adminAccounts = $this->getServiceLocator()->get('config')['admin']['accounts'];

            $isAdmin = in_array($userEmail, $adminAccounts);

            if ($isAdmin) {
                return parent::onDispatch($event);
            }
        }

        return $this->redirect()->toRoute('home');
    }

    public function systemMessageAction()
    {
        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\Admin\SystemMessageForm');

        if ($this->request->isPost()) {
            $post = $this->request->getPost();

            $form->setData($post);

            if ($form->isValid()) {
                if (empty($post['message'])) {
                    $this->cache()->removeItem('system-message');
                } else {
                    $this->cache()->setItem('system-message', $post['message']);
                }

                return $this->redirect()->toRoute('home');
            }
        } else {
            $messageElement = $form->get('message');
            $currentMessage = $this->cache()->getItem('system-message');
            $messageElement->setValue($currentMessage);
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    public function paymentSwitchAction()
    {
        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\Admin\PaymentSwitch');

        $saved = false;

        if ($this->request->isPost()) {
            $post = $this->request->getPost();

            $form->setData($post);

            if ($form->isValid()) {
                $percentage = $form->getData()['percentage'];

                $this->cache()->setItem('worldpay-percentage', $percentage);

                $saved = true;
            }
        } else {
            $element = $form->get('percentage');
            $percentage = $this->cache()->getItem('worldpay-percentage');

            if (!is_numeric($percentage)) {
                // Default to 0
                $percentage = 0;
            }

            $element->setValue($percentage);
        }

        return new ViewModel([
            'form' => $form,
            'save' => $saved
        ]);
    }

    public function userSearchAction()
    {
        /** @var UserSearchForm $form */
        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\Admin\UserSearchForm');

        if ($this->request->isPost()) {
            $post = $this->request->getPost();

            $form->setData($post);

            if ($form->isValid()) {
                $email = $post['email'];

                /** @var AdminService $adminService */
                $adminService = $this->getServiceLocator()->get('AdminService');

                $result = $adminService->searchUsers($email);

                if ($result === false) {
                    // Set error message
                    $messages = array_merge($form->getMessages(), [
                        'email' => ['No user found for email address']
                    ]);
                    $form->setMessages($messages);
                } else {
                    
                }

                /*if (empty($post['message'])) {
                    $this->cache()->removeItem('system-message');
                } else {
                    $this->cache()->setItem('system-message', $post['message']);
                }

                return $this->redirect()->toRoute('home');*/
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
