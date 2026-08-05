<style>
    :root {
        --bg-main: #071739;
        --bg-card: #0b2147;
        --bg-input: #071632;
        --border: #1b4b91;
        --primary: #4c8dff;
        --text: #ffffff;
        --text-soft: #9db8ff;
    }

    .main {
        background: var(--bg-main);
        min-height: 100vh;
        padding-bottom: 30px;
    }

    /* =========================
      CARD
  ========================= */
    .card {
        background: var(--bg-card);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, .06);
        box-shadow: 0 4px 25px rgba(0, 0, 0, .18);
    }

    .card-title {
        color: #fff;
        font-weight: 700;
    }

    .card-title span {
        color: #8fb3ff;
        font-size: 13px;
    }

    /* =========================
      FILTER
  ========================= */
    .filter-card {
        overflow: hidden;
    }

    .month-wrapper {
        position: relative;
    }

    .month-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #8fb3ff;
        font-size: 18px;
        z-index: 2;
    }

    .month-input {
        background: var(--bg-input) !important;
        border: 1px solid var(--border) !important;
        color: #fff !important;
        min-height: 52px;
        border-radius: 14px;
        padding-left: 45px;
        font-weight: 600;
    }

    .month-input:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 .2rem rgba(76, 141, 255, .20) !important;
    }

    .month-input::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 1;
        filter: invert(1);
    }

    /* =========================
      BUTTON
  ========================= */
    .btn-filter {
        min-height: 52px;
        border-radius: 14px;
        padding: 0 22px;
        font-weight: 600;
        border: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
    }

    .btn-success {
        background: linear-gradient(135deg, #059669, #10b981) !important;
    }

    /* =========================
      TABLE
  ========================= */
    .table-responsive {
        border-radius: 18px;
        overflow: auto;
    }

    .kepegawaian-table {
        margin: 0;
        color: #fff;
        min-width: max-content;
    }

    .kepegawaian-table thead th {
        background: #102a57;
        color: #cfe1ff;
        border-color: #17376f;
        font-size: 13px;
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
    }

    .kepegawaian-table td {
        border-color: #17376f;
        vertical-align: middle;
    }

    .pegawai-col {
        min-width: 240px;
        position: sticky;
        left: 0;
        z-index: 3;
        background: #0b2147 !important;
    }

    .tanggal-col {
        min-width: 65px;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .pegawai-info strong {
        color: #fff;
        font-size: 14px;
    }

    .pegawai-info small {
        display: block;
        color: #8fb3ff;
        margin-top: 3px;
    }

    /* =========================
      SELECT STATUS
  ========================= */
    .status-select {
        min-width: 70px;
        border-radius: 10px;
        border: 1px solid #29529a !important;
        background: #081a38 !important;
        color: #fff !important;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .status-select:focus {
        border-color: #4c8dff !important;
        box-shadow: none !important;
    }

    /* =========================
      SUMMARY
  ========================= */
    .summary-box {
        border-radius: 14px;
        padding: 10px;
        text-align: center;
        min-width: 70px;
    }

    .summary-box h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }

    .summary-masuk {
        background: rgba(34, 197, 94, .14);
        border: 1px solid rgba(34, 197, 94, .35);
        color: #4ade80;
    }

    .summary-izin {
        background: rgba(250, 204, 21, .14);
        border: 1px solid rgba(250, 204, 21, .35);
        color: #fde047;
    }

    .summary-sakit {
        background: rgba(6, 182, 212, .14);
        border: 1px solid rgba(6, 182, 212, .35);
        color: #67e8f9;
    }

    .summary-alpha {
        background: rgba(239, 68, 68, .14);
        border: 1px solid rgba(239, 68, 68, .35);
        color: #f87171;
    }

    /* =========================
      ALERT
  ========================= */
    .alert {
        border-radius: 14px;
        border: none;
    }

    .alert-success {
        background: rgba(16, 185, 129, .14);
        color: #6ee7b7;
        border: 1px solid rgba(16, 185, 129, .35);
    }

    /* =========================
      SCROLLBAR
  ========================= */
    ::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }

    ::-webkit-scrollbar-thumb {
        background: #29529a;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-track {
        background: #081a38;
    }

/* =========================
   BUTTON EXPORT EXCEL
========================= */

.btn-export-excel {
    min-height: 52px;
    padding: 0 22px;
    border-radius: 14px;

    background: rgba(34, 197, 94, .14);
    border: 1px solid rgba(34, 197, 94, .35);

    color: #4ade80 !important;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    transition: .2s ease;
}

.btn-export-excel i {
    font-size: 18px;
}

.btn-export-excel:hover {
    background: rgba(34, 197, 94, .22);
    border-color: #22c55e;
    color: #86efac !important;

    transform: translateY(-1px);
}

.btn-export-excel:active {
    transform: scale(.98);
}
</style>

<?php
$today = date('Y-m-d');
?>

