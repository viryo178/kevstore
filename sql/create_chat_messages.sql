-- Local database for Kevstore
CREATE DATABASE IF NOT EXISTS `kevstore`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `kevstore`;

-- Create chat_messages table for Kevstore chat feature
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender` VARCHAR(191) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
