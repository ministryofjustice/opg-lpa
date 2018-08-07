<?php
namespace Application\Model\DataAccess\Repository\Auth;

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
