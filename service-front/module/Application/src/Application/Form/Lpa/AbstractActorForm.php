<?php
namespace Application\Form\Lpa;

abstract class AbstractActorForm extends AbstractForm
{
    /**
     * @var Opg\Lpa\DataModel\AbstractData $actor
     */
    protected $actor;
    
    public function validateByModel()
    {
        $modelizedData = $this->formDataModelization($this->data);
        
        if(array_key_exists('dob', $modelizedData) && ($modelizedData['dob']['date'] == "")) {
            $modelizedData['dob'] = null;
        }
    
        if(array_key_exists('email', $modelizedData) && ($modelizedData['email']['address'] == "")) {
            $modelizedData['email'] = null;
        }
        
        if(array_key_exists('phone', $modelizedData) && ($modelizedData['phone']['number'] == "")) {
            $modelizedData['phone'] = null;
        }
        
        $this->actor->populate($modelizedData);
        $validation = $this->actor->validate();
    
        if(array_key_exists('dob', $modelizedData) && ($modelizedData['dob'] == null) && array_key_exists('dob', $validation)) {
            $validation['dob-date'] = $validation['dob'];
            unset($validation['dob']);
        }
    
        if(array_key_exists('email', $modelizedData) && ($modelizedData['email'] == null) && array_key_exists('email', $validation)) {
            $validation['email-address'] = $validation['email'];
            unset($validation['email']);
        }
    
        if(array_key_exists('phone', $modelizedData) && ($modelizedData['phone'] == null) && array_key_exists('phone', $validation)) {
            $validation['phone-number'] = $validation['phone'];
            unset($validation['phone']);
        }
        
        if(count($validation) == 0) {
            return ['isValid'=>true, 'messages' => []];
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => $this->modelValidationMessageConverter($validation),
            ];
        }
    }
    
    public function getModelizedData()
    {
        $modelizedData = parent::getModelizedData();
    
        if(array_key_exists('dob', $modelizedData) && ($modelizedData['dob']['date'] == "")) {
            $modelizedData['dob'] = null;
        }
    
        if(array_key_exists('email', $modelizedData) && ($modelizedData['email']['address'] == "")) {
            $modelizedData['email'] = null;
        }
        
        if(array_key_exists('phone', $modelizedData) && ($modelizedData['phone']['number'] == "")) {
            $modelizedData['phone'] = null;
        }
        
        return $modelizedData;
    }
}
