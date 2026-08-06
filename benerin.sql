-- Perbaikan untuk database `kevsmyid_storekevs`.
-- Jalankan file ini setelah mengimpor kevsmyid_storekevs.sql.
--
-- Tabel `akun_bin` dipakai oleh application/controllers/Admin.php,
-- tetapi belum terdapat di dump database.

USE `kevsmyid_storekevs`;

SET NAMES utf8mb4;

-- 2FA hanya diisi untuk akun Gemini. Akun lain boleh NULL/kosong.
ALTER TABLE `akun`
  ADD COLUMN IF NOT EXISTS `two_fa` varchar(500) DEFAULT NULL AFTER `password`;

CREATE TABLE IF NOT EXISTS `akun_bin` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `original_id` int(11) DEFAULT NULL,
  `nama_akun` varchar(191) NOT NULL,
  `username` varchar(191) DEFAULT NULL,
  `account_data` longtext NOT NULL,
  `sold_at` datetime DEFAULT NULL,
  `binned_at` datetime NOT NULL,
  `purge_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_akun_bin_original_id` (`original_id`),
  KEY `idx_akun_bin_purge_at` (`purge_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hasil harus menampilkan: DATABASE SIAP
SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'akun'
      AND COLUMN_NAME = 'two_fa'
  )
  AND EXISTS(
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'akun_bin'
  ),
  'DATABASE SIAP',
  'DATABASE BELUM LENGKAP'
) AS `hasil_perbaikan`;
