<?php
namespace Application\Model\Service\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface as ZFAdapterInterface;

interface AdapterInterface extends ZFAdapterInterface
{

    /**
     * Set the credentials to attempt authentication with.
     *
     * @param $email
     * @param $password
     */
    public function setCredentials( $email, $password );

    /**
     * Set the email address credential to attempt authentication with.
     *
     * @param $email
     */
    public function setEmail( $email );

    /**
     * Set the password credential to attempt authentication with.
     *
     * @param $password
     */
    public function setPassword( $password );

}
