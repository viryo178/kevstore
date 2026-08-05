<?php
$formatValue = function ($value) {
  if ($value === null || $value === '') {
    return '-';
  }

  return (string) $value;
};
?>

<style>
  .activity-detail-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
  }

  .activity-detail-box {
    border: 1px solid rgba(148, 163, 184, 0.25);
    border-radius: 8px;
    padding: 12px;
    background: rgba(15, 23, 42, 0.3);
  }

  .activity-detail-label {
    display: block;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
  }

  .activity-detail-value {
    color: #ffffff;
    font-weight: 600;
    word-break: break-word;
  }

  .change-table td,
  .change-table th {
    vertical-align: top;
  }

  .change-value {
    max-width: 360px;
    white-space: pre-wrap;
    word-break: break-word;
  }
</style>

<main id="main" class="main">

  <div class="pagetitle">
    <h1 class="text-white">Detail Aktivitas</h1>

    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?= base_url('admin') ?>">Home</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= base_url('admin/aktivitas') ?>">Log Aktivitas</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <h5 class="card-title mb-0">Perubahan Akun</h5>

          <a href="<?= base_url('admin/aktivitas') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>

        <div class="activity-detail-meta">
          <div class="activity-detail-box">
            <span class="activity-detail-label">Akun</span>
            <span class="activity-detail-value"><?= htmlspecialchars($activity->nama_akun ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
          </div>

          <div class="activity-detail-box">
            <span class="activity-detail-label">Action</span>
            <span class="activity-detail-value"><?= htmlspecialchars($activity->action ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
          </div>

          <div class="activity-detail-box">
            <span class="activity-detail-label">By</span>
            <span class="activity-detail-value"><?= htmlspecialchars($activity->changed_by_name ?? $activity->changed_by ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
          </div>

          <div class="activity-detail-box">
            <span class="activity-detail-label">Waktu</span>
            <span class="activity-detail-value"><?= htmlspecialchars($activity->created_at ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </div>

        <?php if (!empty($changes)): ?>
          <div class="table-responsive">
            <table class="table table-borderless change-table">
              <thead>
                <tr>
                  <th>Field</th>
                  <th>Sebelumnya</th>
                  <th>Sekarang</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($changes as $change): ?>
                  <tr>
                    <td><?= htmlspecialchars($change['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="change-value"><?= htmlspecialchars($formatValue($change['before']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="change-value"><?= htmlspecialchars($formatValue($change['after']), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info mb-0">
            Detail perubahan belum tersedia untuk log ini.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

</main>
