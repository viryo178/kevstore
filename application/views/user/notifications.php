<?php
$expired_accounts = $expired_accounts ?? [];
$almost_expired = $almost_expired ?? [];
$status_problem = $status_problem ?? [];
$notification_modal_accounts = [];

foreach (array_merge($expired_accounts, $almost_expired, $status_problem) as $account) {
    if (!isset($account->id_akun)) {
        continue;
    }

    $notification_modal_accounts[$account->id_akun] = $account;
}
?>

<style>
    /* =========================
   NOTIFICATION DARK MODE
========================= */

    .notif-card {
        background: #0b1f3a !important;
        border-radius: 18px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 18px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .25);
        border: 1px solid rgba(255, 255, 255, .05);
        border-left: 5px solid transparent;
        transition: .3s;
    }

    .notif-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .35);
    }

    .notif-danger {
        border-left-color: #ef4444;
    }

    .notif-danger .notif-icon {
        background: rgba(239, 68, 68, .12);
        color: #ef4444;
        box-shadow: 0 0 15px rgba(239, 68, 68, .25),
            0 0 30px rgba(239, 68, 68, .15);
    }

    .notif-warning {
        border-left-color: #facc15;
    }

    .notif-warning .notif-icon {
        background: rgba(250, 204, 21, .12);
        color: #facc15;
        box-shadow: 0 0 15px rgba(250, 204, 21, .20),
            0 0 30px rgba(250, 204, 21, .10);
    }

    .notif-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .notif-content {
        flex: 1;
    }

    .notif-title {
        font-size: 17px;
        font-weight: 700;
        color: #ffffff !important;
        margin-bottom: 6px;
    }

    .notif-desc {
        color: #cbd5e1 !important;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .notif-info {
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #93c5fd !important;
    }

    .notif-info span {
        background: rgba(255, 255, 255, .04);
        padding: 6px 10px;
        border-radius: 10px;
    }

    .notif-action .btn {
        border-radius: 12px;
        padding: 8px 18px;
        font-weight: 600;
        border: none;
    }

    .notif-action .btn-warning {
        background: linear-gradient(135deg, #eab308, #facc15);
        color: #111827 !important;
    }

    @media(max-width:768px) {
        .notif-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .notif-action {
            width: 100%;
        }

        .notif-action .btn {
            width: 100%;
        }
    }
</style>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Notifikasi</h1>
    </div>

    <section class="section">

        <?php if (!empty($expired_accounts) || !empty($almost_expired) || !empty($status_problem)): ?>

            <div class="row">

                <!-- EXPIRED -->
                <?php foreach ($expired_accounts as $a): ?>
                    <div class="col-12 mb-3">
                        <div class="notif-card notif-danger">
                            <div class="notif-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>

                            <div class="notif-content">
                                <div class="notif-title">Password Expired</div>

                                <div class="notif-desc">
                                    Akun <strong><?= $a->nama_akun ?></strong> sudah expired.
                                </div>

                                <div class="notif-info">
                                    <span><?= $a->username ?></span>
                                    <span><?= date('d M Y', strtotime($a->expired_password)) ?></span>
                                </div>
                            </div>

                            <div class="notif-action">
                                <button class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#updateModal<?= $a->id_akun ?>">
                                    Update
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- EXPIRED HARI INI -->
                <?php foreach ($almost_expired as $a): ?>
                    <div class="col-12 mb-3">
                        <div class="notif-card notif-warning">
                            <div class="notif-icon">
                                <i class="bi bi-bell-fill"></i>
                            </div>

                            <div class="notif-content">
                                <div class="notif-title">Expired Hari Ini</div>

                                <div class="notif-desc">
                                    Akun <strong><?= $a->nama_akun ?></strong> jatuh tempo hari ini.
                                </div>

                                <div class="notif-info">
                                    <span><?= $a->username ?></span>
                                    <span><?= date('d M Y', strtotime($a->expired_password)) ?></span>
                                </div>
                            </div>

                            <div class="notif-action">
                                <button type="button" class="btn btn-warning btn-sm text-dark"
                                    data-bs-toggle="modal"
                                    data-bs-target="#updateModal<?= $a->id_akun ?>">
                                    Cek
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- STATUS PROBLEM -->
                <?php foreach ($status_problem as $a): ?>
                    <div class="col-12 mb-3">
                        <div class="notif-card notif-danger">
                            <div class="notif-icon">
                                <i class="bi bi-shield-exclamation"></i>
                            </div>

                            <div class="notif-content">
                                <div class="notif-title">Status Bermasalah</div>

                                <div class="notif-desc">
                                    Akun <strong><?= $a->nama_akun ?></strong> membutuhkan pengecekan status.
                                </div>

                                <div class="notif-info">
                                    <span><?= $a->username ?></span>
                                    <span><?= ucwords(str_replace('_', ' ', (string) $a->status)) ?></span>
                                </div>
                            </div>

                            <div class="notif-action">
                                <button class="btn btn-warning btn-sm text-dark"
                                    data-bs-toggle="modal"
                                    data-bs-target="#updateModal<?= $a->id_akun ?>">
                                    Cek
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:60px;"></i>
                    <h4>Tidak Ada Notifikasi</h4>
                </div>
            </div>

        <?php endif; ?>

    </section>
</main>


<!-- ================= MODAL UPDATE AKUN ================= -->
<?php foreach ($notification_modal_accounts as $a): ?>
    <div class="modal fade" id="updateModal<?= $a->id_akun ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <form action="<?= base_url('user/edit_akun/' . $a->id_akun) ?>" method="POST">

                    <div class="modal-header">
                        <h5 class="modal-title">Update Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="kategori" value="belum_terjual">
                        <input type="hidden" name="status" value="aktif">
                        <input type="hidden" name="website" value="<?= htmlspecialchars((string)$a->website, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="max_user" value="0">
                        <input type="hidden" name="expired_password" value="">
                        <input class="form-control mb-2" name="nama_akun" value="<?= htmlspecialchars((string)$a->nama_akun, ENT_QUOTES, 'UTF-8') ?>">
                        <input class="form-control mb-2" name="username" value="<?= htmlspecialchars((string)$a->username, ENT_QUOTES, 'UTF-8') ?>">
                        <input class="form-control mb-2" name="password" value="<?= htmlspecialchars((string)$a->password, ENT_QUOTES, 'UTF-8') ?>">
                        <label class="form-label mb-1">Note</label>
                        <textarea class="form-control mb-2" name="note" rows="3" placeholder="Catatan"><?= htmlspecialchars((string)$a->note, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary">Update</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
<?php endforeach; ?>
