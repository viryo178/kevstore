-- Perbaikan untuk database `kevsmyid_storekevs`.
-- Jalankan file ini setelah mengimpor kevsmyid_storekevs.sql.
--
-- Tabel `akun_bin` dipakai oleh application/controllers/Admin.php,
-- tetapi belum terdapat di dump database.

USE `kevsmyid_storekevs`;

SET NAMES utf8mb4;

-- 2FA diisi untuk akun Gemini/Adobe. Akun lain boleh NULL/kosong.
ALTER TABLE `akun`
  ADD COLUMN IF NOT EXISTS `two_fa` varchar(500) DEFAULT NULL AFTER `password`;

-- Pastikan kategori Done tersedia untuk akun yang sudah terjual.
ALTER TABLE `akun`
  MODIFY COLUMN `kategori` enum('private','sharing','belum_terjual','done') DEFAULT NULL;

-- Isi master jenis akun. Data yang sudah ada tidak akan diduplikasi.
INSERT INTO `jenis_akun` (`nama_akun`, `slug`, `website_resmi`, `status`)
VALUES
  ('SPOTIFY', 'spotify', NULL, 'aktif'),
  ('LEONARDO', 'leonardo', NULL, 'aktif'),
  ('GEMINI', 'gemini', NULL, 'aktif'),
  ('ZOOM', 'zoom', NULL, 'aktif'),
  ('ADOBE', 'adobe', NULL, 'aktif')
ON DUPLICATE KEY UPDATE
  `nama_akun` = VALUES(`nama_akun`),
  `status` = 'aktif';

-- Hubungkan akun lama dengan master jenis akun berdasarkan namanya.
UPDATE `akun` AS a
INNER JOIN `jenis_akun` AS j
  ON UPPER(TRIM(a.`nama_akun`)) = UPPER(TRIM(j.`nama_akun`))
SET a.`jenis_akun_id` = j.`id_jenis_akun`
WHERE a.`jenis_akun_id` IS NULL;

-- Normalisasi data Gemini lama: satu akun hanya memiliki satu penjualan.
UPDATE `akun`
SET `max_user` = 1,
    `status` = 'terjual',
    `kategori` = 'done'
WHERE UPPER(TRIM(`nama_akun`)) = 'GEMINI'
  AND `max_user` >= 1;

-- Samakan akun terjual lama dengan kategori Done.
UPDATE `akun`
SET `kategori` = 'done'
WHERE `status` = 'terjual';

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
  )
  AND EXISTS(
    SELECT 1
    FROM `jenis_akun`
    WHERE UPPER(TRIM(`nama_akun`)) = 'GEMINI'
  )
  AND EXISTS(
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'akun'
      AND COLUMN_NAME = 'kategori'
      AND COLUMN_TYPE LIKE '%done%'
  ),
  'DATABASE SIAP',
  'DATABASE BELUM LENGKAP'
) AS `hasil_perbaikan`;
