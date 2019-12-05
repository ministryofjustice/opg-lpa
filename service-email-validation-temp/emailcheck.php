<?php

require __DIR__ . '/vendor/autoload.php';
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;                                        
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;                                 
use Egulias\EmailValidator\Validation\RFCValidation;                                             
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\SpoofCheckValidation;
$validator = new EmailValidator();
$multipleValidations = new MultipleValidationWithAnd([                                           
    new RFCValidation(),                                                                         
    new DNSCheckValidation(),
    new NoRFCWarningsValidation(),
    new SpoofCheckValidation()
]);                                                                                              
                 
$result = $validator->isValid("opglpademo+trustcorp@gmail.com", new NoRFCWarningsValidation());
print_r($result);