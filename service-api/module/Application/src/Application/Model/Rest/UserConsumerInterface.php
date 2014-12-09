<?php
namespace Application\Model\Rest;

use Application\Model\Rest\Users\Entity as RouteUser;

interface UserConsumerInterface {

    public function getRouteUser();
    public function setRouteUser( RouteUser $user );

} // interface
