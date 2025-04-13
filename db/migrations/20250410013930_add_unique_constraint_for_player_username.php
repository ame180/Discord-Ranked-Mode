<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueConstraintForPlayerUsername extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE players
            ADD UNIQUE INDEX username_uniq (username),
            DROP INDEX external_id_idx
        ;");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE players
            DROP INDEX username_uniq,
            ADD INDEX external_id_idx (external_id)
        ;");
    }
}
