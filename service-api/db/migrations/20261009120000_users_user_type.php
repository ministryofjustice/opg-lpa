<?php

use Phinx\Migration\AbstractMigration;

class UsersUserType extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('users');

        $users
            ->addColumn('is_professional', 'boolean', ['null' => false, 'default' => false])
            ->update();
    }
}
