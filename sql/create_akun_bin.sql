CREATE TABLE IF NOT EXISTS `akun_bin` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `original_id` INT NULL,
  `nama_akun` VARCHAR(191) NOT NULL,
  `username` VARCHAR(191) NULL,
  `account_data` LONGTEXT NOT NULL,
  `sold_at` DATETIME NULL,
  `binned_at` DATETIME NOT NULL,
  `purge_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_akun_bin_original_id` (`original_id`),
  KEY `idx_akun_bin_purge_at` (`purge_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
