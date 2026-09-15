<?php

use Phinx\Migration\AbstractMigration;

class ApplicationsUpdatedBy extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('applications');

        $users
            ->addColumn('updatedBy', 'text', ['null' => true])
            ->addColumn('version', 'biginteger', ['default' => 1])
            ->update();
    }
}
