<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class LifeSustainingController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\LifeSustainingForm');
     
        $canSustainLife = $form->get('canSustainLife');
        
        $canSustainLifeValueOptions = $canSustainLife->getOptions()['value_options'];
        $canSustainLifeValueOptions[true]['label'] = 'I want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf';
        $canSustainLifeValueOptions[false]['label'] = 'I do not want to give my attorneys authority to give or refuse consent to life-sustaining treatment on my behalf';
        
        $canSustainLifeValueOptions[true] += [
            'label_attributes' => [
                'for' => 'can-sustain-life-true'
            ],
            'attributes' => [
                'id' => 'can-sustain-life-true'
            ],
        ];
        $canSustainLifeValueOptions[false] += [
            'label_attributes' => [
                'for' => 'can-sustain-life-false'
            ],
            'attributes' => [
                'id' => 'can-sustain-life-false'
            ],
        ];
        
        $canSustainLife->setOptions(['value_options'=>$canSustainLifeValueOptions]);
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                if($this->getLpa()->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $primaryAttorneyDecisions = $this->getLpa()->document->primaryAttorneyDecisions;
                }
                else {
                    $primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
                }
                
                $canSustainLife = (bool) $form->getData()['canSustainLife'];
                
                if($primaryAttorneyDecisions->canSustainLife !== $canSustainLife) {
                    $primaryAttorneyDecisions->canSustainLife = $canSustainLife;
                    
                    // persist data
                    if(!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpaId, $primaryAttorneyDecisions)) {
                        throw new \RuntimeException('API client failed to set life sustaining for id: '.$lpaId);
                    }
                }
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if($this->getLpa()->document->primaryAttorneyDecisions != null) {
                $form->bind($this->getLpa()->document->primaryAttorneyDecisions->flatten());
            }
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
