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
  <div class="pagetitle">
    <h1>Bulk Tambah Akun</h1>
  </div>

  <section class="section">
    <div class="card bulk-card">
      <div class="card-body p-4">
        <form action="<?= base_url('user/bulk_tambah_akun') ?>" method="POST">
          <div class="mb-4">
            <h5 class="card-title mb-1">Tambah Stok Grok</h5>
            <div class="bulk-help">Satu akun per baris. Format: username|password|catatan</div>
          </div>

          <div class="mb-3">
            <label>Daftar Akun</label>
            <textarea name="bulk_accounts" class="form-control" placeholder="user1@gmail.com|password123|akun utama&#10;user2@gmail.com|pass456&#10;user3@gmail.com|mypass789|catatan opsional" required></textarea>
          </div>

          <div class="bulk-defaults mb-4">
            Default: <strong>Nama Akun Grok</strong>, <strong>Kategori Belum Terjual</strong>, <strong>Status Aktif</strong>, <strong>Max User 0</strong>, expired dan tanggal dikosongkan.
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('user/kelola_akun') ?>" class="btn btn-secondary">
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
