<?php
namespace Application\Library\Authentication\Identity;

class Guest extends AbstractIdentity {

    public function id(){
        return null;
    }

    public function getRoles(){
        return [ 'guest' ];
    }

} // class
