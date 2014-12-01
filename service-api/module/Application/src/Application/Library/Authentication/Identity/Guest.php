<?php
namespace Application\Library\Authentication\Identity;

class Guest extends AbstractIdentity {

    public function getRoles(){
        return [ 'guest' ];
    }

} // class
