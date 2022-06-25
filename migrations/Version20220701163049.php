<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220701163049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет конфигурацию для отправки email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO config (id, value) VALUES (:id1, :value1), (:id2, :value2), (:id3, :value3), (:id4, :value4), (:id5, :value5)', [
            'id1' => 'email.from',
            'value1' => 'email@example.com',
            'id2' => 'email.confirm.subject',
            'value2' => 'Confirmation',
            'id3' => 'email.forgot-password.subject',
            'value3' => 'Forgot password',
            'id4' => 'email.confirm.url',
            'value4' => 'https://localhost/url?token={{token}}',
            'id5' => 'email.forgot-password.url',
            'value5' => 'https://localhost/url?token={{token}}',
        ]);
    }

    public function down(Schema $schema): void
    {
    }
}
