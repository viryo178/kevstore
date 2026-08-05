<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_create_chat_messages extends CI_Migration {

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `chat_messages` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `sender` VARCHAR(191) NOT NULL,
          `message` TEXT NOT NULL,
          `created_at` DATETIME NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `chat_messages`;');
    }
}
