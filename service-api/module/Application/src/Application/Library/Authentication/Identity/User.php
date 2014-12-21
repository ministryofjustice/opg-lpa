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

    //protected $id = '699aaf1a14ff64239de1e0d03c2d66d4';
    protected $id;

    //------

    public function __construct( $userId ){
        $this->id = $userId;
    }

    public function id(){
        return $this->id;
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
