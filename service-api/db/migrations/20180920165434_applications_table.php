<?php


use Phinx\Migration\AbstractMigration;

class ApplicationsTable extends AbstractMigration
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

        $applications = $this->table('applications', ['id' => false, 'primary_key'=>'id']);

        $applications->addColumn('id', 'biginteger')

            ->addColumn('user', 'text', ['null' => true])

            ->addColumn('updatedAt', 'datetime', ['timezone'=>true])    // NULL is allowed
            ->addColumn('startedAt', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('createdAt', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('completedAt', 'datetime', ['null' => true, 'timezone'=>true])
            ->addColumn('lockedAt', 'datetime', ['null' => true, 'timezone'=>true])

            ->addColumn('locked', 'boolean', ['null' => true])
            ->addColumn('whoAreYouAnswered', 'boolean', ['null' => true])

            ->addColumn('seed', 'biginteger', ['null' => true])
            ->addColumn('repeatCaseNumber', 'biginteger', ['null' => true])

            ->addColumn('document', 'jsonb', ['null' => true])
            ->addColumn('payment', 'jsonb', ['null' => true])
            ->addColumn('metadata', 'jsonb', ['null' => true])

            ->addColumn('search', 'text', ['null' => true])

            ->addIndex(['user', 'updatedAt'])
            ->addIndex(['startedAt'])
            ->addIndex(['createdAt'])
            ->addIndex(['completedAt'])

            /*
             * We don't have an index on 'search'. Rows scanned in such queries are already quite small
             * as they've already been filtered by 'user'.
             */

            ->create();

    }
}
