<?php
namespace Application\Model\Library\Authentication\Identity;

/**
 * Immutable class representing the identity of a currently authenticated user.
 *
 * Class User
 * @package Application\Library\Authentication\Identity
 */
class User {

    /**
     * @var string The user's internal ID.
     */
    private $id;

    /**
     * @var string The user's authentication token.
     */
    private $token;

    /**
     * @var \DateTime The date & time the user last logged in.
     */
    private $lastLogin;

    /**
     * The user's roles.
     * 'admin' could be added to this.
     *
     * @var array
     */
    private $roles = [ 'user' ];

    //------

    public function __construct( $userId, $token, $lastLogin, $isAdmin = false ){
        $this->id = $userId;
        $this->token = $token;
        $this->lastLogin = $lastLogin;

        if( $isAdmin === true ){
            $this->setAsAdmin();
        }
    }

    //------

    public function id(){
        return $this->id;
    }

    public function token(){
        return $this->token;
    }

    public function lastLogin(){
        return $this->lastLogin;
    }

    public function roles(){
        return $this->roles;
    }

    public function isAdmin(){
        return in_array('admin', $this->roles);
    }

    //------

    /**
     * Flags this user as an admin.
     */
    private function setAsAdmin(){

        if( !in_array('admin', $this->roles) ){
            $this->roles[] = 'admin';
        }

    } // function

} // class
