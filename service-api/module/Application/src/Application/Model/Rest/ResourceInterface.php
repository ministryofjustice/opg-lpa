<?php
namespace Application\Model\Rest;

use ZfcRbac\Service\AuthorizationServiceAwareInterface;

interface ResourceInterface extends AuthorizationServiceAwareInterface {

    public function getName();
    public function getIdentifier();

    public function getRouteUser();
    public function setRouteUser( $user );

} // interface
