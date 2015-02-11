<?php
namespace Application\Form\Lpa\Traits;

trait ActorFormModelization
{
    public function validateModel($modelClass)
    {
        $modelizedData = $this->modelization($this->data);
    
        if(array_key_exists('dob', $modelizedData) && ($modelizedData['dob']['date'] == "")) {
            $modelizedData['dob'] = null;
        }
    
        if(array_key_exists('email', $modelizedData) && ($modelizedData['email']['address'] == "")) {
            $modelizedData['email'] = null;
        }
    
        $personModel = new $modelClass($modelizedData);
    
        $validation = $personModel->validate();
    
        if(array_key_exists('dob', $modelizedData) && ($modelizedData['dob'] == null) && array_key_exists('dob', $validation)) {
            $validation['dob-date'] = $validation['dob'];
            unset($validation['dob']);
        }
    
        if(array_key_exists('email', $modelizedData) && ($modelizedData['email'] == null) && array_key_exists('email', $validation)) {
            $validation['email-address'] = $validation['email'];
            unset($validation['email']);
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
    
        return $modelizedData;
    }
}
