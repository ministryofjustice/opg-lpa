<?php
namespace Application\Traits;

trait LogTrait
{
    protected function log() {
        return $this->getServiceLocator()->get('Logger');
    }
    
    protected function info($message, $extra = []) {
        $this->log()->info($message, $extra);
    }
    
    protected function err($message, $extra = []) {
        $this->log()->err($message, $extra);
    }
}
