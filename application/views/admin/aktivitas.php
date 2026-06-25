<?php
$adminBadgeClass = function ($name) {
  $normalized = strtolower(trim((string) $name));
  $normalized = preg_replace('/\s+/', ' ', $normalized);

  if ($normalized === 'admin utama') {
    return 'admin-badge admin-badge-primary';
  }

  if (in_array($normalized, ['admin 1', 'admin1', 'admin_1'], true)) {
    return 'admin-badge admin-badge-success';
  }

  if (in_array($normalized, ['admin 2', 'admin2', 'admin_2'], true)) {
    return 'admin-badge admin-badge-warning';
  }

  if (in_array($normalized, ['admin 3', 'admin3', 'admin_3'], true)) {
    return 'admin-badge admin-badge-danger';
  }

  return 'admin-badge admin-badge-default';
};
?>

<style>
  .admin-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 84px;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
    background: rgba(15, 23, 42, 0.35) !important;
  }

  .admin-badge-primary {
    color: #60a5fa !important;
    border: 1px solid #3b82f6;
  }

  .admin-badge-success {
    color: #4ade80 !important;
    border: 1px solid #22c55e;
  }

  .admin-badge-warning {
    color: #facc15 !important;
    border: 1px solid #facc15;
  }

  .admin-badge-danger {
    color: #f87171 !important;
    border: 1px solid #ef4444;
  }

  .admin-badge-default {
    color: #cbd5e1 !important;
    border: 1px solid #475569;
  }
</style>

<main id="main" class="main">

  <div class="pagetitle">
    <h1 class="text-white">Detail Akun</h1>

    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?= base_url('admin') ?>">
            Home
          </a>
        </li>

        <li class="breadcrumb-item">
          log aktivitas
        </li>
      </ol>
    </nav>
  </div>

  <section class="section">

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Aktivitas Sistem</h5>

        <table class="table table-borderless datatable">
          <thead>
            <tr>
              <th>Akun</th>
              <th>Username</th>
              <th>Action</th>
              <th>By</th>
              <th>Waktu</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($activity as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a->nama_akun ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a->akun_username ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a->action, ENT_QUOTES, 'UTF-8') ?></td>
                <?php $changedBy = $a->changed_by_name ?? $a->changed_by; ?>
                <td>
                  <span class="<?= $adminBadgeClass($changedBy) ?>">
                    <?= htmlspecialchars($changedBy, ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($a->created_at, ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <a href="<?= base_url('admin/hapus_activity/' . $a->id) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus log aktivitas ini?')">
                    <i class="bi bi-trash"></i> Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>

  </section>

</main>
