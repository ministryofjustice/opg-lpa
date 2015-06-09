<?php
namespace Application\View\Helper;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Mvc\Router\Http\RouteMatch;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;

class ActorsList extends AbstractAccordion
{
    /**
     * @param Lpa $lpa
     * @return array|null
     */
    public function __invoke (Lpa $lpa, RouteMatch $routeMatch=null)
    {
        if($lpa === null) {
            return null;
        }
        
        $actors = [];
        
        if(($routeMatch == null) || ($routeMatch->getMatchedRouteName() != 'lpa/donor/edit')) {
            $actors[] = ['firstname'=>$lpa->document->donor->name->first, 'lastname'=>$lpa->document->donor->name->last, 'type'=>'donor'];
        }
        
        if((($routeMatch == null) || ($routeMatch->getMatchedRouteName() != 'lpa/certificate-provider/edit')) && ($lpa->document->certificateProvider instanceof CertificateProvider)) {
            $actors[] = ['firstname'=>$lpa->document->certificateProvider->name->first, 'lastname'=>$lpa->document->certificateProvider->name->last, 'type'=>'certificate provider'];
        }
        
        foreach($lpa->document->primaryAttorneys as $idx => $attorney) {
            if(($routeMatch != null) && ($routeMatch->getMatchedRouteName() == 'lpa/primary-attorney/edit') && ($routeMatch->getParam('idx')==$idx)) continue;
            
            if($attorney instanceof Human) {
                $actors[] = ['firstname'=>$attorney->name->first, 'lastname'=>$attorney->name->last, 'type'=>'attorney'];
            }
        }
        
        foreach($lpa->document->replacementAttorneys as $idx => $attorney) {
            if(($routeMatch != null) && ($routeMatch->getMatchedRouteName() == 'lpa/replacement-attorney/edit') && ($routeMatch->getParam('idx')==$idx)) continue;
            
            if($attorney instanceof Human) {
                $actors[] = ['firstname'=>$attorney->name->first, 'lastname'=>$attorney->name->last, 'type'=>'replacement attorney'];
            }
        }
        
        foreach($lpa->document->peopleToNotify as $idx => $notifiedPerson) {
            if(($routeMatch != null) && ($routeMatch->getMatchedRouteName() == 'lpa/people-to-notify/edit') && ($routeMatch->getParam('idx')==$idx)) continue;
        
            $actors[] = ['firstname'=>$notifiedPerson->name->first, 'lastname'=>$notifiedPerson->name->last, 'type'=>'people to notify'];
        }
        
        return $actors;
    
    }
}
