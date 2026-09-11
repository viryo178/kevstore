<?php
$formatValue = function ($value) {
  if ($value === null || $value === '') {
    return '-';
  }

  return (string) $value;
};

$is_hapus = !empty($is_hapus);
$is_edit = !empty($is_edit);
$before_data = !empty($before_data) ? $before_data : [];
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

  /* Detail akun style for hapus view */
  .dashboard-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
  }

  .sales-card {
    background: rgba(59, 130, 246, .15);
    color: #60a5fa;
    box-shadow: 0 0 25px rgba(59, 130, 246, .25);
  }

  .revenue-card {
    background: rgba(34, 197, 94, .15);
    color: #4ade80;
    box-shadow: 0 0 25px rgba(34, 197, 94, .25);
  }

  .danger-card {
    background: rgba(239, 68, 68, .15);
    color: #f87171;
    box-shadow: 0 0 25px rgba(239, 68, 68, .25);
  }

  .detail-label {
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 10px;
    display: block;
    font-weight: 500;
  }

  .detail-box {
    background: #081223;
    border: 1px solid rgba(255, 255, 255, .05);
    border-radius: 14px;
    padding: 14px 16px;
    color: #ffffff;
    font-weight: 500;
    min-height: 52px;
    display: flex;
    align-items: center;
    word-break: break-word;
  }

  .note-box {
    align-items: flex-start;
    line-height: 1.8;
    min-height: 100px;
  }

  .btn-glow {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    border: none;
    border-radius: 14px;
    padding: 10px 18px;
    box-shadow: 0 0 25px rgba(59, 130, 246, .35);
    font-weight: 600;
  }

  /* Edit comparison styles */
  .edit-change-row {
    border: 1px solid rgba(148, 163, 184, 0.1);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 16px;
    background: rgba(15, 23, 42, 0.3);
  }

  .edit-change-header {
    background: rgba(59, 130, 246, .1);
    padding: 10px 16px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  }

  .edit-change-header span {
    color: #60a5fa;
    font-weight: 700;
    font-size: 13px;
  }

  .edit-change-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
  }

  .edit-change-col {
    padding: 14px 16px;
  }

  .edit-change-col:first-child {
    border-right: 1px solid rgba(148, 163, 184, 0.1);
  }

  .edit-col-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
  }

  .edit-col-label.before {
    color: #f87171;
  }

  .edit-col-label.after {
    color: #4ade80;
  }

  .edit-col-value {
    color: #ffffff;
    font-weight: 500;
    word-break: break-word;
    white-space: pre-wrap;
  }

  .deleted-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(239, 68, 68, .15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, .25);
    border-radius: 8px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 700;
  }

  /* Changed vs unchanged row styles */
  .edit-changed {
    border-color: rgba(251, 191, 36, 0.3) !important;
  }

  .edit-unchanged {
    opacity: 0.65;
  }

  .edit-header-changed {
    background: rgba(251, 191, 36, .1) !important;
  }

  .edit-header-changed span:first-child {
    color: #fbbf24 !important;
  }

  .changed-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(251, 191, 36, .15);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, .3);
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 8px;
  }

  @media(max-width:768px) {
    .dashboard-icon {
      width: 50px;
      height: 50px;
      font-size: 20px;
    }

    .edit-change-body {
      grid-template-columns: 1fr;
    }

    .edit-change-col:first-child {
      border-right: none;
      border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    }
  }
</style>

