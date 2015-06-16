<?php
namespace Application\Form\Lpa;

use Zend\Form\Form;
use Zend\Form\Element\Csrf;
use Application\Form\Validator\Csrf as CsrfValidator;
use Opg\Lpa\DataModel\Validator\ValidatorResponse;
use Zend\Form\Element\Checkbox;
use Zend\Form\FormInterface;
use Zend\Form\Element\Radio;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractForm extends Form implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $inputFilter, $serviceLocator, $logger;

    /**
     * @var string The Csrf name user for this form.
     */
    private $csrfName = null;

    /**
     * @return string The CSRF name user for this form.
     */
    public function csrfName(){
        return $this->csrfName;
    }
    
    public function init()
    {
        parent::init();
        $this->setAttribute('method', 'post');

        $this->csrfName = 'secret_'.md5(get_class($this));

        $this->add( (new Csrf($this->csrfName))->setCsrfValidator(
            new CsrfValidator([
                'name' => $this->csrfName,
                'salt' => $this->getServiceLocator()->getServiceLocator()->get('Config')['csrf']['salt'],
            ])
        ));

        //---

        $filter = $this->getInputFilter();
        
        // add elements
        foreach($this->formElements as $name => $elm) {
            $params = [
                    'name' => $name,
                    'type' => $elm['type'],
            ];
            
            if(array_key_exists('options', $elm)) {
                $params['options'] = $elm['options'];
            }
            
            if(array_key_exists('attributes', $elm)) {
                $params['attributes'] = $elm['attributes'];
            }
            
            $this->add($params);
            
            // add default filters
            $filterParams = [
                    'name' => $name,
                    'required' => (array_key_exists('required', $elm)?$elm['required']:false),
                    'filters' => [
                            ['name' => 'Zend\Filter\StripTags'],
                            ['name' => 'Zend\Filter\StringTrim'],
                    ],
            ];
            
            // add additional filters if given
            if(array_key_exists('filters', $elm)) {
                $filterParams['filters'] += $elm['filters'];
            }
            
            // add validators if given
            if(array_key_exists('validators', $elm)) {
                $filterParams['validators'] = $elm['validators'];
            }
            
            $filter->add($filterParams);
            
        }
        
        $this->setInputFilter($filter);
        
        $this->prepare();
        
        //@todo: to be removed - logging form CSRF element value
        if($this->getLogger() !== null) {
            //$this->getLogger()->debug('SessionId: '.$this->get('secret')->getCsrfValidator()->getSessionName().", FormName: ". $this->getName() .', Csrf: '.$this->get('secret')->getValue());
        }
    }
    
    /**
     * (non-PHPdoc)
     * 
     * Validate form elements through model validation.
     * 
     * @see \Zend\Form\Form::isValid()
     */
    public function isValid()
    {
        $result = parent::isValid();
        
        if($result) {
            // do validation though model validators.
            $modelValidationResult = $this->validateByModel();
            
            // if Zend validation was successful, do validation through model.
            $this->isValid = $result = (bool) ($result & $modelValidationResult['isValid']);
        }
        
        // merge both zend and LPA model validation error messages.
        if (!$result) {
            $messages = $this->getInputFilter()->getMessages();
            
            // simplify zend email address validation error.
            if(isset($messages['email-address'])) {
                $messages['email-address'] = [
                        \Zend\Validator\EmailAddress::INVALID_FORMAT => "Invalid email address.",
                ];
            }
            
            // @todo: to be removed - capture CSRF error
            if(($this->getLogger() !== null) && isset($messages['secret']) && isset($messages['secret']['notSame'])) {
                $this->getLogger()->err($messages['secret']['notSame'].", and received CSRF taken is: ".$this->data['secret']);
                
                // logging session container contents
                //$csrfSsession = new \Zend\Session\Container($this->get('secret')->getCsrfValidator()->getSessionName());
                //$this->getLogger()->debug($csrfSsession->tokenList);
            } // end of to be removed.
            
            // merge Zend and model validation errors.
            if(isset($modelValidationResult) && isset($modelValidationResult['messages'])) {
                $messages = array_merge($messages, $modelValidationResult['messages']);
            }
            
            $this->setMessages($messages);
        }
        
        //@todo: to be removed - logging received CSRF
        if($this->getLogger() !== null) {
            //$this->getLogger()->debug('SessionId: '.$this->get('secret')->getCsrfValidator()->getSessionName().". CSRF token: ".$this->data['secret']);
        } // end of to be removed.
        
        return $result;
    }
    
    
    /**
     * Convert model validation response to Zend Form validation messages format.
     * 
     * @param ValidatorResponse $validationResponse: e.g. {storage: ['name.first'=>['value'=>'', 'messages'=>[0=>'cannot-be-blank'],],]}
     * @return array: e.g. ['name-first'=>'cannot-be-blank',]
     * or
     * @param ValidatorResponse $validationResponse: e.g. {storage: ['address.address2/postcode'=>['value'=>'', 'messages'=>[0=>'cannot-be-null'],],]}
     * @return array: e.g. ['address-address2'=>'linked-1-cannot-be-null','address-postcode'=>'linked-1-cannot-be-null',]
     */
    protected function modelValidationMessageConverter(ValidatorResponse $validationResponse, $context=null)
    {
        $messages = [];
        $linkIdx = 1;
        
        // loop through all form elements.
        foreach($validationResponse as $validationErrorKey => $validationErrors) {
            
            $errorKeyStubs = explode('.', $validationErrorKey);
            
            // set the first stub of a form element name
            $formElementName = $errorKeyStubs[0];
            
            // store multi fields element names which they are validated together.
            $multiFieldsNames = [];
            
            // loop through stubs.
            for($i=1; $i<count($errorKeyStubs); $i++) {
                
                // test if it's a multi-fields validation error.
                if(strstr($errorKeyStubs[$i], '/')) {
                    $linkedElements = explode('/', $errorKeyStubs[$i]);
                    $prefix = 'linked-'.$linkIdx++.'-';
                    $multiFieldsNames[$prefix] = [];
                    
                    // store multi fields element names
                    foreach($linkedElements as $name) {
                        $multiFieldsNames[$prefix][] = $formElementName.'-'.$name;
                    }
                }
                else {
                    $formElementName.='-'.$errorKeyStubs[$i];
                }
            }
            
            if(count($multiFieldsNames) > 0) {
                // store validations errors for multi fields elements.
                foreach($multiFieldsNames as $prefix=>$multiFields) {
                    foreach($multiFields as $name) {
                        $messages[$name] = $validationErrors['messages'];
                        $messages[$name][0] = $prefix.$messages[$name][0];
                    }
                }
            }
            else {
                $messages[$formElementName] = $validationErrors['messages'];
            }
        }
        
        return $messages;
    }
    
    /**
     * Convert form data to model-compatible input data format.
     * 
     * @param array $formData. e.g. ['name-title'=>'Mr','name-first'=>'John',]
     * 
     * @return array. e.g. ['name'=>['title'=>'Mr','first'=>'John',],]
     */
    protected function convertFormDataForModel($formData)
    {
        $modelData = [];
        foreach($formData as $key=>$value) {
            $names = explode('-', $key);
            $m = &$modelData;
            for($i=0; $i<count($names); $i++) {
                if(!array_key_exists($names[$i], $m)) {
                    $m[$names[$i]] = [];
                }
                $m = &$m[$names[$i]];
            }
            
            if($this->has($key) && (($this->get($key) instanceof Checkbox)||($this->get($key) instanceof Radio))) {
                // convert checkbox/radio value "" to false and "1" to true
                if(($value=="0")||($value===false)||($value=="")||($value===null)) {
                    $m = false;
                }
                elseif(($value=="1")||($value===true)) {
                    $m = true;
                }
                else {
                    $m = $value;
                }
            }
            else {
                $m = $value;
            }
        }
        
        return $modelData;
    }
    
    public function bind($modelizedDataArray, $flags = FormInterface::VALUES_NORMALIZED)
    {
        return parent::bind(new \ArrayObject($modelizedDataArray));
    }
    
    /**
     * get validated form data for creating model object.
     * 
     * @return \Application\Form\Lpa\array. e.g. ['name'=>['title'=>'Mr','first'=>'John',],]
     */
    public function getModelDataFromValidatedForm()
    {
        if($this->data != null) {
            return $this->convertFormDataForModel($this->getData());
        }
    }

    public function getLogger()
    {
        return $this->getServiceLocator()->getServiceLocator()->get('Logger');
    }
    
    abstract protected function validateByModel();
}
