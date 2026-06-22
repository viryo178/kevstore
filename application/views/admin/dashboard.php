<style>
  .bg-border-success {
    background-color: rgba(34, 197, 94, .12) !important;
    color: #4ade80 !important;
    border: 1px solid #22c55e !important;
  }

  .bg-border-danger {
    background-color: rgba(239, 68, 68, .12) !important;
    color: #f87171 !important;
    border: 1px solid #ef4444 !important;
  }

  #tableAkun .bg-border-success {
    background-color: rgba(34, 197, 94, .12) !important;
    color: #4ade80 !important;
    border: 1px solid #22c55e !important;
    padding: 4px 10px !important;
    border-radius: 8px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    display: inline-block !important;
  }

  #tableAkun .bg-border-danger {
    background-color: rgba(239, 68, 68, .12) !important;
    color: #f87171 !important;
    border: 1px solid #ef4444 !important;
    padding: 4px 10px !important;
    border-radius: 8px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    display: inline-block !important;
  }

  #tableAkun .badge-private {
    background-color: rgba(59, 130, 246, .12) !important;
    color: #60a5fa !important;
    border: 1px solid #3b82f6 !important;
    padding: 4px 10px !important;
    border-radius: 8px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    display: inline-block !important;
  }

  #tableAkun .badge-sharing {
    background-color: rgba(234, 179, 8, .12) !important;
    color: #facc15 !important;
    border: 1px solid #eab308 !important;
    padding: 4px 10px !important;
    border-radius: 8px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    display: inline-block !important;
  }

  .btn-tambah {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    border: none;
    color: #fff !important;
    padding: 10px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: .2s;
  }

  .btn-edit {
    background: rgba(234, 179, 8, .10);
    color: #facc15 !important;
    border: 1px solid rgba(234, 179, 8, .35);
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .btn-detail {
    background: rgba(59, 130, 246, .10);
    color: #60a5fa !important;
    border: 1px solid rgba(59, 130, 246, .35);
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
  }

  .password-text {
    color: #67e8f9;
    font-weight: 600;
  }

  .modal-content {
    background: #0f172a !important;
    color: white !important;
    border: 1px solid rgba(255, 255, 255, .08) !important;
  }

  .modal input,
  .modal textarea,
  .modal select {
    background: #1e293b !important;
    color: #fff !important;
    border: 1px solid #334155 !important;
  }

  .modal label {
    margin-bottom: 6px;
  }

  /* =========================
   FIX BACKDROP MODAL
========================= */

  /* backdrop modal jangan terlalu hitam */
  .modal-backdrop.show {
    opacity: 0.45 !important;
    background: #000 !important;
  }

  /* modal content */
  .modal-content {
    background: #0b1739 !important;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
  }

  /* header modal */
  .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 18px 24px;
  }

  /* footer modal */
  .modal-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding: 18px 24px;
  }

  /* body modal */
  .modal-body {
    padding: 24px;
  }

  /* title */
  .modal-title {
    color: #fff;
    font-weight: 700;
  }

  /* text */
  .modal-content label,
  .modal-content p,
  .modal-content span {
    color: #dbe7ff !important;
  }

  /* input */
  .modal-content .form-control,
  .modal-content .form-select,
  .modal-content textarea {
    background: #081225 !important;
    border: 1px solid #16366f !important;
    color: #fff !important;
    border-radius: 12px;
    min-height: 48px;
  }

  /* focus */
  .modal-content .form-control:focus,
  .modal-content .form-select:focus,
  .modal-content textarea:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, .25) !important;
  }

  /* placeholder */
  .modal-content .form-control::placeholder {
    color: #8aa3d1;
  }

  /* tombol */
  .modal-content .btn-warning {
    background: #facc15;
    border: none;
    color: #111;
    font-weight: 600;
  }

  .modal-content .btn-secondary {
    background: #1e293b;
    border: none;
  }

  /* =========================
   ALERT / NOTIF
========================= */

  /* notif hanya di body */
  .main .alert {
    width: 100%;
    border-radius: 14px;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
  }

  /* jangan full layar */
  .alert-success,
  .alert-danger,
  .alert-warning,
  .alert-info {
    max-width: 100%;
    overflow: hidden;
  }

  /* style notif modern */
  .alert-success {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
  }

  .alert-danger {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #f87171;
  }

  .alert-warning {
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #fbbf24;
  }

  .alert-info {
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.35);
    color: #60a5fa;
  }

  /* =========================
   FIX SIDEBAR TERTEMBUS
========================= */

  .sidebar {
    z-index: 1000 !important;
  }

  .main {
    position: relative;
    z-index: 1;
  }

  /* modal di atas body saja */
  .modal {
    z-index: 1055 !important;
  }

  .modal-backdrop {
    z-index: 1050 !important;
  }

  .dashboard-notif-list {
    display: grid;
    gap: 10px;
  }

  .dashboard-notif-list .notif-card {
    background: #0b1f3a !important;
    border-radius: 14px;
    padding: 11px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, .25);
    border: 1px solid rgba(255, 255, 255, .05);
    border-left: 4px solid transparent;
    transition: .3s;
    text-decoration: none;
  }

  .dashboard-notif-list .notif-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, .35);
  }

  .dashboard-notif-list .notif-danger {
    border-left-color: #ef4444;
  }

  .dashboard-notif-list .notif-warning {
    border-left-color: #facc15;
  }

  .dashboard-notif-list .notif-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
  }

  .dashboard-notif-list .notif-danger .notif-icon {
    background: rgba(239, 68, 68, .12);
    color: #ef4444;
    box-shadow: 0 0 15px rgba(239, 68, 68, .25), 0 0 30px rgba(239, 68, 68, .15);
  }

  .dashboard-notif-list .notif-warning .notif-icon {
    background: rgba(250, 204, 21, .12);
    color: #facc15;
    box-shadow: 0 0 15px rgba(250, 204, 21, .20), 0 0 30px rgba(250, 204, 21, .10);
  }

  .dashboard-notif-list .notif-content {
    flex: 1;
    min-width: 0;
  }

  .dashboard-notif-list .notif-title {
    font-size: 13px;
    font-weight: 700;
    color: #fff !important;
    margin-bottom: 3px;
  }

  .dashboard-notif-list .notif-desc {
    color: #cbd5e1 !important;
    font-size: 12px;
    line-height: 1.35;
    margin-bottom: 6px;
  }

  .dashboard-notif-list .notif-info {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    font-size: 11px;
    color: #93c5fd !important;
  }

  .dashboard-notif-list .notif-info span {
    background: rgba(255, 255, 255, .04);
    padding: 3px 7px;
    border-radius: 8px;
  }

  .dashboard-notif-list .notif-card {
    width: 100%;
    color: inherit;
  }

  .dashboard-notif-list button.notif-card {
    border-top: 1px solid rgba(255, 255, 255, .05);
    border-right: 1px solid rgba(255, 255, 255, .05);
    border-bottom: 1px solid rgba(255, 255, 255, .05);
    cursor: pointer;
    text-align: left;
  }

  .notif-count {
    background: rgba(96, 165, 250, .14);
    color: #bfdbfe;
    border: 1px solid rgba(96, 165, 250, .25);
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
  }

  .notif-chevron {
    color: #94a3b8;
    transition: transform .2s ease;
  }

  .notif-card[aria-expanded="true"] .notif-chevron {
    transform: rotate(180deg);
  }

  .notif-account-list {
    background: rgba(8, 18, 35, .88);
    border: 1px solid rgba(255, 255, 255, .06);
    border-top: 0;
    border-radius: 0 0 12px 12px;
    padding: 8px;
    margin-top: -10px;
  }

  .notif-account-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 6px;
    border-bottom: 1px solid rgba(255, 255, 255, .06);
  }

  .notif-account-item:last-child {
    border-bottom: 0;
  }

  .notif-account-name {
    color: #fff;
    font-size: 12px;
    font-weight: 700;
  }

  .notif-account-meta {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 2px;
  }
