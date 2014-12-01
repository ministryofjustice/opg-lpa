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

    protected $id = 'ad353da6b73ceee2201cee2f9936c509';

    //------

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
