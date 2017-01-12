<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use Application\Model\FormFlowChecker;

use Opg\Lpa\DataModel\Lpa\Lpa;

class TypeController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TypeForm');

        $type = $form->get('type');

        $typeValueOptions = $type->getOptions()['value_options'];
        $typeValueOptions['property-and-financial']['label'] = 'Property and financial affairs';
        $typeValueOptions['health-and-welfare']['label'] = 'Health and welfare';

        $typeValueOptions['property-and-financial'] += [
            'label_attributes' => [
                'for' => 'property-and-financial',
            ],
            'attributes' => [
                'id' => 'property-and-financial',
            ],
        ];
        $typeValueOptions['health-and-welfare'] += [
            'label_attributes' => [
                'for' => 'health-and-welfare',
            ],
            'attributes' => [
                'id' => 'health-and-welfare',
            ],
        ];

        $type->setOptions( ['value_options' => $typeValueOptions] );

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            if($form->isValid()) {

                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

                $lpa = $this->getLpaApplicationService()->createApplication();

                if( !( $lpa instanceof Lpa ) ){

                    $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');
                    return $this->redirect()->toRoute( 'user/dashboard' );

                }

                $lpaType = $form->getData()['type'];

                // persist data
                if(!$this->getLpaApplicationService()->setType($lpa->id, $lpaType)) {
                    throw new \RuntimeException('API client failed to set LPA type for id: '.$lpa->id);
                }

                $formFlowChecker = new FormFlowChecker();
                return $this->redirect()->toRoute($formFlowChecker->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
            }
        }

        $analyticsDimensions = [
            'dimension2' => date('Y-m-d'),
            'dimension3' => 0,
        ];

        $view = new ViewModel([
            'form'=>$form,
            'isChangeAllowed' => true,
            'analyticsDimensions' => json_encode($analyticsDimensions)
        ]);

        $view->setTemplate('application/type/index');

        return $view;
    }

}
