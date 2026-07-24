<?php

use Phinx\Migration\AbstractMigration;

class UsersOneLoginSub extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('one_login_sub', 'text', ['null' => true])
            ->addIndex(['one_login_sub'], [
                'unique' => true,
                'name'   => 'users_one_login_sub_unique',
            ])
            ->update();
    }
}
