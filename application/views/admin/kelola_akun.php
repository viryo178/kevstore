<style>
  .btn-hapus {
    background: rgba(239, 68, 68, .10);
    color: #f87171 !important;
    border: 1px solid rgba(239, 68, 68, .35);
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: .2s;
  }

  .btn-hapus:hover {
    background: #ef4444;
    color: #fff !important;
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

  .btn-tambah:disabled {
    opacity: .55;
    cursor: not-allowed;
  }

  .bulk-check {
    width: 18px;
    height: 18px;
    appearance: none;
    -webkit-appearance: none;
    background: #081225;
    border: 1px solid #16366f;
    border-radius: 5px;
    cursor: pointer;
    display: inline-grid;
    place-content: center;
    transition: .15s;
    vertical-align: middle;
  }

  .bulk-check::after {
    content: "";
    width: 5px;
    height: 9px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    opacity: 0;
    transform: rotate(45deg) scale(.8);
    transition: .15s;
  }

  .bulk-check:checked {
    background: #2563eb;
    border-color: #60a5fa;
  }

  .bulk-check:checked::after {
    opacity: 1;
    transform: rotate(45deg) scale(1);
  }

  .bulk-check:indeterminate {
    background: #2563eb;
    border-color: #60a5fa;
  }

  .bulk-check:indeterminate::after {
    width: 9px;
    height: 2px;
    border: 0;
    background: #fff;
    opacity: 1;
    transform: none;
  }

  #tableAkun th:first-child,
  #tableAkun td:first-child {
    width: 44px;
    text-align: center;
  }

  .bulk-edit-row {
    background: rgba(15, 23, 42, .55);
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 16px;
  }

  .bulk-edit-row-title {
    color: #fff;
    font-weight: 700;
    margin-bottom: 14px;
  }

  #bulkEditModal .modal-dialog {
    max-height: calc(100vh - 32px);
  }

  #bulkEditModal .modal-content {
    max-height: calc(100vh - 32px);
    overflow: hidden;
  }

  #bulkEditModal .modal-body {
    overflow-y: auto;
    max-height: calc(100vh - 210px);
    scrollbar-width: none;
    -ms-overflow-style: none;
  }

  #bulkEditModal .modal-body::-webkit-scrollbar {
    width: 0;
    height: 0;
    display: none;
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

  .info-card {
    background: #0b2147;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, .05);
  }

  .stat-card-link {
    color: inherit;
    display: block;
    text-decoration: none;
  }

  .stat-card-link:hover {
    color: inherit;
  }

  .stat-card-link .info-card {
    cursor: pointer;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
  }

  .stat-card-link:hover .info-card {
    border-color: rgba(59, 130, 246, .45);
    box-shadow: 0 16px 30px rgba(2, 8, 23, .22);
    transform: translateY(-2px);
  }

  .info-card .card-title {
    color: #fff;
    font-size: 15px;
    font-weight: 600;
  }

  .info-card .card-title span {
    color: #8aa3d1;
    font-size: 13px;
  }

  .info-card h6 {
    color: #fff;
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 0;
  }

  .card-icon {
    width: 64px;
    height: 64px;
    background: rgba(59, 130, 246, .12);
    color: #60a5fa;
    font-size: 28px;
  }

  .kelola-akun-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin: 0 0 14px;
    flex-wrap: wrap;
  }

  .kelola-akun-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
    flex-wrap: wrap;
  }

  .kelola-akun-card-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-left: auto;
  }

  .kelola-akun-controls label {
    color: #dbe7ff;
    font-size: 14px;
    margin: 0;
  }

  .kelola-akun-controls select,
  .kelola-akun-controls input {
    background: #081225;
    border: 1px solid #16366f;
    color: #fff;
    border-radius: 8px;
    min-height: 34px;
    font-size: 13px;
  }

  .kelola-akun-controls select {
    padding: 4px 28px 4px 9px;
    margin: 0 5px;
  }

  .kelola-akun-controls input {
    width: 220px;
    max-width: 100%;
    padding: 6px 10px;
  }

  .kelola-akun-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 14px;
    flex-wrap: wrap;
    color: #dbe7ff;
    font-size: 14px;
  }

  .kelola-akun-pagination {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .kelola-akun-pagination button {
    background: #081225;
    border: 1px solid #16366f;
    color: #dbe7ff;
    border-radius: 9px;
    min-width: 38px;
    height: 36px;
    padding: 0 12px;
  }

  .kelola-akun-pagination button.active {
    background: #2563eb;
    border-color: #3b82f6;
    color: #fff;
  }

  .kelola-akun-pagination button:disabled {
    opacity: .5;
    cursor: not-allowed;
  }
</style>



<main id="main" class="main">
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
  ?>
  <!-- kelola-akun-table-fix-v3-manual-controls -->

  <div class="pagetitle">
    <h1><?= htmlspecialchars($page_title ?? 'Kelola Akun', ENT_QUOTES, 'UTF-8') ?></h1>
  </div>

  <section class="section dashboard">

    <div class="row">
      <?php
      $total_akun = count($akun);

      $total_verif = 0;
      $total_aktif = 0;
      $total_deactived = 0;
      $total_disable_x = 0;
      $total_disable_email = 0;
      $total_ban = 0;
      $total_belum_terjual = 0;

      foreach ($akun as $a) {
        $status_akun = kevstore_effective_akun_status($a->status ?? '', $a->note ?? '');

        if ($status_akun == 'verif') {
          $total_verif++;
        }

        if ($status_akun == 'aktif') {
          $total_aktif++;
        }

        if ($status_akun == 'deactived') {
          $total_deactived++;
        }
        if ($status_akun == 'disable_x') {
          $total_disable_x++;
        }
        if ($status_akun == 'disable_email') {
          $total_disable_email++;
        }
        if ($status_akun == 'ban') {
          $total_ban++;
        }
        if ($a->kategori == 'belum_terjual') {
          $total_belum_terjual++;
        }
      }

      $persen_verif = $total_akun > 0 ? round(($total_verif / $total_akun) * 100) : 0;
      $persen_aktif = $total_akun > 0 ? round(($total_aktif / $total_akun) * 100) : 0;
      $persen_deactived = $total_akun > 0 ? round(($total_deactived / $total_akun) * 100) : 0;
      $persen_disable_x = $total_akun > 0 ? round(($total_disable_x / $total_akun) * 100) : 0;
      $persen_disable_email = $total_akun > 0 ? round(($total_disable_email / $total_akun) * 100) : 0;
      $persen_ban = $total_akun > 0 ? round(($total_ban / $total_akun) * 100) : 0;
      $persen_belum_terjual = $total_akun > 0 ? round(($total_belum_terjual / $total_akun) * 100) : 0;
      ?>

      <!-- TOTAL SEMUA AKUN -->
      <div class="col-xxl-3 col-md-6">
        <a href="<?= base_url('admin/kelola_akun') ?>" class="stat-card-link">

          <div class="card info-card sales-card">

            <div class="card-body">

              <h5 class="card-title">
                Total Akun <span>| Semua</span>
              </h5>

              <div class="d-flex align-items-center">

                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">

                  <i class="bi bi-people-fill"></i>

                </div>

                <div class="ps-3">

                  <h6><?= $total_akun ?></h6>

                  <span class="text-primary small pt-1 fw-bold">
                    100%
                  </span>

                </div>

              </div>

            </div>

          </div>
        </a>

      </div>

      <!-- TOTAL VERIF -->
      <div class="col-xxl-3 col-md-6">

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

                <h6><?= $total_verif ?></h6>

                <span class="text-danger small pt-1 fw-bold">
                  <?= $persen_verif ?>%
                </span>

              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- TOTAL AKTIF -->
      <div class="col-xxl-3 col-md-6">

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

                <h6><?= $total_aktif ?></h6>

                <span class="text-success small pt-1 fw-bold">
                  <?= $persen_aktif ?>%
                </span>

              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- TOTAL DEACTIVED -->
      <div class="col-xxl-3 col-md-6">
        <a href="<?= base_url('admin/deactived') ?>" class="stat-card-link">

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

                  <h6><?= $total_deactived ?></h6>

                  <span class="text-danger small pt-1 fw-bold">
                    <?= $persen_deactived ?>%
                  </span>

                </div>

              </div>

            </div>

          </div>
        </a>

      </div><!-- TOTAL BELUM TERJUAL -->
      <div class="col-xxl-3 col-md-6">
        <a href="<?= base_url('admin/deactived') ?>" class="stat-card-link">

          <div class="card info-card customers-card">

            <div class="card-body">

              <h5 class="card-title">
                Disable X <span>| Total</span>
              </h5>

              <div class="d-flex align-items-center">

                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">

                  <i class="bi bi-x-octagon"></i>

                </div>

                <div class="ps-3">

                  <h6><?= $total_disable_x ?></h6>

                  <span class="text-danger small pt-1 fw-bold">
                    <?= $persen_disable_x ?>%
                  </span>

                </div>

              </div>

            </div>

          </div>
        </a>

      </div>
      <div class="col-xxl-3 col-md-6">
        <a href="<?= base_url('admin/deactived') ?>" class="stat-card-link">

          <div class="card info-card customers-card">

            <div class="card-body">

              <h5 class="card-title">
                Disable Email <span>| Total</span>
              </h5>

              <div class="d-flex align-items-center">

                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">

                  <i class="bi bi-envelope-x"></i>

                </div>

                <div class="ps-3">

                  <h6><?= $total_disable_email ?></h6>

                  <span class="text-danger small pt-1 fw-bold">
                    <?= $persen_disable_email ?>%
                  </span>

                </div>

              </div>

            </div>

          </div>
        </a>

      </div>
      <div class="col-xxl-3 col-md-6">
        <a href="<?= base_url('admin/deactived') ?>" class="stat-card-link">

          <div class="card info-card customers-card">

            <div class="card-body">

              <h5 class="card-title">
                Ban <span>| Total</span>
              </h5>

              <div class="d-flex align-items-center">

                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">

                  <i class="bi bi-slash-circle"></i>

                </div>

                <div class="ps-3">

                  <h6><?= $total_ban ?></h6>

                  <span class="text-danger small pt-1 fw-bold">
                    <?= $persen_ban ?>%
                  </span>

                </div>

              </div>

            </div>

          </div>
        </a>

      </div>
      <!-- TOTAL BELUM TERJUAL -->
      <div class="col-xxl-3 col-md-6">
        <a href="<?= base_url('admin') ?>" class="stat-card-link">

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

                  <h6><?= $total_belum_terjual ?></h6>

                  <span class="text-warning small pt-1 fw-bold">
                    <?= $persen_belum_terjual ?>%
                  </span>

                </div>

              </div>

            </div>

          </div>
        </a>

      </div>
      <div class="col-12">

        <div class="card recent-sales overflow-auto">

          <div class="card-body">

            <div class="kelola-akun-card-head">

              <h5 class="card-title mb-0">
                <?= htmlspecialchars($table_title ?? 'Data Seluruh Akun', ENT_QUOTES, 'UTF-8') ?>
              </h5>

              <div class="kelola-akun-card-actions">
                <form id="bulkEditSelectForm" action="<?= base_url('admin/bulk_edit_akun') ?>" method="GET"></form>

                <a
                  href="<?= base_url('admin/bulk_tambah_akun') ?>"
                  class="btn-tambah text-decoration-none">

                  <i class="bi bi-list-plus"></i>
                  Bulk Tambah

                </a>

                <button
                  type="submit"
                  class="btn-tambah"
                  id="bulkEditButton"
                  form="bulkEditSelectForm">

                  <i class="bi bi-pencil-square"></i>
                  Bulk Edit <span id="bulkSelectedCount">(0)</span>

                </button>

                <a
                  href="<?= base_url('admin/tambah_akun') ?>"
                  class="btn-tambah text-decoration-none">

                  <i class="bi bi-plus-lg"></i>
                  Tambah Akun

                </a>
              </div>

            </div>

            <div class="datatable-top kelola-manual-table-top">
              <div class="datatable-dropdown">
                <label>
                  <select class="datatable-selector" id="kelolaEntriesPerPage" aria-label="Pilih jumlah data per halaman">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="-1">All</option>
                  </select>
                  entries per page
                </label>
              </div>

              <div class="datatable-search">
                <input class="datatable-input" id="kelolaTableSearch" type="search" placeholder="Search..." aria-label="Search" autocomplete="off">
              </div>
            </div>

            <div class="table-responsive">

              <table
                class="table table-borderless align-middle"
                id="tableAkun">

                
                <thead>
                  <tr>
                    <th>
                      <input type="checkbox" class="bulk-check" id="bulkCheckAll">
                    </th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Expired</th>
                    <th>Aksi</th>
                  </tr>
                </thead>

                <tbody>

                  <?php foreach ($akun as $a): ?>
                    <?php
                    $search_text = implode(' ', [
                      $a->id_akun ?? '',
                      $a->nama_akun ?? '',
                      $a->username ?? '',
                      $a->password ?? '',
                      $a->kategori ?? '',
                      str_replace('_', ' ', $a->kategori ?? ''),
                      $a->status ?? '',
                      $a->website ?? '',
                      $a->max_user ?? '',
                      $a->expired_password ?? '',
                      !empty($a->expired_password) ? date('d-m-Y', strtotime($a->expired_password)) : '',
                      $a->note ?? ''
                    ]);
                    ?>

                    <tr id="akun-row-<?= $a->id_akun ?>"
                      data-search="<?= htmlspecialchars($search_text, ENT_QUOTES, 'UTF-8') ?>">

                      <td>
                        <input type="checkbox"
                          class="bulk-check bulk-akun-check"
                          name="ids[]"
                          form="bulkEditSelectForm"
                          value="<?= $a->id_akun ?>">
                      </td>

                      <td>
                        <strong>
                          <?= $a->nama_akun ?>
                        </strong>
                      </td>

                      <td>
                        <?= $a->username ?>
                      </td>

                      <td>
                        <span class="password-text">
                          <?= $a->password ?>
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

                        <?php elseif ($a->kategori == 'belum_terjual'): ?>

                          <span class="badge-sharing">
                            Belum Terjual
                          </span>

                        <?php endif; ?>

                      </td>

                      <td>
                        <?php $status_akun = kevstore_effective_akun_status($a->status ?? '', $a->note ?? ''); ?>

                        <?php if ($status_akun == 'aktif'): ?>

                          <span class="bg-border-success">
                            Aktif
                          </span>

                        <?php elseif ($status_akun == 'verif'): ?>

                          <span class="bg-border-danger">
                            Verif
                          </span>

                        <?php elseif ($status_akun == 'deactived'): ?>

                          <span class="bg-border-danger">
                            Deactived
                          </span>

                        <?php elseif ($status_akun == 'terjual'): ?>

                          <span class="bg-border-success"
                            style="background:rgba(99,102,241,.15);color:#a5b4fc;border:1px solid #6366f1;">
                            Terjual
                          </span>

                        <?php elseif ($status_akun == 'ban'): ?>

                          <span class="bg-border-danger">
                            Ban
                          </span>

                        <?php elseif ($status_akun == 'disable_x'): ?>

                          <span class="bg-border-danger">
                            Disable X
                          </span>

                        <?php elseif ($status_akun == 'disable_email'): ?>

                          <span class="bg-border-danger">
                            Disable Email
                          </span>

                        <?php endif; ?>

                      </td>

                      <td>
                        <?= !empty($a->expired_password) ? date('d-m-Y', strtotime($a->expired_password)) : '-' ?>
                      </td>

                      <td class="d-flex gap-2">

                        <a href="<?= base_url('admin/detail_akun/' . $a->id_akun) ?>"
                          class="btn-detail">

                          <i class="bi bi-eye-fill"></i>

                        </a>

                        <a
                          href="<?= base_url('admin/edit_akun/' . $a->id_akun) ?>"
                          class="btn-edit">

                          <i class="bi bi-pencil-square"></i>

                        </a>

                        <a href="<?= base_url('admin/hapus_akun/' . $a->id_akun) ?>"
                          class="btn-hapus"
                          data-delete-akun>

                          <i class="bi bi-trash-fill"></i>

                        </a>

                      </td>

                    </tr>

                  <?php endforeach; ?>

                </tbody>

              </table>

            </div>

            <div class="datatable-bottom kelola-manual-table-bottom">
              <div class="datatable-info" id="kelolaTableInfo">Menampilkan 0 sampai 0 dari 0 entries</div>
              <nav class="datatable-pagination" id="kelolaTablePagination" aria-label="Table pagination"></nav>
            </div>
          </div>

        </div>

      </div>

    </div>

  </section>

</main><script type="application/json" id="bulkAkunData">
  <?= json_encode($akun, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>

<script>
  const kelolaTableState = { page: 1 };

  function getKelolaTableRows() {
    return Array.from(document.querySelectorAll('#tableAkun tbody tr'));
  }

  function getKelolaFilteredRows() {
    const search = document.getElementById('kelolaTableSearch');
    const keyword = search ? search.value.trim().toLowerCase() : '';

    return getKelolaTableRows().filter(function(row) {
      const haystack = (row.dataset.search || row.innerText || '').toLowerCase();
      return haystack.includes(keyword);
    });
  }

  function renderKelolaPagination(totalPages) {
    const pagination = document.getElementById('kelolaTablePagination');

    if (!pagination) return;

    pagination.innerHTML = '';

    if (totalPages <= 1) return;

    const list = document.createElement('ul');
    list.className = 'datatable-pagination-list';

    function addButton(label, page, disabled, active) {
      const item = document.createElement('li');
      if (disabled) item.classList.add('datatable-disabled');
      if (active) item.classList.add('datatable-active');

      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = label;
      button.disabled = disabled;
      button.addEventListener('click', function() {
        if (disabled) return;
        kelolaTableState.page = page;
        renderKelolaManualTable(false);
      });

      item.appendChild(button);
      list.appendChild(item);
    }

    addButton('<', Math.max(1, kelolaTableState.page - 1), kelolaTableState.page === 1, false);

    const maxButtons = 6;
    const startPage = totalPages <= maxButtons
      ? 1
      : Math.max(1, Math.min(kelolaTableState.page - 2, totalPages - maxButtons + 1));
    const endPage = Math.min(totalPages, startPage + maxButtons - 1);

    for (let page = startPage; page <= endPage; page++) {
      addButton(String(page), page, false, page === kelolaTableState.page);
    }

    addButton('>', Math.min(totalPages, kelolaTableState.page + 1), kelolaTableState.page === totalPages, false);
    pagination.appendChild(list);
  }

  function renderKelolaManualTable(resetPage = false) {
    const selector = document.getElementById('kelolaEntriesPerPage');
    const info = document.getElementById('kelolaTableInfo');
    const allRows = getKelolaTableRows();
    const filteredRows = getKelolaFilteredRows();
    const perPage = selector ? parseInt(selector.value, 10) : 10;

    if (resetPage) kelolaTableState.page = 1;

    const totalRows = filteredRows.length;
    const totalPages = perPage === -1 ? 1 : Math.max(1, Math.ceil(totalRows / perPage));
    kelolaTableState.page = Math.min(Math.max(kelolaTableState.page, 1), totalPages);

    const startIndex = perPage === -1 ? 0 : (kelolaTableState.page - 1) * perPage;
    const endIndex = perPage === -1 ? totalRows : Math.min(startIndex + perPage, totalRows);
    const visibleRows = new Set(filteredRows.slice(startIndex, endIndex));

    allRows.forEach(function(row) {
      row.style.display = visibleRows.has(row) ? '' : 'none';
    });

    if (info) {
      info.textContent = totalRows
        ? `Menampilkan ${startIndex + 1} sampai ${endIndex} dari ${totalRows} entries`
        : 'Menampilkan 0 sampai 0 dari 0 entries';
    }

    renderKelolaPagination(totalPages);

    if (typeof updateBulkSelectionUi === 'function') {
      updateBulkSelectionUi();
    }
  }

  function initKelolaNiceTable() {
    const table = document.getElementById('tableAkun');
    const selector = document.getElementById('kelolaEntriesPerPage');
    const search = document.getElementById('kelolaTableSearch');

    if (!table || !selector || !search) {
      return false;
    }

    if (table.dataset.kelolaManualReady !== '1') {
      table.dataset.kelolaManualReady = '1';
      selector.addEventListener('change', function() {
        renderKelolaManualTable(true);
      });
      search.addEventListener('input', function() {
        renderKelolaManualTable(true);
      });
    }

    renderKelolaManualTable(true);
    return true;
  }

  window.kelolaAkunRenderTable = renderKelolaManualTable;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(initKelolaNiceTable, 0);
    });
  } else {
    setTimeout(initKelolaNiceTable, 0);
  }

  window.addEventListener('load', initKelolaNiceTable);
  const selectedBulkAkun = new Set();
  let akunBulkData = readBulkAkunData(document);

  function readBulkAkunData(sourceDocument) {
    const dataScript = sourceDocument.querySelector('#bulkAkunData');

    if (!dataScript) return [];

    try {
      return JSON.parse(dataScript.textContent);
    } catch (error) {
      return [];
    }
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizeStatusValue(value) {
    return String(value === null || value === undefined ? '' : value)
      .trim()
      .toLowerCase()
      .replace(/[\s-]+/g, '_');
  }

  function selectedOption(value, expected) {
    return normalizeStatusValue(value) === expected ? 'selected' : '';
  }

  function getVisibleBulkChecks() {
    return Array.from(document.querySelectorAll('.bulk-akun-check')).filter(function(check) {
      const row = check.closest('tr');
      return row && row.style.display !== 'none';
    });
  }

  function updateBulkSelectionUi() {
    document.querySelectorAll('.bulk-akun-check:checked').forEach(function(check) {
      selectedBulkAkun.add(check.value);
    });

    const selectedCount = selectedBulkAkun.size;
    const bulkButton = document.getElementById('bulkEditButton');
    const bulkCount = document.getElementById('bulkSelectedCount');
    const checkAll = document.getElementById('bulkCheckAll');
    const rowChecks = getVisibleBulkChecks();

    rowChecks.forEach(function(check) {
      check.checked = selectedBulkAkun.has(check.value);
    });

    if (bulkButton) bulkButton.disabled = false;
    if (bulkCount) bulkCount.textContent = `(${selectedCount})`;

    if (checkAll) {
      const checkedRows = rowChecks.filter(function(check) { return check.checked; }).length;
      checkAll.checked = rowChecks.length > 0 && checkedRows === rowChecks.length;
      checkAll.indeterminate = checkedRows > 0 && checkedRows < rowChecks.length;
    }
  }

  function showCrudMessage(message) {
    alert(message);
  }

  function buildBulkEditRow(account) {
    const id = account.id_akun;

    return `
      <div class="bulk-edit-row">
        <div class="bulk-edit-row-title">${escapeHtml(account.nama_akun || 'Akun')} #${escapeHtml(id)}</div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Nama Akun</label>
            <input type="text" name="akun[${id}][nama_akun]" class="form-control" value="${escapeHtml(account.nama_akun)}">
          </div>
          <div class="col-md-6 mb-3">
            <label>Kategori</label>
            <select name="akun[${id}][kategori]" class="form-select">
              <option value="private" ${selectedOption(account.kategori, 'private')}>Private</option>
              <option value="sharing" ${selectedOption(account.kategori, 'sharing')}>Sharing</option>
              <option value="belum_terjual" ${selectedOption(account.kategori, 'belum_terjual')}>Belum Terjual</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label>Status</label>
            <select name="akun[${id}][status]" class="form-select">
              <option value="aktif" ${selectedOption(account.status, 'aktif')}>Aktif</option>
              <option value="verif" ${selectedOption(account.status, 'verif')}>Verif</option>
              <option value="deactived" ${selectedOption(account.status, 'deactived')}>Deactived</option>
              <option value="ban" ${selectedOption(account.status, 'ban')}>Ban</option>
              <option value="disable_x" ${selectedOption(account.status, 'disable_x')}>Disable X</option>
              <option value="disable_email" ${selectedOption(account.status, 'disable_email')}>Disable Email</option>
              <option value="terjual" ${selectedOption(account.status, 'terjual')}>Terjual</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label>Username</label>
            <input type="text" name="akun[${id}][username]" class="form-control" value="${escapeHtml(account.username)}">
          </div>
          <div class="col-md-6 mb-3">
            <label>Password</label>
            <input type="text" name="akun[${id}][password]" class="form-control" value="${escapeHtml(account.password)}">
          </div>
          <div class="col-md-6 mb-3">
            <label>Website</label>
            <input type="text" name="akun[${id}][website]" class="form-control" value="${escapeHtml(account.website)}">
          </div>
          <div class="col-md-6 mb-3">
            <label>Max User</label>
            <input type="number" name="akun[${id}][max_user]" class="form-control" value="${escapeHtml(account.max_user)}">
          </div>
          <div class="col-md-6 mb-3">
            <label>Expired Password</label>
            <input type="date" name="akun[${id}][expired_password]" class="form-control" value="${escapeHtml(account.expired_password)}">
          </div>
          <div class="col-md-12 mb-3">
            <label>Note</label>
            <textarea name="akun[${id}][note]" rows="3" class="form-control">${escapeHtml(account.note)}</textarea>
          </div>
        </div>
      </div>
    `;
  }

  function openBulkEditModal() {
    const selectedAccounts = akunBulkData.filter(function(account) {
      return selectedBulkAkun.has(String(account.id_akun));
    });
    const container = document.getElementById('bulkEditContainer');
    const modalEl = document.getElementById('bulkEditModal');

    if (!selectedAccounts.length || !container || !modalEl) {
      showCrudMessage('Pilih akun yang ingin diedit');
      return;
    }

    container.innerHTML = selectedAccounts.map(buildBulkEditRow).join('');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  document.addEventListener('change', function(e) {
    const rowCheck = e.target.closest('.bulk-akun-check');

    if (rowCheck) {
      if (rowCheck.checked) {
        selectedBulkAkun.add(rowCheck.value);
      } else {
        selectedBulkAkun.delete(rowCheck.value);
      }
      updateBulkSelectionUi();
      return;
    }

    if (e.target.id === 'bulkCheckAll') {
      getVisibleBulkChecks().forEach(function(check) {
        if (e.target.checked) {
          selectedBulkAkun.add(check.value);
        } else {
          selectedBulkAkun.delete(check.value);
        }
      });
      updateBulkSelectionUi();
    }
  });
  document.addEventListener('click', function(e) {
    const deleteButton = e.target.closest('[data-delete-akun]');

    if (!deleteButton) return;

    e.preventDefault();
  });

  function parseJsonResponse(response) {
    return response.text().then(function(text) {
      try {
        return JSON.parse(text);
      } catch (error) {
        return {
          status: response.ok ? 'success' : 'error',
          message: response.ok ? 'Berhasil diproses' : 'Gagal memproses request'
        };
      }
    });
  }

  function closeModalFromElement(element) {
    const modalEl = element ? element.closest('.modal') : null;

    if (!modalEl || !window.bootstrap) return;

    const modal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.hide();
  }

  function refreshKelolaAkunTable(resetPage = false) {
    return fetch(window.location.href, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      cache: 'no-store'
    })
      .then(function(response) {
        return response.text();
      })
      .then(function(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const table = document.getElementById('tableAkun');
        const currentBody = document.querySelector('#tableAkun tbody');
        const latestBody = doc.querySelector('#tableAkun tbody');
        const currentData = document.querySelector('#bulkAkunData');
        const latestData = doc.querySelector('#bulkAkunData');

        if (currentBody && latestBody) {
          currentBody.innerHTML = latestBody.innerHTML;
        }

        if (currentData && latestData) {
          currentData.textContent = latestData.textContent;
          akunBulkData = readBulkAkunData(document);
        }

        document.querySelectorAll('.modal[id^="editModal"]').forEach(function(modal) {
          modal.remove();
        });

        doc.querySelectorAll('.modal[id^="editModal"]').forEach(function(modal) {
          document.body.appendChild(modal.cloneNode(true));
        });

        selectedBulkAkun.clear();

        if (typeof window.kelolaAkunRenderTable === 'function') {
          window.kelolaAkunRenderTable(resetPage);
        }

        updateBulkSelectionUi();
      });
  }

  function submitAjaxForm(form) {
    const button = form.querySelector('[type="submit"]');

    if (button) button.disabled = true;

    return fetch(form.action, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: new FormData(form)
    })
      .then(parseJsonResponse)
      .then(function(result) {
        if (result.status !== 'success') {
          throw new Error(result.message || 'Request gagal diproses');
        }

        closeModalFromElement(form);
        form.reset();
        showCrudMessage(result.message || 'Data berhasil disimpan');
        return refreshKelolaAkunTable();
      })
      .catch(function(error) {
        showCrudMessage(error.message || 'Request gagal diproses');
      })
      .finally(function() {
        if (button) button.disabled = false;
      });
  }

  document.addEventListener('submit', function(e) {
    const form = e.target;

    if (
      form.matches('#formTambahAkun') ||
      form.matches('#formBulkEditAkun') ||
      form.matches('.form-edit-akun')
    ) {
      e.preventDefault();
      submitAjaxForm(form);
    }
  });

  document.addEventListener('click', function(e) {
    const tambahButton = e.target.closest('[data-bs-target="#tambahModal"]');

    if (tambahButton && window.bootstrap) {
      const modalEl = document.getElementById('tambahModal');
      if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
      return;
    }

    const editButton = e.target.closest('[data-bs-target^="#editModal"]');

    if (editButton && window.bootstrap) {
      const modalEl = document.querySelector(editButton.getAttribute('data-bs-target'));
      if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
      return;
    }

    const deleteButton = e.target.closest('[data-delete-akun]');

    if (!deleteButton) return;

    e.preventDefault();

    if (!confirm('Yakin ingin menghapus akun ini?')) {
      return;
    }

    fetch(deleteButton.href, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(parseJsonResponse)
      .then(function(result) {
        if (result.status !== 'success') {
          throw new Error(result.message || 'Akun gagal dihapus');
        }

        showCrudMessage(result.message || 'Akun berhasil dihapus');
        return refreshKelolaAkunTable();
      })
      .catch(function(error) {
        showCrudMessage(error.message || 'Akun gagal dihapus');
      });
  });

  setTimeout(updateBulkSelectionUi, 0);
</script>
