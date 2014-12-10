<?php
namespace Application\Model\Rest;

use ZfcRbac\Service\AuthorizationServiceAwareInterface;

interface ResourceInterface extends AuthorizationServiceAwareInterface {

    public function getName();
    public function getIdentifier();

    /**
     * @return string The type of Resource. Can be 'singular' or 'collection'.
     */
    public function getType();

} // interface
