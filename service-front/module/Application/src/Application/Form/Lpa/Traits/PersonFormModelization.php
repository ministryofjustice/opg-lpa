<?php
namespace Application\Form\Lpa\Traits;

trait PersonFormModelization
{
    public function validateModel($modelClass)
    {
        $modelizedData = $this->modelization($this->data);
    
        if($modelizedData['dob']['date'] == "") {
            $modelizedData['dob'] = null;
        }
    
        if($modelizedData['email']['address'] == "") {
            $modelizedData['email'] = null;
        }
    
        $personModel = new $modelClass($modelizedData);
    
        $validation = $personModel->validate();
    
        if(($modelizedData['dob'] == null) && array_key_exists('dob', $validation)) {
            $validation['dob-date'] = $validation['dob'];
            unset($validation['dob']);
        }
    
        if(($modelizedData['email'] == null) && array_key_exists('email', $validation)) {
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
    
        if($modelizedData['dob']['date'] == "") {
            $modelizedData['dob'] = null;
        }
    
        if($modelizedData['email']['address'] == "") {
            $modelizedData['email'] = null;
        }
    
        return $modelizedData;
    }
}
