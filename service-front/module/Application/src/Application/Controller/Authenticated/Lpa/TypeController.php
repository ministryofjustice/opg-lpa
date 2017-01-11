<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;

class TypeController extends AbstractLpaController
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

        $isChangeAllowed = true;

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            if($form->isValid()) {

                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

                $lpaId = $this->getLpa()->id;

                $lpaType = $form->getData()['type'];

                if($lpaType != $this->getLpa()->document->type) {
                    // persist data
                    if(!$this->getLpaApplicationService()->setType($lpaId, $lpaType)) {
                        throw new \RuntimeException('API client failed to set LPA type for id: '.$lpaId);
                    }
                }

                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if($this->getLpa()->document instanceof Document) {
                $form->bind($this->getLpa()->document->flatten());

                if($this->getLpa()->document->donor instanceof Donor) {
                    $isChangeAllowed = false;
                }
            }
        }

        if (empty($this->getLpa()->document->type)) {
            $analyticsDimensions = [
                'dimension2' => date('Y-m-d'),
                'dimension3' => 0,
            ];

            $this->layout()->setVariable('analyticsDimensions', json_encode($analyticsDimensions));

        }
        return new ViewModel([
            'form'=>$form,
            'cloneUrl'=>$this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id'=>$this->getLpa()->id]),
            'nextUrl'=>$this->url()->fromRoute('lpa/donor', ['lpa-id'=>$this->getLpa()->id]),
            'isChangeAllowed' => $isChangeAllowed,
        ]);
    }

}
