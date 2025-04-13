<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSlugToGuilds extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE guilds
            ADD COLUMN slug VARCHAR(255) DEFAULT NULL AFTER external_id,
            ADD UNIQUE INDEX slug_uniq (slug)
        ;");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE guilds
            DROP INDEX slug_uniq,
            DROP COLUMN slug
        ;");
    }
}
