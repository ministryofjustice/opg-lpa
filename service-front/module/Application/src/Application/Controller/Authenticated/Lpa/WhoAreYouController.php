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
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Zend\Form\Element;

class WhoAreYouController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        if($this->getLpa()->whoAreYouAnswered == true) {
            return new ViewModel( ['nextRoute'=>$this->url()->fromRoute( $this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id'=>$lpaId] )] );
        }
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\WhoAreYouForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));
        
        $who            = $form->get('who');
        
        $professional   = $form->get('professional');
        
        $whoOptions = [];
        
        $whoOptions['donor'] = new Element('who', [
                'label' => "The donor used this online service with little or no help",
        ]);
        $whoOptions['donor']->setAttributes([
                'type' => 'radio',
                'id' => 'who-donor',
                'value' => $who->getOptions()['value_options']['donor']['value'],
                'checked' => (($who->getValue() == 'donor')? 'checked':null),
        ]);
        
        $whoOptions['friend-or-family'] = new Element('who', [
                'label' => "A friend or family member helped the donor get online, navigate or enter information",
        ]);
        $whoOptions['friend-or-family']->setAttributes([
                'type' => 'radio',
                'id' => 'who-friend-or-family',
                'value' => $who->getOptions()['value_options']['friendOrFamily']['value'],
                'checked' => (($who->getValue() == 'friendOrFamily')? 'checked':null),
        ]);
        
        $whoOptions['professional'] = new Element('who', [
                'label' => "A paid professional made the LPA on the donor's behalf",
        ]);
        $whoOptions['professional']->setAttributes([
                'type' => 'radio',
                'id' => 'who-professional',
                'value' => $who->getOptions()['value_options']['professional']['value'],
                'checked' => (($who->getValue() == 'professional')? 'checked':null),
        ]);
        
        $whoOptions['digital-partner'] = new Element('who', [
                'label' => "OPG's Assisted Digital Service helped the donor",
        ]);
        $whoOptions['digital-partner']->setAttributes([
                'type' => 'radio',
                'id' => 'who-digital-partner',
                'value' => $who->getOptions()['value_options']['digitalPartner']['value'],
                'checked' => (($who->getValue() == 'digitalPartner')? 'checked':null),
        ]);
        
        $whoOptions['organisation'] = new Element('who', [
                'label' => "Another organisation, such as a charity, council or community group, helped the donor",
        ]);
        $whoOptions['organisation']->setAttributes([
                'type' => 'radio',
                'id' => 'who-organisation',
                'value' => $who->getOptions()['value_options']['organisation']['value'],
                'checked' => (($who->getValue() == 'organisation')? 'checked':null),
        ]);
        
        $whoOptions['notSaid'] = new Element('who', [
                'label' => "I'd prefer not to say",
        ]);
        $whoOptions['notSaid']->setAttributes([
                'type' => 'radio',
                'id' => 'who-notSaid',
                'value' => $who->getOptions()['value_options']['notSaid']['value'],
                'checked' => (($who->getValue() == 'notSaid')? 'checked':null),
        ]);
        
        $professionalOptions = [];
        $professionalOptions['solicitor'] = new Element('professional', [
                'label' => "Solicitor",
        ]);
        $professionalOptions['solicitor']->setAttributes([
                'type' => 'radio',
                'id' => 'professional-solicitor',
                'value' => $professional->getOptions()['value_options']['solicitor']['value'],
                'checked' => (($professional->getValue() == 'solicitor')? 'checked':null),
        ]);
        
        $professionalOptions['will-writer'] = new Element('professional', [
                'label' => "Will-writer",
        ]);
        $professionalOptions['will-writer']->setAttributes([
                'type' => 'radio',
                'id' => 'professional-will-writer',
                'value' => $professional->getOptions()['value_options']['will-writer']['value'],
                'checked' => (($professional->getValue() == 'will-writer')? 'checked':null),
        ]);
        
        $professionalOptions['other'] = new Element('professional', [
                'label' => "Other",
        ]);
        $professionalOptions['other']->setAttributes([
                'type' => 'radio',
                'id' => 'professional-other',
                'value' => $professional->getOptions()['value_options']['other']['value'],
                'checked' => (($professional->getValue() == 'other')? 'checked':null),
        ]);
        
        if($this->request->isPost()) {
            
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                // persist data
                
                $whoAreYou = new WhoAreYou( $form->getModelDataFromValidatedForm() );
                
                if( !$this->getLpaApplicationService()->setWhoAreYou($lpaId, $whoAreYou) ) {
                    throw new \RuntimeException('API client failed to set Who Are You for id: '.$lpaId);
                }
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        
        return new ViewModel([
                'form'=>$form,
                'whoOptions' => $whoOptions,
                'professionalOptions' => $professionalOptions,
            ]
        );
    }
}