<?php

use Phinx\Migration\AbstractMigration;

class UsersOneLoginEmail extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('users');

        $users
            ->addColumn('one_login_email', 'text', ['null' => true])
            ->update();
    }
}
