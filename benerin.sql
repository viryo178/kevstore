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

-- Variasi khusus akun Zoom.
ALTER TABLE `akun`
  ADD COLUMN IF NOT EXISTS `durasi_zoom` varchar(20) DEFAULT NULL AFTER `nama_akun`;

-- Akun Zoom lama berasal dari paket yang sebelumnya hanya tersedia (1 bulan).
UPDATE `akun`
SET `durasi_zoom` = '1_bulan'
WHERE UPPER(TRIM(`nama_akun`)) = 'ZOOM'
  AND (`durasi_zoom` IS NULL OR `durasi_zoom` = '');

-- Pastikan kategori Done tersedia untuk akun yang sudah terjual.
ALTER TABLE `akun`
  MODIFY COLUMN `kategori` enum('private','sharing','belum_terjual','done') DEFAULT NULL;

-- Samakan enum status aplikasi dan database. Tahap pertama tetap menerima
-- status lama agar datanya dapat dimigrasikan tanpa terpotong.
ALTER TABLE `akun`
  MODIFY COLUMN `status` enum(
    'aktif','verif','deactived','tidak_preimum','lainnya',
    'disable_x','disable_email','ban','terjual'
  ) NOT NULL DEFAULT 'aktif';

UPDATE `akun`
SET `status` = CASE
  WHEN `status` = 'disable_x' THEN 'tidak_preimum'
  WHEN `status` = 'disable_email' THEN 'lainnya'
  ELSE `status`
END
WHERE `status` IN ('disable_x', 'disable_email');

ALTER TABLE `akun`
  MODIFY COLUMN `status` enum(
    'aktif','verif','deactived','tidak_preimum','lainnya','ban','terjual'
  ) NOT NULL DEFAULT 'aktif';

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
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'akun'
      AND COLUMN_NAME = 'durasi_zoom'
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
  )
  AND EXISTS(
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'akun'
      AND COLUMN_NAME = 'status'
      AND COLUMN_TYPE LIKE '%tidak_preimum%'
      AND COLUMN_TYPE LIKE '%lainnya%'
  ),
  'DATABASE SIAP',
  'DATABASE BELUM LENGKAP'
) AS `hasil_perbaikan`;
