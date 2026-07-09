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
    border: 1.5px solid #3b82f6 !important;
  }

  .admin-badge-success {
    color: #4ade80 !important;
    border: 1.5px solid #22c55e !important;
  }

  .admin-badge-warning {
    color: #facc15 !important;
    border: 1.5px solid #facc15 !important;
  }

  .admin-badge-danger {
    color: #f87171 !important;
    border: 1.5px solid #ef4444 !important;
  }

  .admin-badge-default {
    color: #cbd5e1 !important;
    border: 1.5px solid #475569 !important;
  }

  .activity-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 12px;
    margin: 8px 0 18px;
  }

  .activity-filter .form-control {
    min-width: 180px;
  }

  .activity-change {
    min-width: 220px;
    line-height: 1.45;
  }

  .activity-change-label {
    display: inline-block;
    min-width: 64px;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 700;
  }

  .activity-email {
    word-break: break-all;
  }

  .activity-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .btn-activity-delete {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
  }

  .btn-activity-delete:hover {
    background: #b91c1c !important;
    border-color: #b91c1c !important;
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

        <form class="activity-filter" method="get" action="<?= base_url('admin/aktivitas') ?>">
          <div>
            <label class="form-label text-white" for="tanggal_mulai">Dari tanggal</label>
            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div>
            <label class="form-label text-white" for="tanggal_selesai">Sampai tanggal</label>
            <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bi bi-funnel"></i> Terapkan
          </button>

          <a href="<?= base_url('admin/aktivitas') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
          </a>
        </form>

        <table class="table table-borderless datatable">
          <thead>
            <tr>
              <th>Akun</th>
              <th>Username</th>
              <th>Perubahan Email</th>
              <th>Action</th>
              <th>By</th>
              <th>Waktu</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($activity as $a): ?>
              <?php
                $emailBefore = $a->akun_username_before ?? '';
                $emailAfter = $a->akun_username_after ?? '';
                $isEditAction = stripos((string) $a->action, 'edit') !== false;

                if ($emailBefore === '' && $isEditAction) {
                  $emailBefore = $a->akun_username_snapshot ?? '';
                }

                if ($emailAfter === '') {
                  $emailAfter = $a->akun_username ?? '';
                }
              ?>
              <tr>
                <td><?= htmlspecialchars($a->nama_akun ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a->akun_username ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="activity-change">
                  <?php if ($isEditAction): ?>
                    <div>
                      <span class="activity-change-label">Sebelum</span>
                      <span class="activity-email"><?= htmlspecialchars($emailBefore !== '' ? $emailBefore : '-', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div>
                      <span class="activity-change-label">Sesudah</span>
                      <span class="activity-email"><?= htmlspecialchars($emailAfter !== '' ? $emailAfter : '-', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($a->action, ENT_QUOTES, 'UTF-8') ?></td>
                <?php $changedBy = $a->changed_by_name ?? $a->changed_by; ?>
                <td>
                  <span class="<?= $adminBadgeClass($changedBy) ?>">
                    <?= htmlspecialchars($changedBy, ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($a->created_at, ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <div class="activity-actions">
                    <a href="<?= base_url('admin/detail_activity/' . $a->id) ?>" class="btn btn-info btn-sm text-white">
                      <i class="bi bi-eye"></i> Detail
                    </a>

                    <a href="<?= base_url('admin/hapus_activity/' . $a->id) ?>" class="btn btn-danger btn-sm btn-activity-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus log aktivitas ini?')">
                      <i class="bi bi-trash"></i> Hapus
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>

  </section>

</main>
