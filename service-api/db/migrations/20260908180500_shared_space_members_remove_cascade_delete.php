<?php

use Phinx\Migration\AbstractMigration;

class SharedSpaceMembersRemoveCascadeDelete extends AbstractMigration
{
    /**
     * The shared_space_members -> shared_space foreign key was originally
     * created with ON DELETE CASCADE (see 20260722170304_shared_space_tables.php).
     * This removes that cascade so that deleting a shared_space row fails
     * with a foreign key violation if a shared_space_members row still
     * references it, rather than silently deleting membership rows - the
     * application is now responsible for explicitly deleting membership
     * rows before deleting a shared space (see
     * SharedSpaceService::deleteAccount()).
     */
    public function up(): void
    {
        $table = $this->table('shared_space_members');

        $table->dropForeignKey('sharedSpaceId')->save();

        $table
            ->addForeignKey('sharedSpaceId', 'shared_space', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->save();
    }

    public function down(): void
    {
        $table = $this->table('shared_space_members');

        $table->dropForeignKey('sharedSpaceId')->save();

        $table
            ->addForeignKey('sharedSpaceId', 'shared_space', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->save();
    }
}
