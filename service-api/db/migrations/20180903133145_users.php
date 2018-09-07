<?php


use Phinx\Migration\AbstractMigration;

class Users extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $users = $this->table('users', ['id' => false, 'primary_key'=>'id']);

        $users->addColumn('id', 'string')
            ->addColumn('identity', 'text', ['null' => true])
            ->addColumn('password_hash', 'text', ['null' => true])
            ->addColumn('activation_token', 'text', ['null' => true])

            ->addColumn('active', 'boolean', ['null' => true])
            ->addColumn('failed_login_attempts', 'integer', ['null' => true])

            ->addColumn('created', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('updated', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('activated', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('last_login', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('last_failed_login', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('deleted', 'datetime', ['null' => true, 'timezone'=>true])

            ->addColumn('inactivity_flags', 'jsonb', ['null' => true])
            ->addColumn('auth_token', 'jsonb', ['null' => true])
            ->addColumn('email_update_request', 'jsonb', ['null' => true])
            ->addColumn('password_reset_token', 'jsonb', ['null' => true])
            ->addColumn('profile', 'jsonb', ['null' => true])

            // Simple (non-jsonb) indexes.
            ->addIndex(['identity'], ['unique' => true])
            ->addIndex(['activation_token'], ['unique' => true])
            ->addIndex(['activated'])
            ->addIndex(['last_login'])
            ->addIndex(['active','last_login'])
            ->create();

    }

}
