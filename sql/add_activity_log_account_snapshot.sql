ALTER TABLE `activity_log`
  ADD COLUMN `akun_nama_snapshot` VARCHAR(191) NULL AFTER `akun_id`,
  ADD COLUMN `akun_username_snapshot` VARCHAR(191) NULL AFTER `akun_nama_snapshot`;