<main id="main" class="main">

    <div class="pagetitle">

        <h1 class="text-white">
            Data Kepegawaian
        </h1>

        <nav>
            <ol class="breadcrumb">

                <li class="breadcrumb-item">
                    <a href="<?= base_url('user') ?>">
                        Home
                    </a>
                </li>

                <li class="breadcrumb-item active text-light">
                    Kepegawaian
                </li>

            </ol>
        </nav>

    </div>

    <section class="section">

        <?php if ($this->session->flashdata('success')) : ?>

            <div class="alert alert-success alert-dismissible fade show">

                <?= $this->session->flashdata('success') ?>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>

        <!-- FILTER -->
        <div class="card filter-card mb-4">

            <div class="card-body p-4">

                <div class="mb-4">

                    <h5 class="card-title mb-1">
                        Filter Absensi Pegawai
                    </h5>

                    <div class="text-light small">
                        Pilih bulan untuk melihat data absensi pegawai
                    </div>

                </div>

                <form
                    method="get"
                    action="<?= base_url('user/kepegawaian') ?>"
                    class="row g-3 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label text-light fw-semibold">
                            Pilih Bulan
                        </label>

                        <div class="month-wrapper">

                            <input
                                type="month"
                                name="bulan"
                                class="form-control month-input"
                                value="<?= $bulan ?>">

                        </div>

                    </div>

                    <div class="col-md-8">

                        <div class="d-flex gap-2 flex-wrap">

                            <button
                                type="submit"
                                class="btn btn-primary btn-filter">

                                <i class="bi bi-search"></i>
                                Tampilkan Data

                            </button>

                            <a href="<?= base_url('user/export_kepegawaian?bulan=' . $bulan) ?>"
                                class="btn-export-excel">

                                <i class="bi bi-file-earmark-excel"></i>

                                <span>Export Excel</span>

                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </div>

        <!-- TABLE -->
        <div class="card filter-card">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

                    <div>

                        <h5 class="card-title mb-1">
                            Data Absensi Pegawai
                        </h5>

                        <div class="text-light small">

                            Bulan :

                            <strong class="text-info">
                                <?= date('F Y', strtotime($bulan . '-01')) ?>
                            </strong>

                        </div>

                    </div>

                </div>

                <form method="post" action="<?= base_url('user/simpan_absensi') ?>">
                    <input type="hidden" name="bulan" value="<?= $bulan ?>">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle kepegawaian-table">

                        <thead>

                            <tr>

                                <th class="pegawai-col">
                                    Pegawai
                                </th>

                                <?php
                                $jumlah_hari = date('t', strtotime($bulan . '-01'));

                                for ($i = 1; $i <= $jumlah_hari; $i++) :
                                ?>

                                    <th class="tanggal-col">
                                        <?= $i ?>
                                    </th>

                                <?php endfor; ?>

                                <th class="bg-success text-white">
                                    M
                                </th>

                                <th class="bg-warning text-dark">
                                    I
                                </th>

                                <th class="bg-info text-dark">
                                    S
                                </th>

                                <th class="bg-danger text-white">
                                    A
                                </th>
                                <th style="background:#6b7280; color:white;">
   								 	L
								</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($pegawai as $p) : ?>

                                <?php
                                $masuk = 0;
                                $izin  = 0;
                                $sakit = 0;
                                $alpha = 0;
                                $libur = 0;
                                ?>

                                <tr>

                                    <td class="pegawai-col">

                                        <div class="pegawai-info">

                                            <strong>
                                                <?= $p->nama_user ?>
                                            </strong>

                                            <small>
                                                ID USER :
                                                <?= $p->id_user ?>
                                            </small>

                                        </div>

                                    </td>

                                    <?php
                                    for ($i = 1; $i <= $jumlah_hari; $i++) :

                                        $tgl = $bulan . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);

                                        $status = '';

                                        foreach ($absensi as $a) {

                                            if (
                                                $a->id_user == $p->id_user &&
                                                $a->tanggal == $tgl
                                            ) {

                                                $status = $a->status;
                                                break;
                                            }
                                        }

                                        if ($status == 'masuk') $masuk++;
                                        if ($status == 'izin') $izin++;
                                        if ($status == 'sakit') $sakit++;
                                        if ($status == 'alpha') $alpha++;
                                 	    if ($status == 'libur') $libur++;
                                    ?>

                                        <td class="text-center">

                                                <select
                                                    name="status[<?= $p->id_user ?>][<?= $tgl ?>]"
                                                    class="form-select form-select-sm status-select">

                                                    <option value="">
                                                        -
                                                    </option>

                                                    <option value="masuk"
                                                        <?= ($status == 'masuk') ? 'selected' : '' ?>>
                                                        M
                                                    </option>

                                                    <option value="izin"
                                                        <?= ($status == 'izin') ? 'selected' : '' ?>>
                                                        I
                                                    </option>

                                                    <option value="sakit"
                                                        <?= ($status == 'sakit') ? 'selected' : '' ?>>
                                                        S
                                                    </option>

                                                    <option value="alpha"
                                                        <?= ($status == 'alpha') ? 'selected' : '' ?>>
                                                        A
                                                    </option>
                                                    <option value="libur"
    													<?= ($status == 'libur') ? 'selected' : '' ?>>
   													    L
													</option>

                                                </select>

                                        </td>

                                    <?php endfor; ?>

                                    <td>
                                        <div class="summary-box summary-masuk">
                                            <h4><?= $masuk ?></h4>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="summary-box summary-izin">
                                            <h4><?= $izin ?></h4>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="summary-box summary-sakit">
                                            <h4><?= $sakit ?></h4>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="summary-box summary-alpha">
                                            <h4><?= $alpha ?></h4>
                                        </div>
                                    </td>
                                    <td>
  									  <div class="summary-box" style="
      								  background: rgba(107,114,128,.14);
       								  border: 1px solid rgba(107,114,128,.35);
     							      color: #9ca3af;
  									  ">
       									  <h4><?= $libur ?></h4>
  									  </div>
								   </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-success btn-filter">
                        <i class="bi bi-save"></i>
                        Simpan Absensi
                    </button>
                </div>

                </form>

            </div>

        </div>

    </section>

</main>

