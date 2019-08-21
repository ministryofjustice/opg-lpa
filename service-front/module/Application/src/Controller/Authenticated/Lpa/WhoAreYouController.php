<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Zend\Form\Element;
use Zend\View\Model\ViewModel;

class WhoAreYouController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        if ($lpa->whoAreYouAnswered == true) {
            $nextUrl = $this->url()->fromRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);

            return new ViewModel(['nextUrl' => $nextUrl]);
        }

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\WhoAreYouForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpa->id]));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                // persist data
                $whoAreYou = new WhoAreYou($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setWhoAreYou($lpa, $whoAreYou)) {
                    throw new \RuntimeException('API client failed to set Who Are You for id: ' . $lpa->id);
                }

                return $this->moveToNextRoute();
            }
        }

        $who            = $form->get('who');

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
                'label' => "A friend or family member (who may also be the attorney) helped the donor use this online service",
        ]);
        $whoOptions['friend-or-family']->setAttributes([
                'type' => 'radio',
                'id' => 'who-friend-or-family',
                'value' => $who->getOptions()['value_options']['friendOrFamily']['value'],
                'checked' => (($who->getValue() == 'friendOrFamily')? 'checked':null),
        ]);

        $whoOptions['finance-professional'] = new Element('who', [
                'label' => "A paid finance professional made the LPA on the donor's behalf",
        ]);
        $whoOptions['finance-professional']->setAttributes([
                'type' => 'radio',
                'id' => 'who-finance-professional',
                'value' => $who->getOptions()['value_options']['financeProfessional']['value'],
                'checked' => (($who->getValue() == 'financeProfessional')? 'checked':null),
        ]);

        $whoOptions['legal-professional'] = new Element('who', [
                'label' => "A paid legal professional made the LPA on the donor's behalf",
        ]);
        $whoOptions['legal-professional']->setAttributes([
                'type' => 'radio',
                'id' => 'who-legal-professional',
                'value' => $who->getOptions()['value_options']['legalProfessional']['value'],
                'checked' => (($who->getValue() == 'legalProfessional')? 'checked':null),
        ]);

        $whoOptions['estate-planning-professional'] = new Element('who', [
                'label' => "A paid estate planning professional made the LPA on the donor's behalf",
        ]);
        $whoOptions['estate-planning-professional']->setAttributes([
                'type' => 'radio',
                'id' => 'who-estate-planning-professional',
                'value' => $who->getOptions()['value_options']['estatePlanningProfessional']['value'],
                'checked' => (($who->getValue() == 'estatePlanningProfessional')? 'checked':null),
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

        $whoOptions['charity'] = new Element('who', [
            'label' => "A charity made the LPA on the donor's behalf",
        ]);
        $whoOptions['charity']->setAttributes([
            'type' => 'radio',
            'id' => 'who-charity',
            'value' => $who->getOptions()['value_options']['charity']['value'],
            'checked' => (($who->getValue() == 'charity')? 'checked':null),
        ]);

        $whoOptions['organisation'] = new Element('who', [
                'label' => "Another organisation, such as a council or community group, helped the donor",
        ]);
        $whoOptions['organisation']->setAttributes([
                'type' => 'radio',
                'id' => 'who-organisation',
                'value' => $who->getOptions()['value_options']['organisation']['value'],
                'checked' => (($who->getValue() == 'organisation')? 'checked':null),
        ]);

        $whoOptions['other'] = new Element('who', [
                'label' => "Other",
        ]);
        $whoOptions['other']->setAttributes([
                'type' => 'radio',
                'id' => 'who-other',
                'value' => $who->getOptions()['value_options']['other']['value'],
                'checked' => (($who->getValue() == 'other')? 'checked':null),
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

        return new ViewModel([
            'form'                => $form,
            'whoOptions'          => $whoOptions,
        ]);
    }
}
