<?php

use Phinx\Migration\AbstractMigration;

class UsersUserType extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('users');

        $users
            ->addColumn('user_type', 'text', ['null' => true])
            ->update();
    }
}
