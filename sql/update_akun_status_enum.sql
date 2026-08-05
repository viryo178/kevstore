-- Jalankan sekali di database KevStore.
-- UI menampilkan "Disable X" dan "Disable Email", tetapi nilai database-nya
-- disimpan sebagai disable_x dan disable_email.

UPDATE akun
SET status = 'deactived'
WHERE status = 'umur';

ALTER TABLE akun
MODIFY status ENUM(
  'aktif',
  'verif',
  'deactived',
  'disable_x',
  'disable_email',
  'ban',
  'terjual'
) NOT NULL DEFAULT 'aktif';
