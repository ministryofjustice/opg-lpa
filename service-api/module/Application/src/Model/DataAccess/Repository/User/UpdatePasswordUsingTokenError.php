<?php
namespace Application\Model\DataAccess\Repository\User;

class UpdatePasswordUsingTokenError
{

    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function message() : string
    {
        return $this->message;
    }

}
