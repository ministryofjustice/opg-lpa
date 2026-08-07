<?php

use Phinx\Migration\AbstractMigration;

class SharedSpaceInvites extends AbstractMigration
{
    public function change()
    {
        $this->table('shared_space_invites')
            ->addColumn('sharedSpaceId', 'string')
            ->addForeignKey('sharedSpaceId', 'shared_space', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addColumn('invitedBy', 'string')
            ->addForeignKey('invitedBy', 'users', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->addColumn('firstNames', 'text')
            ->addColumn('lastName', 'text')
            ->addColumn('email', 'text')
            ->addColumn('isAdmin', 'boolean')
            ->addColumn('code', 'string')
            ->addColumn('created', 'datetime', ['timezone' => true])
            ->addColumn('expires', 'datetime', ['timezone' => true])
            ->create();
    }
}