</style>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Dashboard</h1>

    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?= base_url('admin') ?>">Home</a>
        </li>

        <li class="breadcrumb-item active">
          Dashboard
        </li>
      </ol>
    </nav>
  </div>

  <section class="section dashboard">

    <!-- ALERT -->
    <div class="container-fluid px-0">

      <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show custom-alert">
          <i class="bi bi-check-circle me-1"></i>

          <?= $this->session->flashdata('success') ?>

          <button type="button"
            class="btn-close"
            data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show custom-alert">
          <i class="bi bi-exclamation-triangle me-1"></i>

          <?= $this->session->flashdata('error') ?>

          <button type="button"
            class="btn-close"
            data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

    </div>

    <div class="row">

      <?php
      if (!function_exists('kevstore_effective_akun_status')) {
        function kevstore_effective_akun_status($status, $note = '')
        {
          $status = strtolower(str_replace([' ', '-'], '_', trim((string) $status)));
          $note = strtolower(str_replace(['-', '_'], ' ', (string) $note));

          if (preg_match('/\bdisable\s*x\b/', $note)) {
            return 'disable_x';
          }

          if (preg_match('/\bdisable\s*email\b/', $note)) {
            return 'disable_email';
          }

          if (preg_match('/\bban(ned)?\b/', $note)) {
            return 'ban';
          }

          return $status;
        }
      }

      $deactived = 0;
      $verif = 0;
      $aktif = 0;
      $belum_terjual = 0;
      $inactive_statuses = ['deactived', 'disable_x', 'disable_email', 'ban'];

      if (!empty($akun)) {
        foreach ($akun as $a) {
          $status_akun = kevstore_effective_akun_status($a->status ?? '', $a->note ?? '');

          if (in_array($status_akun, $inactive_statuses, true)) {
            $deactived++;
          } elseif ($status_akun == 'verif') {
            $verif++;
          } elseif ($status_akun == 'aktif') {
            $aktif++;
          }

          if ($a->kategori == 'belum_terjual') {
            $belum_terjual++;
          }
        }
      }
      ?>
      <?php
      $total_akun = count($akun);

      $persen_verif = $total_akun > 0 ? round(($verif / $total_akun) * 100) : 0;
      $persen_aktif = $total_akun > 0 ? round(($aktif / $total_akun) * 100) : 0;
      $persen_deactived = $total_akun > 0 ? round(($deactived / $total_akun) * 100) : 0;
      $persen_belum_terjual = $total_akun > 0 ? round(($belum_terjual / $total_akun) * 100) : 0;
      $expired_total = count($expired_accounts ?? []) + count($almost_expired ?? []);
      $persen_expired = $total_akun > 0 ? round(($expired_total / $total_akun) * 100) : 0;
      ?>

      <!-- LEFT -->
      <div class="col-lg-8">

        <div class="row">

          <!-- VERIF -->
          <div class="col-xxl-4 col-md-6">

            <div class="card info-card sales-card">

              <div class="card-body">

                <h5 class="card-title">
                  Verif <span>| Total</span>
                </h5>

                <div class="d-flex align-items-center">

                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-shield-check"></i>
                  </div>

                  <div class="ps-3">

                    <h6><?= $verif ?></h6>

          <span class="text-danger small pt-1 fw-bold">
            <?= $persen_verif ?>%
          </span>

                  </div>

                </div>

              </div>

            </div>

          </div>

          <!-- AKTIF -->
          <div class="col-xxl-4 col-md-6">

            <div class="card info-card revenue-card">

              <div class="card-body">

                <h5 class="card-title">
                  Aktif <span>| Total</span>
                </h5>

                <div class="d-flex align-items-center">

                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-check-circle"></i>
                  </div>

                  <div class="ps-3">

                    <h6><?= $aktif ?></h6>

                    <span class="text-success small pt-1 fw-bold">
                      <?= $persen_aktif ?>%
                    </span>

                  </div>

                </div>

              </div>

            </div>

          </div>

          <!-- DEACTIVED -->
          <div class="col-xxl-4 col-xl-12">
            <a href="<?= base_url('admin/deactived') ?>" class="text-decoration-none">

            <div class="card info-card customers-card">

              <div class="card-body">

                <h5 class="card-title">
                  Deactived <span>| Total</span>
                </h5>

                <div class="d-flex align-items-center">

                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-x-circle"></i>
                  </div>

                  <div class="ps-3">

                    <h6><?= $deactived ?></h6>

                    <span class="text-danger small pt-1 fw-bold">
                      <?= $persen_deactived ?>%
                    </span>

                  </div>

                </div>

              </div>

            </div>
            </a>

          </div>

          <!-- BELUM TERJUAL -->
          <div class="col-xxl-6 col-md-6">

            <div class="card info-card customers-card">

              <div class="card-body">

                <h5 class="card-title">
                  Belum Terjual <span>| Total</span>
                </h5>

                <div class="d-flex align-items-center">

                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-bag-x"></i>
                  </div>

                  <div class="ps-3">

                    <h6><?= $belum_terjual ?></h6>

                    <span class="text-warning small pt-1 fw-bold">
                      <?= $persen_belum_terjual ?>%
                    </span>

                  </div>

                </div>

              </div>

            </div>

          </div>

          <!-- EXPIRED -->
          <div class="col-xxl-6 col-md-6">

            <div class="card info-card customers-card">

              <div class="card-body">

                <h5 class="card-title">
                  Expired <span>| Total</span>
                </h5>

                <div class="d-flex align-items-center">

                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-exclamation-triangle"></i>
                  </div>

                  <div class="ps-3">

                    <h6><?= $expired_total ?></h6>

                    <span class="text-danger small pt-1 fw-bold">
                      <?= $persen_expired ?>%
                    </span>

                  </div>

                </div>

              </div>

            </div>

          </div>

          <!-- TABLE -->
          <div class="col-12">

            <div class="card recent-sales overflow-auto">

              <div class="card-body">

                <h5 class="card-title">
                  Akun Tersedia
                  <span>| Max User < 5</span>
                </h5>

                <?php if (!empty($akun_belum_penuh)): ?>

                  <!-- SEARCH -->
                  <div class="row mb-3">

                    <div class="col-md-4">


                    </div>

                  </div>

                  <!-- TABLE -->
                  <div class="table-responsive">

                    <table class="table datatable table-borderless align-middle" id="tableAkun">

                      <thead>
                        <tr>
                          <th>Nama Akun</th>
                          <th>Username</th>
                          <th>Password</th>
                          <th>Max User</th>
                          <th>Kategori</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>

                      <tbody>

                        <?php foreach ($akun_belum_penuh as $a): ?>

                          <tr id="akun-item-<?= $a->id_akun ?>">

                            <td>
                              <strong>
                                <?= htmlspecialchars((string)$a->nama_akun) ?>
                              </strong>
                            </td>

                            <td>
                              <?= htmlspecialchars((string)$a->username) ?>
                            </td>

                            <td>
                              <code class="text-info">
                                <?= htmlspecialchars((string)$a->password) ?>
                              </code>
                            </td>