<main id="main" class="main">

  <div class="pagetitle">
    <h1 class="text-white">Detail Aktivitas</h1>

    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?= base_url('user') ?>">Home</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= base_url('user/aktivitas') ?>">Log Aktivitas</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
      </ol>
    </nav>
  </div>

  <section class="section">

    <!-- META INFO CARD -->
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <h5 class="card-title mb-0">
            <?php if ($is_hapus): ?>
              <i class="bi bi-trash3 text-danger me-2"></i>Data Akun Yang Dihapus
            <?php elseif ($is_edit): ?>
              <i class="bi bi-pencil-square text-warning me-2"></i>Perubahan Akun
            <?php else: ?>
              Detail Aktivitas
            <?php endif; ?>
          </h5>

          <a href="<?= base_url('user/aktivitas') ?>" class="btn btn-primary btn-glow text-white">
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
      </div>
    </div>

    <?php if ($is_hapus && !empty($before_data)): ?>
      <!-- ================================ -->
      <!-- HAPUS: SHOW DELETED ACCOUNT DATA -->
      <!-- ================================ -->
      <div class="row g-4">

        <!-- LEFT -->
        <div class="col-lg-8">
          <div class="card border-0 shadow-lg">
            <div class="card-body p-4">

              <!-- HEADER -->
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                <div class="d-flex align-items-center gap-3">
                  <div class="dashboard-icon danger-card">
                    <i class="bi bi-person-x-fill"></i>
                  </div>
                  <div>
                    <h3 class="text-white fw-bold mb-2">
                      <?= htmlspecialchars($before_data['nama_akun'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <span class="deleted-badge">
                      <i class="bi bi-trash3-fill"></i> Telah Dihapus
                    </span>
                  </div>
                </div>
              </div>

              <!-- USERNAME -->
              <div class="mb-2">
                <label class="detail-label">Username</label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['username'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <!-- PASSWORD -->
              <div class="mb-2">
                <label class="detail-label">Password</label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['password'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <!-- 2FA (GEMINI) -->
              <?php if (strtoupper(trim((string) ($before_data['nama_akun'] ?? ''))) === 'GEMINI' && !empty($before_data['two_fa'])): ?>
              <div class="mb-2">
                <label class="detail-label">2FA</label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['two_fa'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- PASSWORD AKSES (ADOBE) -->
              <?php if (strtoupper(trim((string) ($before_data['nama_akun'] ?? ''))) === 'ADOBE'): ?>
              <div class="mb-2">
                <label class="detail-label">Password Akses</label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['password_akses'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- WEBSITE / AKSES -->
              <div class="mb-2">
                <label class="detail-label">
                  <?= strtoupper(trim((string) ($before_data['nama_akun'] ?? ''))) === 'ADOBE' ? 'Akses' : 'Website' ?>
                </label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['website'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <!-- NOTE -->
              <div>
                <label class="detail-label">Note</label>
                <div class="detail-box note-box">
                  <?= !empty($before_data['note'])
                      ? nl2br(htmlspecialchars($before_data['note'], ENT_QUOTES, 'UTF-8'))
                      : 'Tidak ada note' ?>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-lg">
            <div class="card-body p-4">

              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="dashboard-icon sales-card">
                  <i class="bi bi-info-circle-fill"></i>
                </div>
                <h5 class="text-white fw-bold mb-0">Informasi Tambahan</h5>
              </div>

              <!-- VARIASI -->
              <?php if (!empty($before_data['durasi_zoom'])): ?>
              <div class="mb-2">
                <label class="detail-label">Variasi</label>
                <div class="detail-box">
                  <?php
                    $durasi = $before_data['durasi_zoom'];
                    if ($durasi === '14_hari') echo '14 Hari';
                    elseif ($durasi === '1_bulan') echo '1 Bulan';
                    else echo htmlspecialchars($durasi, ENT_QUOTES, 'UTF-8');
                  ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- KATEGORI -->
              <div class="mb-2">
                <label class="detail-label">Kategori</label>
                <div class="detail-box">
                  <?= ucfirst(htmlspecialchars($before_data['kategori'] ?? '-', ENT_QUOTES, 'UTF-8')) ?>
                </div>
              </div>

              <!-- STATUS -->
              <div class="mb-2">
                <label class="detail-label">Status</label>
                <div class="detail-box">
                  <?= ucfirst(htmlspecialchars($before_data['status'] ?? '-', ENT_QUOTES, 'UTF-8')) ?>
                </div>
              </div>

              <!-- MAX USER -->
              <div class="mb-2">
                <label class="detail-label">Max User</label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['max_user'] ?? '0', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <!-- EXPIRED PASSWORD -->
              <div class="mb-2">
                <label class="detail-label">Expired Password</label>
                <div class="detail-box">
                  <?= !empty($before_data['expired_password'])
                      ? date('d M Y', strtotime($before_data['expired_password']))
                      : '-' ?>
                </div>
              </div>

              <!-- CREATED BY -->
              <div class="mb-2">
                <label class="detail-label">Created By</label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['created_by'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <!-- LAST EDITED BY -->
              <div class="mb-2">
                <label class="detail-label">Last Edited By</label>
                <div class="detail-box">
                  <?= htmlspecialchars($before_data['last_edited_by'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <!-- LAST EDITED AT -->
              <div>
                <label class="detail-label">Last Edited At</label>
                <div class="detail-box">
                  <?= !empty($before_data['last_edited_at'])
                      ? date('d M Y H:i', strtotime($before_data['last_edited_at'])) . ' WIB'
                      : '-' ?>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>

    <?php elseif ($is_edit && !empty($changes)): ?>
      <!-- ================================ -->
      <!-- EDIT: SHOW BEFORE → AFTER        -->
      <!-- ================================ -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">
            <i class="bi bi-arrow-left-right text-info me-2"></i>Detail Perubahan
          </h5>

          <?php foreach ($changes as $change): ?>
            <?php $is_changed = !empty($change['changed']); ?>
            <div class="edit-change-row <?= $is_changed ? 'edit-changed' : 'edit-unchanged' ?>">
              <div class="edit-change-header <?= $is_changed ? 'edit-header-changed' : '' ?>">
                <span><?= htmlspecialchars($change['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($is_changed): ?>
                  <span class="changed-badge"><i class="bi bi-pencil-fill"></i> Diubah</span>
                <?php endif; ?>
              </div>
              <div class="edit-change-body">
                <div class="edit-change-col">
                  <span class="edit-col-label before">
                    <i class="bi bi-x-circle-fill"></i> Sebelumnya
                  </span>
                  <div class="edit-col-value"><?= htmlspecialchars($formatValue($change['before']), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="edit-change-col">
                  <span class="edit-col-label after">
                    <i class="bi bi-check-circle-fill"></i> Sesudahnya
                  </span>
                  <div class="edit-col-value"><?= htmlspecialchars($formatValue($change['after']), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>

    <?php elseif (!empty($changes)): ?>
      <!-- FALLBACK: GENERIC TABLE -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Detail Perubahan</h5>
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
        </div>
      </div>

    <?php else: ?>
      <div class="card">
        <div class="card-body">
          <div class="alert alert-info mb-0">
            Detail perubahan belum tersedia untuk log ini.
          </div>
        </div>
      </div>
    <?php endif; ?>

  </section>

</main>
