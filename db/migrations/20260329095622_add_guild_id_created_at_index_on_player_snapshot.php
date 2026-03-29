<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddGuildIdCreatedAtIndexOnPlayerSnapshot extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE player_snapshots
            ADD INDEX guild_id_created_at_idx (guild_id, created_at)
        ;");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE player_snapshots
            DROP INDEX guild_id_created_at_idx
        ;");
    }
}