<td>

<?php
$limit = ($a->kategori == 'private') ? 1 : 5;
?>

<span class="<?= $a->max_user >= $limit ? 'bg-border-danger' : 'bg-border-success' ?>">
    <?= $a->max_user ?> / <?= $limit ?>
</span>

</td>

                            <td>

                              <?php if ($a->kategori == 'private'): ?>

                                <span class="badge-private">
                                  Private
                                </span>

                              <?php elseif ($a->kategori == 'sharing'): ?>

                                <span class="badge-sharing">
                                  Sharing
                                </span>

                              <?php else: ?>

                                <span class="badge-sharing">
                                  Belum Terjual
                                </span>

                              <?php endif; ?>

                            </td>

                            <td>

                              <!-- TAMBAH -->
<button
    class="btn btn-sm btn-primary btn-tambah-max"
    <?= $a->max_user >= $limit ? 'disabled' : '' ?>
    data-id="<?= $a->id_akun ?>"
    data-nama="<?= htmlspecialchars((string)$a->nama_akun, ENT_QUOTES, 'UTF-8') ?>"
    data-username="<?= htmlspecialchars((string)$a->username, ENT_QUOTES, 'UTF-8') ?>"
    data-password="<?= htmlspecialchars((string)$a->password, ENT_QUOTES, 'UTF-8') ?>"
    data-max="<?= $a->max_user ?>"
    data-kategori="<?= $a->kategori ?>">

                                <i class="bi bi-clipboard"></i>

                              </button>

                              <!-- EDIT -->
                              <button
                                class="btn btn-sm btn-warning btn-edit-akun"
                                data-id="<?= $a->id_akun ?>">

                                <i class="bi bi-pencil-square"></i>

                              </button>

                            </td>

                          </tr>

                        <?php endforeach; ?>

                      </tbody>

                    </table>

                  </div>

                <?php else: ?>

                  <div class="alert alert-danger">
                    Tidak ada akun tersedia
                  </div>

                <?php endif; ?>

              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- RIGHT -->
      <div class="col-lg-4">

        <div class="card recent-sales overflow-auto">

          <div class="card-body">

            <h5 class="card-title">
              Notifikasi Terbaru
            </h5>

            <div class="dashboard-notif-list">

              <?php
              $dashboard_notification_groups = [];

              $expired_dashboard_accounts = array_merge($expired_accounts ?? [], $almost_expired ?? []);

              if (!empty($expired_dashboard_accounts)) {
                $dashboard_notification_groups[] = [
                  'id' => 'expired',
                  'title' => 'Akun Expired',
                  'description' => count($expired_dashboard_accounts) . ' akun expired',
                  'icon' => 'bi-exclamation-triangle-fill',
                  'severity' => 'notif-danger',
                  'accounts' => $expired_dashboard_accounts,
                  'meta' => 'expired_password'
                ];
              }
              ?>

              <?php if (!empty($dashboard_notification_groups)): ?>

                <?php foreach ($dashboard_notification_groups as $index => $notification): ?>
                  <?php $collapse_id = 'dashboardNotif' . ucfirst($notification['id']); ?>

                  <div class="notif-group">
                    <button
                      type="button"
                      class="notif-card <?= htmlspecialchars((string)$notification['severity']) ?>"
                      data-bs-toggle="collapse"
                      data-bs-target="#<?= $collapse_id ?>"
                      aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                      aria-controls="<?= $collapse_id ?>">

                      <div class="notif-icon">
                        <i class="bi <?= htmlspecialchars((string)$notification['icon']) ?>"></i>
                      </div>

                      <div class="notif-content">
                        <div class="notif-title">
                          <?= htmlspecialchars((string)$notification['title']) ?>
                        </div>

                        <div class="notif-desc">
                          <?= htmlspecialchars((string)$notification['description']) ?>
                        </div>

                        <div class="notif-info">
                          <span><?= count($notification['accounts']) ?> akun</span>
                        </div>
                      </div>

                      <span class="notif-count"><?= count($notification['accounts']) ?></span>
                      <i class="bi bi-chevron-down notif-chevron"></i>
                    </button>

                    <div id="<?= $collapse_id ?>" class="collapse <?= $index === 0 ? 'show' : '' ?>">
                      <div class="notif-account-list">
                        <?php foreach ($notification['accounts'] as $account): ?>
                          <div class="notif-account-item">
                            <div>
                              <div class="notif-account-name">
                                <?= htmlspecialchars((string)$account->nama_akun) ?>
                              </div>
                              <div class="notif-account-meta">
                                <?= htmlspecialchars((string)$account->username) ?>
                                <?php if ($notification['meta'] === 'expired_password'): ?>
                                  - <?= !empty($account->expired_password) ? date('d M Y', strtotime($account->expired_password)) : '-' ?>
                                <?php elseif ($notification['meta'] === 'last_edited_at'): ?>
                                  - <?= !empty($account->last_edited_at) ? date('d M Y H:i', strtotime($account->last_edited_at)) : '-' ?>
                                <?php else: ?>
                                  - <?= htmlspecialchars(ucfirst((string)$account->status)) ?>
                                <?php endif; ?>
                              </div>
                            </div>

                            <button
                              type="button"
                              class="btn btn-sm btn-warning btn-edit-akun"
                              data-notification-check="1"
                              data-id="<?= (int)$account->id_akun ?>">
                              Edit
                            </button>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>

              <?php else: ?>

                <div class="text-muted">
                  Tidak ada notifikasi
                </div>

              <?php endif; ?>

            </div>

          </div>

        </div>

      </div>

    </div>

    </div>

  </section>

