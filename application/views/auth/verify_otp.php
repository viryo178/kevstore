<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Verifikasi OTP</title>

    <link href="<?= base_url() ?>assets/img/favicon.png" rel="icon">

    <!-- Vendor CSS Files -->
    <link href="<?= base_url() ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="<?= base_url() ?>assets/css/style.css" rel="stylesheet">

</head>

<body>

    <main>
        <div class="container">

            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">

                <div class="container">

                    <div class="row justify-content-center">

                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                            <div class="card mb-3">

                                <div class="card-body">

                                    <div class="pt-4 pb-2">

                                        <h5 class="card-title text-center pb-0 fs-4">
                                            Verifikasi OTP
                                        </h5>

                                        <p class="text-center small">
                                            Masukkan kode OTP yang dikirim ke WhatsApp
                                        </p>

                                    </div>

                                    <!-- ALERT -->

                                    <?php if ($this->session->flashdata('error')) : ?>

                                        <div class="alert alert-danger">
                                            <?= $this->session->flashdata('error') ?>
                                        </div>

                                    <?php endif; ?>

                                    <!-- FORM -->

                                    <form class="row g-3"
                                        action="<?= base_url('check-otp') ?>"
                                        method="post">

                                        <div class="col-12">

                                            <label class="form-label">
                                                Masukkan OTP
                                            </label>

                                            <input type="text"
                                                name="otp"
                                                class="form-control"
                                                placeholder=""
                                                required>

                                        </div>

                                        <div class="col-12">

                                            <button class="btn btn-primary w-100"
                                                type="submit">

                                                Verifikasi OTP

                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

        </div>
    </main>

    <!-- Vendor JS Files -->
    <script src="<?= base_url() ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Template Main JS File -->
    <script src="<?= base_url() ?>assets/js/main.js"></script>

</body>

</html>