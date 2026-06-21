<style>
  .bulk-card { background:#0b1f3a; border:1px solid rgba(255,255,255,.08); border-radius:18px; color:#fff; }
  .bulk-row { background:#081833; border:1px solid rgba(255,255,255,.08); border-radius:14px; padding:18px; margin-bottom:16px; }
  .bulk-row-title { color:#fff; font-weight:700; margin-bottom:14px; }
  .bulk-card label { color:#cbd5e1; font-weight:600; margin-bottom:8px; }
  .bulk-card .form-control, .bulk-card .form-select {
    background:#081223 !important; border:1px solid #1e3a5f !important; color:#fff !important; border-radius:10px;
  }
</style>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Bulk Edit Akun</h1>
  </div>

  <section class="section">
    <div class="card bulk-card">
      <div class="card-body p-4">
        <form action="<?= base_url('user/bulk_edit_akun') ?>" method="POST">
          <?php foreach ($akun as $a): ?>
            <div class="bulk-row">
              <div class="bulk-row-title"><?= htmlspecialchars($a->nama_akun ?? 'Akun', ENT_QUOTES, 'UTF-8') ?> #<?= $a->id_akun ?></div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label>Nama Akun</label>
                  <input type="text" name="akun[<?= $a->id_akun ?>][nama_akun]" class="form-control" value="<?= htmlspecialchars($a->nama_akun ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label>Kategori</label>
                  <select name="akun[<?= $a->id_akun ?>][kategori]" class="form-select">
                    <option value="private" <?= ($a->kategori ?? '') === 'private' ? 'selected' : '' ?>>Private</option>
                    <option value="sharing" <?= ($a->kategori ?? '') === 'sharing' ? 'selected' : '' ?>>Sharing</option>
                    <option value="belum_terjual" <?= ($a->kategori ?? '') === 'belum_terjual' ? 'selected' : '' ?>>Belum Terjual</option>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label>Status</label>
                  <select name="akun[<?= $a->id_akun ?>][status]" class="form-select">
                    <option value="aktif" <?= ($a->status ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="verif" <?= ($a->status ?? '') === 'verif' ? 'selected' : '' ?>>Verif</option>
                    <option value="deactived" <?= ($a->status ?? '') === 'deactived' ? 'selected' : '' ?>>Deactived</option>
                    <option value="umur" <?= ($a->status ?? '') === 'umur' ? 'selected' : '' ?>>Umur</option>
                    <option value="terjual" <?= ($a->status ?? '') === 'terjual' ? 'selected' : '' ?>>Terjual</option>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label>Username</label>
                  <input type="text" name="akun[<?= $a->id_akun ?>][username]" class="form-control" value="<?= htmlspecialchars($a->username ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label>Password</label>
                  <input type="text" name="akun[<?= $a->id_akun ?>][password]" class="form-control" value="<?= htmlspecialchars($a->password ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label>Website</label>
                  <input type="text" name="akun[<?= $a->id_akun ?>][website]" class="form-control" value="<?= htmlspecialchars($a->website ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label>Max User</label>
                  <input type="number" name="akun[<?= $a->id_akun ?>][max_user]" class="form-control" value="<?= htmlspecialchars($a->max_user ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label>Expired Password</label>
                  <input type="date" name="akun[<?= $a->id_akun ?>][expired_password]" class="form-control" value="<?= htmlspecialchars($a->expired_password ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 mb-3">
                  <label>Note</label>
                  <textarea name="akun[<?= $a->id_akun ?>][note]" rows="3" class="form-control"><?= htmlspecialchars($a->note ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('user/kelola_akun') ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-warning">Update Semua</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
