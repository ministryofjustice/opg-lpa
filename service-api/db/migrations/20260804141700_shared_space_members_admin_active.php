<?php

use Phinx\Migration\AbstractMigration;

class SharedSpaceMembersAdminActive extends AbstractMigration
{
    public function change()
    {
        $sharedSpaceMembers = $this->table('shared_space_members');

        $sharedSpaceMembers
            ->addColumn('isAdmin', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('isActive', 'boolean', ['null' => false, 'default' => true])
            ->update();
    }
}