</main>

<!-- MODAL EDIT -->
<div class="modal fade" id="editAkunModal" tabindex="-1">

  <div class="modal-dialog modal-lg modal-dialog-centered">

    <div class="modal-content custom-modal">

      <form id="formEditAkun">

        <div class="modal-header border-0">

          <h5 class="modal-title text-white">
            Edit Akun
          </h5>

          <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="modal"></button>

        </div>

        <div class="modal-body">

          <input type="hidden" id="edit_id">

          <div class="row">

            <div class="col-md-6 mb-3">

              <label class="text-light">
                Nama Akun
              </label>

              <input
                type="text"
                id="edit_nama_akun"
                class="form-control custom-input">

            </div>

            <div class="col-md-6 mb-3 edit-extra-field">

              <label class="text-light">
                Kategori
              </label>

              <select id="edit_kategori" class="form-control custom-input">
    <option value="sharing">Sharing</option>
    <option value="private">Private</option>
    <option value="belum_terjual">Belum Terjual</option>
</select>

            </div>

            <div class="col-md-6 mb-3 edit-extra-field">

              <label class="text-light">
                Status
              </label>

              <select id="edit_status" class="form-control custom-input">

                <option value="aktif">Aktif</option>
                <option value="verif">Verif</option>
                <option value="deactived">Deactived</option>
                <option value="ban">Ban</option>
                <option value="disable_x">Disable X</option>
                <option value="disable_email">Disable Email</option>
                <option value="terjual">Terjual</option>

              </select>

            </div>

            <div class="col-md-6 mb-3">

              <label class="text-light">
                Username
              </label>

              <input
                type="text"
                id="edit_username"
                class="form-control custom-input">

            </div>

            <div class="col-md-6 mb-3">

              <label class="text-light">
                Password
              </label>

              <input
                type="text"
                id="edit_password"
                class="form-control custom-input">

            </div>

            <div class="col-md-6 mb-3 edit-extra-field">

              <label class="text-light">
                Website
              </label>

              <input
                type="text"
                id="edit_website"
                class="form-control custom-input">

            </div>

            <div class="col-md-6 mb-3 edit-extra-field">

              <label class="text-light">
                Max User
              </label>

              <input
                type="number"
                id="edit_max_user"
                class="form-control custom-input">

            </div>

            <div class="col-md-6 mb-3 edit-extra-field">

              <label class="text-light">
                Expired Password
              </label>

              <input
                type="date"
                id="edit_expired_password"
                class="form-control custom-input">

            </div>

            <div class="col-12">

              <label class="text-light">
                Note
              </label>

              <textarea
                id="edit_note"
                rows="4"
                class="form-control custom-input"></textarea>

            </div>

          </div>

        </div>

        <div class="modal-footer border-0">

          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal">

            Batal

          </button>

          <button
            type="submit"
            class="btn btn-warning">

            Update

          </button>

        </div>

      </form>

    </div>

  </div>

