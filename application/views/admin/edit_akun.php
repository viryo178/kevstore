<style>
  .form-card { background:#0b1f3a; border:1px solid rgba(255,255,255,.08); border-radius:18px; color:#fff; }
  .form-card label { color:#cbd5e1; font-weight:600; margin-bottom:8px; }
  .form-card .form-control, .form-card .form-select {
    background:#081223 !important; border:1px solid #1e3a5f !important; color:#fff !important; border-radius:10px;
  }
</style>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Edit Akun</h1>
  </div>

  <section class="section">
    <div class="card form-card">
      <div class="card-body p-4">
        <form action="<?= base_url('admin/edit_akun/' . $akun->id_akun) ?>" method="POST">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>Nama Akun</label>
              <input type="text" name="nama_akun" class="form-control" value="<?= htmlspecialchars($akun->nama_akun ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Kategori</label>
              <select name="kategori" class="form-select">
                <option value="private" <?= ($akun->kategori ?? '') === 'private' ? 'selected' : '' ?>>Private</option>
                <option value="sharing" <?= ($akun->kategori ?? '') === 'sharing' ? 'selected' : '' ?>>Sharing</option>
                <option value="belum_terjual" <?= ($akun->kategori ?? '') === 'belum_terjual' ? 'selected' : '' ?>>Belum Terjual</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label>Status</label>
              <select name="status" class="form-select">
                <option value="aktif" <?= ($akun->status ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="verif" <?= ($akun->status ?? '') === 'verif' ? 'selected' : '' ?>>Verif</option>
                <option value="deactived" <?= ($akun->status ?? '') === 'deactived' ? 'selected' : '' ?>>Deactived</option>
                <option value="ban" <?= ($akun->status ?? '') === 'ban' ? 'selected' : '' ?>>Ban</option>
                <option value="disable_x" <?= ($akun->status ?? '') === 'disable_x' ? 'selected' : '' ?>>Disable X</option>
                <option value="disable_email" <?= ($akun->status ?? '') === 'disable_email' ? 'selected' : '' ?>>Disable Email</option>
                <option value="terjual" <?= ($akun->status ?? '') === 'terjual' ? 'selected' : '' ?>>Terjual</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label>Username</label>
              <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($akun->username ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Password</label>
              <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($akun->password ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label>Website</label>
              <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($akun->website ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label>Max User</label>
              <input type="number" name="max_user" class="form-control" value="<?= htmlspecialchars($akun->max_user ?? '0', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label>Expired Password</label>
              <input type="date" name="expired_password" class="form-control" value="<?= htmlspecialchars($akun->expired_password ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 mb-3">
              <label>Note</label>
              <textarea name="note" rows="3" class="form-control"><?= htmlspecialchars($akun->note ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('admin/kelola_akun') ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
