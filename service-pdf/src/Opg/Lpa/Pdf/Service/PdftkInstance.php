<?php
namespace Opg\Lpa\Pdf\Service;

use mikehaertl\pdftk\Pdf;

/**
 * A factory class for creating pdftk instance.
 * 
 * Most part of this code (from line 22) are for unit testing.
 * The setPdftkInstance() is called by unit tests in the setup() method to
 * inject a new mock object. The mocck object is then stored in the static $instance
 * property. So when pdf generator need a new instance of pdftk object, the mock object
 * is deeply cloned by serializtion followed by unserializtion, to make sure a
 * new mock object is returned.
 */
class PdftkInstance {
    
    static $instance = null;
    
    static function getInstance($path = null, $options = array())
    {
        if(static::$instance === null) {
            
            return new Pdf($path, $options);
            
        }
        else {
            
            // deep clone injected object.
            $instance = unserialize(serialize(static::$instance));
            if($path !== null) {
                $instance->addFile($path);
            }
            
            return $instance;
        }
    }
    
    /**
     * check injected object has all methods as mikehaertl\pdftk\Pdf
     * 
     * @param Object $pdftkInstance - object that has all methods of mikehaertl\pdftk\Pdf
     * 
     * @return boolean - false: not injected; true: injected successfully.
     */
    static function setPdftkInstance($pdftkInstance)
    {
        if(!is_object($pdftkInstance)) {
            return false;
        }
        
        $pdftkMethods = (new \ReflectionClass('mikehaertl\pdftk\Pdf'))->getMethods();
        
        $pdftkInstanceReflection = new \ReflectionClass($pdftkInstance);
        
        foreach($pdftkMethods as $method) {
            
            if(!$pdftkInstanceReflection->hasMethod($method->getName())) {
                return false;
            }
        }
        
        static::$instance = $pdftkInstance;
        return true;
    }
    
} // class PdftkInstance
