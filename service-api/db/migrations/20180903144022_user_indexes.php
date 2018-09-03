<?php


use Phinx\Migration\AbstractMigration;

class UserIndexes extends AbstractMigration
{
    public function up()
    {
        $this->execute("CREATE UNIQUE INDEX authentication_token ON users(((auth_token->>'token')::TEXT))");
        $this->execute("CREATE UNIQUE INDEX authentication_password_reset ON users(((password_reset_token->>'token')::TEXT))");
    }

    public function down()
    {
        $this->execute("DROP INDEX IF EXISTS authentication_token");
        $this->execute("DROP INDEX IF EXISTS authentication_password_reset");
    }
}
