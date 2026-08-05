ALTER TABLE `activity_log`
  ADD COLUMN `akun_nama_snapshot` VARCHAR(191) NULL AFTER `akun_id`,
  ADD COLUMN `akun_username_snapshot` VARCHAR(191) NULL AFTER `akun_nama_snapshot`,
  ADD COLUMN `akun_username_before` VARCHAR(191) NULL AFTER `akun_username_snapshot`,
  ADD COLUMN `akun_username_after` VARCHAR(191) NULL AFTER `akun_username_before`,
  ADD COLUMN `akun_before_snapshot` TEXT NULL AFTER `akun_username_after`,
  ADD COLUMN `akun_after_snapshot` TEXT NULL AFTER `akun_before_snapshot`;
