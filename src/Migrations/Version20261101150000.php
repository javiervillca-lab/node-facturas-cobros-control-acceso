<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the direct member foreign key from the access context';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acceso_miembro DROP CONSTRAINT FK_C9EE68A97952B53A');
    }

    public function down(Schema $schema): void
    {
        $miembrosHuerfanos = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM acceso_miembro acceso LEFT JOIN cliente ON cliente.id = acceso.miembro_id WHERE cliente.id IS NULL'
        );
        $this->abortIf($miembrosHuerfanos > 0, 'Recreate the missing member records before restoring the access/member foreign key.');
        $this->addSql('ALTER TABLE acceso_miembro ADD CONSTRAINT FK_C9EE68A97952B53A FOREIGN KEY (miembro_id) REFERENCES cliente (id) NOT DEFERRABLE');
    }
}
