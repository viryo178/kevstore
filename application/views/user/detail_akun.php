<main id="main" class="main">

    <div class="pagetitle">
        <h1 class="text-white">Detail Akun</h1>

        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= base_url('user') ?>">
                        Home
                    </a>
                </li>

                <li class="breadcrumb-item">
                    Kelola Akun
                </li>

                <li class="breadcrumb-item active">
                    Detail
                </li>
            </ol>
        </nav>
    </div>

    <section class="section">

        <div class="row g-4">

            <!-- LEFT -->
            <div class="col-lg-8">

                <div class="card border-0 shadow-lg">

                    <div class="card-body p-4">

                        <!-- HEADER -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">

                            <div class="d-flex align-items-center gap-3">

                                <div class="dashboard-icon revenue-card">
                                    <i class="bi bi-person-fill"></i>
                                </div>

                                <div>

                                    <h3 class="text-white fw-bold mb-2">
                                        <?= $akun->nama_akun ?? '-' ?>
                                    </h3>

                                    <span class="badge bg-primary px-3 py-2">
                                        <?= ucfirst($akun->kategori ?? '-') ?>
                                    </span>

                                </div>

                            </div>

                            <a href="<?= base_url('user/kelola_akun') ?>"
                               class="btn btn-primary btn-glow text-white">

                                <i class="bi bi-arrow-left"></i>
                                Kembali

                            </a>

                        </div>

                        <!-- USERNAME -->
                        <div class="mb-2">

                            <label class="detail-label">
                                Username
                            </label>

                            <div class="detail-box">
                                <?= $akun->username ?? '-' ?>
                            </div>

                        </div>

                        <!-- PASSWORD -->
                        <div class="mb-2">

                            <label class="detail-label">
                                Password
                            </label>

                            <div class="detail-box">
                                <?= $akun->password ?? '-' ?>
                            </div>

                        </div>

                        <!-- WEBSITE -->
                        <div class="mb-2">

                            <label class="detail-label">
                                Website
                            </label>

                            <div class="detail-box">
                                <?= $akun->website ?: '-' ?>
                            </div>

                        </div>

                        <!-- NOTE -->
                        <div>

                            <label class="detail-label">
                                Note
                            </label>

                            <div class="detail-box note-box">

                                <?= $akun->note
                                    ? nl2br($akun->note)
                                    : 'Tidak ada note' ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- RIGHT -->
            <div class="col-lg-4">

                <!-- INFO -->
                <div class="card border-0 shadow-lg">

                    <div class="card-body p-4">

                        <div class="d-flex align-items-center gap-2 mb-2">

                            <div class="dashboard-icon sales-card">
                                <i class="bi bi-info-circle-fill"></i>
                            </div>

                            <h5 class="text-white fw-bold mb-0">
                                Informasi Tambahan
                            </h5>

                        </div>

                        <!-- MAX USER -->
                        <div class="mb-2">

                            <label class="detail-label">
                                Max User
                            </label>

                            <div class="detail-box">
                                <?= $akun->max_user ?? '0' ?>
                            </div>

                        </div>

                        <!-- EXPIRED -->
                        <div class="mb-2">

                            <label class="detail-label">
                                Expired Password
                            </label>

                            <div class="detail-box">

                                <?= $akun->expired_password
                                    ? date('d M Y', strtotime($akun->expired_password))
                                    : '-' ?>

                            </div>

                        </div>

                        <!-- CREATED -->
                        <div class="mb-2">

                            <label class="detail-label">
                                Created By
                            </label>

                            <div class="detail-box">
                                <?= $akun->created_by ?? '-' ?>
                            </div>

                        </div>

                        <!-- LAST EDIT -->
                        <div class="mb-2">

                            <label class="detail-label">
                                Last Edited By
                            </label>

                            <div class="detail-box">
                                <?= $akun->last_edited_by ?: '-' ?>
                            </div>

                        </div>

                        <!-- LAST EDIT AT -->
                        <div>

                            <label class="detail-label">
                                Last Edited At
                            </label>

                            <div class="detail-box">

                                <?= $akun->last_edited_at
                                    ? date('d M Y H:i', strtotime($akun->last_edited_at)) . ' WIB'
                                    : '-' ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</main>

<style>

/* ICON */
.dashboard-icon{
    width:60px;
    height:60px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
}

/* BLUE */
.sales-card{
    background:rgba(59,130,246,.15);
    color:#60a5fa;
    box-shadow:0 0 25px rgba(59,130,246,.25);
}

/* GREEN */
.revenue-card{
    background:rgba(34,197,94,.15);
    color:#4ade80;
    box-shadow:0 0 25px rgba(34,197,94,.25);
}

/* LABEL */
.detail-label{
    color:#94a3b8;
    font-size:14px;
    margin-bottom:10px;
    display:block;
    font-weight:500;
}

/* BOX */
.detail-box{
    background:#081223;
    border:1px solid rgba(255,255,255,.05);
    border-radius:14px;
    padding:14px 16px;
    color:#ffffff;
    font-weight:500;
    min-height:52px;
    display:flex;
    align-items:center;
    word-break:break-word;
}

/* NOTE */
.note-box{
    align-items:flex-start;
    line-height:1.8;
    min-height:150px;
}

/* BUTTON */
.btn-glow{
    background:linear-gradient(135deg,#2563eb,#4f46e5);
    border:none;
    border-radius:14px;
    padding:10px 18px;
    box-shadow:0 0 25px rgba(59,130,246,.35);
    font-weight:600;
}

/* CARD */
.card{
    background:linear-gradient(
        180deg,
        rgba(18, 31, 61, 0.98),
        rgba(12, 27, 58, 0.98)
    ) !important;

    border:1px solid rgba(255,255,255,.05) !important;

    border-radius:22px !important;

    overflow:hidden;
}

/* MOBILE */
@media(max-width:768px){

    .dashboard-icon{
        width:50px;
        height:50px;
        font-size:20px;
    }

}

</style>