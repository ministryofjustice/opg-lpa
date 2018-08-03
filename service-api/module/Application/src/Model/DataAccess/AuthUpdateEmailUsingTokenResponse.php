<?php
namespace Application\Model\DataAccess;

class AuthUpdateEmailUsingTokenResponse {

    private $user;
    private $error;

    public function __construct($input)
    {
        if ($input instanceof AuthUserInterface) {
            $this->user = $input;

        } else if (is_string($input)) {
            $this->error = $input;

        } else {
            throw new \UnexpectedValueException("Unexpected data type passed. AuthUserInterface or string needed.");
        }
    }

    public function error() : bool
    {
        return !($this->user instanceof AuthUserInterface);
    }

    public function message() : ?string
    {
        return $this->error;
    }

    public function getUser() : AuthUserInterface
    {
        return $this->user;
    }
}
