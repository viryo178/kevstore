<style>
  .account-type-card { background:#0b2147; border:1px solid rgba(255,255,255,.06); border-radius:18px; }
  .account-type-card label { color:#dbe7ff; margin-bottom:6px; font-weight:600; }
  .account-type-card .form-control, .account-type-card .form-select { background:#081225; border:1px solid #16366f; color:#fff; border-radius:12px; }
  .account-type-card .form-control:focus, .account-type-card .form-select:focus { background:#081225; color:#fff; border-color:#60a5fa; box-shadow:0 0 0 3px rgba(96,165,250,.18); }
  .account-type-table { color:#dbe7ff; }
  .account-type-table > :not(caption) > * > * { background:transparent; color:inherit; border-color:rgba(148,163,184,.14); }
  .account-type-status { display:inline-flex!important; align-items:center!important; justify-content:center!important; padding:4px 10px!important; border-radius:999px!important; font-size:11px!important; font-weight:700!important; line-height:1.2!important; }
  .account-type-status.is-active { color:#4ade80!important; background:rgba(34,197,94,.1)!important; border:1px solid #22c55e!important; box-shadow:0 0 14px rgba(34,197,94,.12)!important; }
  .account-type-status.is-inactive { color:#94a3b8!important; background:rgba(148,163,184,.09)!important; border:1px solid #64748b!important; }
  .account-type-delete { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; padding:0; color:#f87171; background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.55); border-radius:9px; }
  .account-type-delete i { color:inherit!important; }
  .account-type-delete:hover { color:#fff; background:#dc3545; border-color:#dc3545; }
</style>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Tambahkan Jenis Akun</h1>
    <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url('admin') ?>">Admin</a></li><li class="breadcrumb-item active">Jenis Akun</li></ol></nav>
  </div>

  <section class="section">
    <?php if ($this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?= htmlspecialchars($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="card account-type-card">
          <div class="card-body p-4">
            <h5 class="card-title">Jenis Akun Baru</h5>
            <form action="<?= base_url('admin/jenis_akun') ?>" method="POST">
              <div class="mb-3">
                <label for="nama_akun">Nama Jenis Akun</label>
                <input class="form-control" id="nama_akun" name="nama_akun" placeholder="Contoh: Canva" required>
              </div>
              <div class="mb-3">
                <label for="slug">Slug (opsional)</label>
                <input class="form-control" id="slug" name="slug" placeholder="Otomatis: canva">
              </div>
              <div class="mb-3">
                <label for="website_resmi">Website Resmi (opsional)</label>
                <input class="form-control" id="website_resmi" name="website_resmi" type="url" placeholder="https://contoh.com">
              </div>
              <div class="mb-4">
                <label for="status">Status</label>
                <select class="form-select" id="status" name="status">
                  <option value="aktif">Aktif</option>
                  <option value="nonaktif">Nonaktif</option>
                </select>
              </div>
              <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-circle me-1"></i> Tambahkan Jenis Akun</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card account-type-card">
          <div class="card-body p-4">
            <h5 class="card-title">Daftar Jenis Akun</h5>
            <div class="table-responsive">
              <table class="table account-type-table align-middle">
                <thead><tr><th>Nama</th><th>Slug</th><th>Status</th><th>Website</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                  <?php foreach (($jenis_akun ?? []) as $type): ?>
                    <tr>
                      <td><?= htmlspecialchars($type->nama_akun, ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) $type->slug, ENT_QUOTES, 'UTF-8') ?></td>
                      <td><span class="account-type-status <?= $type->status === 'aktif' ? 'is-active' : 'is-inactive' ?>"><?= ucfirst($type->status) ?></span></td>
                      <td><?php if (!empty($type->website_resmi)): ?><a href="<?= htmlspecialchars($type->website_resmi, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Buka</a><?php else: ?>-<?php endif; ?></td>
                      <td class="text-center">
                        <form action="<?= base_url('admin/jenis_akun/hapus/' . (int) $type->id_jenis_akun) ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus jenis akun <?= htmlspecialchars(addslashes($type->nama_akun), ENT_QUOTES, 'UTF-8') ?>? Data akun tidak ikut terhapus.');">
                          <button type="submit" class="account-type-delete" title="Hapus <?= htmlspecialchars($type->nama_akun, ENT_QUOTES, 'UTF-8') ?>" aria-label="Hapus <?= htmlspecialchars($type->nama_akun, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-trash3"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