</div>

<style>
  .main {
    background: #071739;
    min-height: 100vh;
  }

  .card {
    background: #0b2147;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.05);
  }

  .table {
    color: #fff;
  }

  .table th {
    color: #9db8ff;
  }

  .custom-input {
    background: #071632 !important;
    border: 1px solid #1b4b91 !important;
    color: #fff !important;
  }

  .custom-input:focus {
    background: #071632 !important;
    color: #fff !important;
    box-shadow: none !important;
    border-color: #4c8dff !important;
  }

  .custom-modal {
    background: #0b1f44;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
  }

  .modal-backdrop.show {
    opacity: .5;
  }

  .custom-alert {
    position: relative;
    z-index: 1;
    margin-bottom: 20px;
  }
</style>

<script>
  function normalizeStatusValue(value) {
    return String(value === null || value === undefined ? '' : value)
      .trim()
      .toLowerCase()
      .replace(/[\s-]+/g, '_');
  }

  // SEARCH
const searchAkun = document.getElementById('searchAkun');

if (searchAkun) {

  searchAkun.addEventListener('keyup', function() {

    let value = this.value.toLowerCase();

    let rows = document.querySelectorAll('#tableAkun tbody tr');

    rows.forEach(function(row) {

      let text = row.innerText.toLowerCase();

      row.style.display =
        text.includes(value) ? '' : 'none';

    });

  });

}

  // OPEN EDIT
  document.addEventListener('click', function(e) {

    const btn = e.target.closest('.btn-edit-akun');

    if (!btn) return;

    const id = btn.dataset.id;
    const isNotificationCheck = btn.dataset.notificationCheck === '1';

    fetch('<?= base_url('admin/get_akun/') ?>' + id)

      .then(res => res.json())

      .then(res => {

        if (res.status === 'success') {

          const d = res.data;

          document.getElementById('edit_id').value = d.id_akun;
          document.getElementById('edit_nama_akun').value = d.nama_akun;
          document.getElementById('edit_kategori').value = d.kategori;
          document.getElementById('edit_status').value = normalizeStatusValue(d.status) || 'aktif';
          document.getElementById('edit_username').value = d.username;
          document.getElementById('edit_password').value = d.password;
          document.getElementById('edit_website').value = d.website;
          document.getElementById('edit_max_user').value = d.max_user;
          document.getElementById('edit_expired_password').value = d.expired_password;
          document.getElementById('edit_note').value = d.note;

          if (isNotificationCheck) {
            document.getElementById('edit_kategori').value = 'belum_terjual';
            document.getElementById('edit_status').value = 'aktif';
            document.getElementById('edit_max_user').value = 0;
            document.getElementById('edit_expired_password').value = '';
          }

          document.querySelectorAll('.edit-extra-field').forEach(function(field) {
            field.style.display = isNotificationCheck ? 'none' : '';
          });

          document.querySelector('#editAkunModal .modal-title').textContent =
            isNotificationCheck ? 'Update Akun' : 'Edit Akun';

          document.getElementById('formEditAkun').dataset.notificationCheck =
            isNotificationCheck ? '1' : '0';

          new bootstrap.Modal(
            document.getElementById('editAkunModal')
          ).show();

        }

      });

  });

  // SUBMIT EDIT
  document.getElementById('formEditAkun')
    .addEventListener('submit', function(e) {

      e.preventDefault();

      const id = document.getElementById('edit_id').value;

      const fd = new FormData();

      fd.append('nama_akun', document.getElementById('edit_nama_akun').value);
      fd.append('kategori', document.getElementById('edit_kategori').value);
      fd.append('status', document.getElementById('edit_status').value);
      fd.append('username', document.getElementById('edit_username').value);
      fd.append('password', document.getElementById('edit_password').value);
      fd.append('website', document.getElementById('edit_website').value);
      fd.append('max_user', document.getElementById('edit_max_user').value);
      fd.append('expired_password', document.getElementById('edit_expired_password').value);
      fd.append('note', document.getElementById('edit_note').value);

      fetch('<?= base_url('admin/edit_akun/') ?>' + id, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: fd
        })

        .then(res => res.json())

        .then(res => {

          if (res.status === 'success') {

            updateAkunRow(res.data);

            if (this.dataset.notificationCheck === '1') {
              document
                .querySelectorAll(`.btn-edit-akun[data-notification-check="1"][data-id="${id}"]`)
                .forEach(function(button) {
                  const item = button.closest('.notif-account-item');
                  if (item) item.remove();
                });
            }

            const modal = bootstrap.Modal.getInstance(
              document.getElementById('editAkunModal')
            );

            if (modal) {
              modal.hide();
            }

          } else {

            alert('Gagal update');

          }

        });




    });

  function getLoginText(kategori, username, password) {
    const baseText = `Email: ${username}
Password: ${password}
berikut cara login ke Grok menggunakan email:
1. Buka website atau aplikasi Grok.
2. Klik tombol Login / Sign in.
3. Pilih opsi Lanjut dengan Email atau Masuk dengan Email.
4. Masukkan email yang kami berikan
5. Jika diminta, masukkan password yang kami kasih
.
WAJIB KETIK ULANG JANGAN COPY PASTE.`;

    const footerRules = `\n\ndilarang Otak-atik Profil dan Password akun
dilarang Otak-atik billing payment
dilarang Mengganti Email & Password & MENDISABLE email dan X
MELANGGAR? DENDA 500K + GARANSI HANGUS + AKUN DI TARIK`;

    if (kategori !== 'sharing') {
      return `${baseText}${footerRules}`;
    }

    return `Email: ${username}
Password: ${password}
berikut cara login ke Grok menggunakan email:
1. Buka website atau aplikasi Grok.
2. Klik tombol Login / Sign in.
3. Pilih opsi Lanjut dengan Email atau Masuk dengan Email.
4. Masukkan email yang kami berikan
5. Jika diminta, masukkan password yang kami kasih
dilarang Otak-atik Profil dan Password akun
dilarang Otak-atik billing payment
dilarang Mengganti Email & Password & MENDISABLE email dan X
MELANGGAR? DENDA 500K + GARANSI HANGUS + AKUN DI TARIK
`;
  }

  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    return new Promise((resolve, reject) => {
      document.execCommand('copy') ? resolve() : reject();
      textarea.remove();
    });
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getAkunLimit(kategori) {
    return kategori === 'private' ? 1 : 5;
  }

  function isAkunAvailable(akun) {
    const maxUser = Number(akun.max_user || 0);

    return akun.status === 'aktif' && (
      akun.kategori === 'belum_terjual' ||
      (akun.kategori === 'sharing' && maxUser < 5) ||
      (akun.kategori === 'private' && maxUser < 1)
    );
  }

  function getKategoriBadge(kategori) {
    if (kategori === 'private') {
      return '<span class="badge-private">Private</span>';
    }

    if (kategori === 'sharing') {
      return '<span class="badge-sharing">Sharing</span>';
    }

    return '<span class="badge-sharing">Belum Terjual</span>';
  }

  function updateAkunRow(akun) {
    const row = document.getElementById('akun-item-' + akun.id_akun);

    if (!row) return;

    if (!isAkunAvailable(akun)) {
      row.remove();
      return;
    }

    const maxUser = Number(akun.max_user || 0);
    const limit = getAkunLimit(akun.kategori);
    const cells = row.querySelectorAll('td');
    const copyButton = row.querySelector('.btn-tambah-max');

    cells[0].innerHTML = `<strong>${escapeHtml(akun.nama_akun)}</strong>`;
    cells[1].innerHTML = escapeHtml(akun.username);
    cells[2].innerHTML = `<code class="text-info">${escapeHtml(akun.password)}</code>`;
    cells[3].innerHTML = `<span class="${maxUser >= limit ? 'bg-border-danger' : 'bg-border-success'}">${maxUser} / ${limit}</span>`;
    cells[4].innerHTML = getKategoriBadge(akun.kategori);

    if (copyButton) {
      copyButton.dataset.nama = akun.nama_akun || '';
      copyButton.dataset.username = akun.username || '';
      copyButton.dataset.password = akun.password || '';
      copyButton.dataset.max = String(maxUser);
      copyButton.dataset.kategori = akun.kategori || '';
      copyButton.disabled = maxUser >= limit;
    }
  }

  function updateMaxUserRow(id, data) {
    const row = document.getElementById('akun-item-' + id);

    if (!row) return;

    if (data.akun_status !== 'aktif' || Number(data.max_user) >= Number(data.limit)) {
      row.remove();
      return;
    }

    const badge = row.querySelector('td:nth-child(4) span');
    const copyButton = row.querySelector('.btn-tambah-max');

    if (badge) {
      badge.textContent = `${data.max_user} / ${data.limit}`;
      badge.className = Number(data.max_user) >= Number(data.limit) ? 'bg-border-danger' : 'bg-border-success';
    }

    if (copyButton) {
      copyButton.dataset.max = String(data.max_user);
      copyButton.disabled = Number(data.max_user) >= Number(data.limit);
    }
  }

  document.addEventListener('click', function(e) {

    const target = e.target.closest('.btn-tambah-max');

    if (!target) return;

    const id = target.dataset.id;
    const kategori = target.dataset.kategori;
    const username = target.dataset.username || '';
    const password = target.dataset.password || '';
    const loginText = getLoginText(kategori, username, password);

    copyToClipboard(loginText)
      .then(() => fetch(
        '<?= base_url('admin/ajax_tambah_max_user/') ?>' +
        id, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }))
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          updateMaxUserRow(id, data);
          alert('Data login berhasil dicopy');
        } else {
          alert(data.message);
        }
      })
      .catch(() => {
        alert('Gagal copy data login');
      });

  });
</script>
