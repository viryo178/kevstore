<style>
  .bulk-card {
    background: #0b2147;
    border: 1px solid rgba(255, 255, 255, .06);
    border-radius: 18px;
  }

  .bulk-card label {
    color: #dbe7ff;
    margin-bottom: 6px;
    font-weight: 600;
  }

  .bulk-card .form-control {
    background: #081225 !important;
    border: 1px solid #16366f !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    border-radius: 12px;
  }

  .bulk-card textarea.form-control {
    min-height: 240px;
    resize: vertical;
    caret-color: #ffffff;
    font-weight: 500;
    line-height: 1.6;
  }

  .bulk-card .form-control:focus {
    background: #081225 !important;
    border-color: #60a5fa !important;
    box-shadow: 0 0 0 3px rgba(96, 165, 250, .18) !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
  }

  .bulk-card .form-control::placeholder {
    color: #9cc3ff !important;
    -webkit-text-fill-color: #9cc3ff !important;
    opacity: 1;
  }

  .bulk-help {
    color: #8fb3e8;
    font-size: 13px;
  }

  .bulk-defaults {
    background: rgba(96, 165, 250, .12);
    border: 1px solid rgba(96, 165, 250, .18);
    border-radius: 12px;
    color: #a9c7f5;
    font-size: 13px;
    padding: 10px 12px;
  }

  .bulk-defaults strong {
    color: #dbe7ff;
  }
</style>

<main id="main" class="main">
  <?php
    $bulk_product = in_array(($bulk_product ?? 'GROK'), ['SPOTIFY', 'LEONARDO', 'GROK'], true)
      ? ($bulk_product ?? 'GROK')
      : 'GROK';
    $bulk_max_user = in_array($bulk_product, ['SPOTIFY', 'LEONARDO'], true) ? 1 : 0;
  ?>
  <div class="pagetitle">
    <h1>Bulk Tambah Akun</h1>
  </div>

  <section class="section">
    <div class="card bulk-card">
      <div class="card-body p-4">
        <form action="<?= base_url('user/bulk_tambah_akun') ?>" method="POST">
          <input type="hidden" name="product" value="<?= htmlspecialchars($bulk_product, ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-4">
            <h5 class="card-title mb-1">Tambah Stok <?= htmlspecialchars($bulk_product, ENT_QUOTES, 'UTF-8') ?></h5>
            <div class="bulk-help"><?= $bulk_product === 'SPOTIFY'
              ? 'Format Spotify: username|password|catatan atau format Email dan Password.'
              : ($bulk_product === 'LEONARDO'
                ? 'Format Leonardo: Akun 1, username/email, lalu pemisah. Hanya username yang disimpan.'
                : 'Satu akun per baris. Format: username|password|catatan') ?></div>
          </div>

          <div class="mb-3">
            <label>Daftar Akun</label>
            <textarea name="bulk_accounts" class="form-control" placeholder="<?= $bulk_product === 'SPOTIFY'
              ? 'username1|password1&#10;&#10;atau&#10;&#10;Email : user@outlook.com&#10;Password : Premium123@'
              : ($bulk_product === 'LEONARDO'
                ? 'Akun 1&#10;user1@hotmail.com&#10;&#10;==================&#10;&#10;Akun 2&#10;user2@hotmail.com'
                : 'user1@gmail.com|password123|akun utama&#10;user2@gmail.com|pass456&#10;user3@gmail.com|mypass789|catatan opsional') ?>" required></textarea>
          </div>

          <div class="bulk-defaults mb-4">
            Default: <strong>Nama Akun <?= htmlspecialchars($bulk_product, ENT_QUOTES, 'UTF-8') ?></strong>, <strong>Kategori Belum Terjual</strong>, <strong>Status Aktif</strong>, <strong>Max User <?= $bulk_max_user ?></strong>, expired dan tanggal dikosongkan.
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('user/kelola_akun?search_akun=' . rawurlencode($bulk_product) . '&product=' . rawurlencode($bulk_product)) ?>" class="btn btn-secondary">
              Batal
            </a>

            <button type="submit" class="btn btn-primary">
              Simpan Semua
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
