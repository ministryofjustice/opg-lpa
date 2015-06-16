<?php
namespace Application\View\Helper;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Mvc\Router\Http\RouteMatch;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Donor;

class ActorsList extends AbstractAccordion
{
    /**
     * Generate a list of actors that user has already added into LPA. 
     * This is used by actor form viewscript to generate json variable that javascript 
     * can access to determin if the actor name is already used in other actors.
     * 
     * @param Lpa $lpa
     * @param $routeMatch - needed when user is editing actor details.
     * 
     * @return array|null
     */
    public function __invoke (Lpa $lpa, RouteMatch $routeMatch=null)
    {
        $actors = [];
        
        if((($routeMatch == null) || ($routeMatch->getMatchedRouteName() != 'lpa/donor/edit')) && ($lpa->document->donor instanceof Donor)) {
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
