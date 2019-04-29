<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Data-seeding migration for testing purposes
 */
final class Version20190428143677 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `user` (`id`, `name`, `email`, `password`, `api_token`, `roles`) VALUES 
(1, "batman", "batman@mail.com", "$argon2i$v=19$m=1024,t=2,p=2$UFVkNG5hYzJVdHJWTnhJbA$8a07vYEyQU0FpKjaBiD+CD8VjR7GsVTRhWv1d5zUbfE", "28260e16cc233d45c912faf44afce9a6", "[]"),
(2, "superman", "superman@mail.com", "$argon2i$v=19$m=1024,t=2,p=2$YlRPZVh3NEhYUEdqNTlCQQ$5B9RX8Eoj7HJL45jRbwu5m0W9zyh+hiLvAdoRP+EQIA", "ec30682601ec9d19fc1df5d4746ffecb", "[]"),
(3, "bananaman", "bananaman@mail.com", "$argon2i$v=19$m=1024,t=2,p=2$eUJPL21rQk13Q3BkTk5ORQ$J0G0UlLCItrGTi2NQjdHMDFCmaj8L4+e+qosW6OjzyI", "50946ef20e389a0d03674182b3cef52a", "[]"),
(4, "superadmin", "iamadmin@mail.com", "$argon2i$v=19$m=1024,t=2,p=2$RjdxL3BsS2VzN3VpUVlOTQ$0hZBYpSlL1fuc+lzvz2pW35Av38z5NzpYGKWX7d0Q9M", "945285df2f9d7975dcb92c4264860f78",  \'[\"ROLE_ADMIN\"]\')');
    }


    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('TRUNCATE TABLE `user`');
    }
}
