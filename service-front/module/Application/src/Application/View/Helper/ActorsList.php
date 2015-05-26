<?php
namespace Application\View\Helper;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Zend\Mvc\Router\Http\RouteMatch;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;

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
            $actors[] = $lpa->document->donor;
        }
        
        if((($routeMatch == null) || ($routeMatch->getMatchedRouteName() != 'lpa/certificate-provider/edit')) && ($lpa->document->certificateProvider instanceof CertificateProvider)) {
            $actors[] = $lpa->document->certificateProvider;
        }
        
        foreach($lpa->document->primaryAttorneys as $idx => $attorney) {
            if(($routeMatch != null) && ($routeMatch->getMatchedRouteName() == 'lpa/primary-attorney/edit') && ($routeMatch->getParam('idx')==$idx)) continue;
            
            $actors[] = $attorney;
        }
        
        foreach($lpa->document->replacementAttorneys as $idx => $attorney) {
            if(($routeMatch != null) && ($routeMatch->getMatchedRouteName() == 'lpa/replacement-attorney/edit') && ($routeMatch->getParam('idx')==$idx)) continue;
        
            $actors[] = $attorney;
        }
        
        foreach($lpa->document->peopleToNotify as $idx => $notifiedPerson) {
            if(($routeMatch != null) && ($routeMatch->getMatchedRouteName() == 'lpa/people-to-notify/edit') && ($routeMatch->getParam('idx')==$idx)) continue;
        
            $actors[] = $notifiedPerson;
        }
        
        $names = [];
        foreach($actors as $actor) {
            if(($actor != null) && !($actor instanceof TrustCorporation)) {
                $names[] = $actor->name->first . ' ' . $actor->name->last;
            }
        }
        
        return $names;
    
    }
}
