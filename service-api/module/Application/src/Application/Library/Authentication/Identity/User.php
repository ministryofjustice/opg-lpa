<?php
namespace Application\Library\Authentication\Identity;

class User extends AbstractIdentity {

    /**
     * The user's roles.
     * 'admin' could be added to this.
     *
     * @var array
     */
    protected $roles = [ 'user' ];

    protected $id;

    protected $email;

    //------

    public function __construct( $userId, $email ){
        $this->id = $userId;
        $this->email = $email;
    }

    public function id(){
        return $this->id;
    }

    public function email(){
        return $this->email;
    }

    public function getRoles(){
        return $this->roles;
    }

    /**
     * Flags this user as an admin.
     */
    public function setAsAdmin(){

        if( !in_array('admin', $this->roles) ){
            $this->roles[] = 'admin';
        }

    } // function

} // class
