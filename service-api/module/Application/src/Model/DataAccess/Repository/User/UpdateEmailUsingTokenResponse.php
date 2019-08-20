<?php
namespace Application\Model\DataAccess\Repository\User;

class UpdateEmailUsingTokenResponse
{

    private $user;
    private $error;

    public function __construct($input)
    {
        if ($input instanceof UserInterface) {
            $this->user = $input;

        } else if (is_string($input)) {
            $this->error = $input;

        } else {
            throw new \UnexpectedValueException("Unexpected data type passed. UserInterface or string needed.");
        }
    }

    public function error() : bool
    {
        return !($this->user instanceof UserInterface);
    }

    public function message() : ?string
    {
        return $this->error;
    }

    public function getUser() : UserInterface
    {
        return $this->user;
    }
}
