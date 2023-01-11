<?php

namespace Application\Model\Service\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface as LaminasAdapterInterface;

interface AdapterInterface extends LaminasAdapterInterface
{
    /**
     * Set the email address credential to attempt authentication with.
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(#[\SensitiveParameter] string $email);

    /**
     * Set the password credential to attempt authentication with.
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(#[\SensitiveParameter] string $password);
}
