<?php

use Phinx\Migration\AbstractMigration;

class SharedSpaceTables extends AbstractMigration
{
    public function change()
    {
        $sharedSpace = $this->table('shared_space', ['id' => false, 'primary_key' => 'id']);

        $sharedSpace->addColumn('id', 'string')
            ->addColumn('name', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true, 'timezone' => true])
            ->addColumn('updated', 'datetime', ['null' => true, 'timezone' => true])
            ->create();

        $sharedSpaceMembers = $this->table('shared_space_members', ['id' => true]);

        $sharedSpaceMembers->addColumn('sharedSpaceId', 'string')
            ->addColumn('userId', 'string')
            ->addColumn('created', 'datetime', ['null' => true, 'timezone' => true])
            ->addIndex(['sharedSpaceId'])
            ->addIndex(['userId'], ['unique' => true])
            ->addForeignKey('sharedSpaceId', 'shared_space', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('userId', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $applications = $this->table('applications');

        $applications->addColumn('sharedSpaceId', 'string', ['null' => true, 'after' => 'user'])
            ->addIndex(['sharedSpaceId'])
            ->addForeignKey('sharedSpaceId', 'shared_space', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
