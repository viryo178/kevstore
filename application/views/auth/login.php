<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Login - KevStore</title>
  <meta content="KevStore login" name="description">
  <meta content="kevstore, login, admin" name="keywords">

  <link href="<?= base_url() ?>assets/img/favicon.png" rel="icon">
  <link href="<?= base_url() ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="<?= base_url() ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --ks-bg: #06152d;
      --ks-bg-deep: #031021;
      --ks-surface: rgba(11, 31, 58, 0.84);
      --ks-surface-strong: rgba(16, 37, 68, 0.92);
      --ks-border: rgba(158, 184, 255, 0.18);
      --ks-border-soft: rgba(255, 255, 255, 0.08);
      --ks-text: #f7faff;
      --ks-muted: #b6c9ec;
      --ks-soft: #9db8ff;
      --ks-primary: #6366f1;
      --ks-primary-strong: #4f46e5;
      --ks-teal: #39c2b7;
      --ks-danger: #ffb4b4;
    }

    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      margin: 0;
      color: var(--ks-text);
      font-family: "Poppins", Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:
        linear-gradient(140deg, rgba(3, 16, 33, 0.96) 0%, rgba(6, 21, 45, 0.98) 48%, rgba(8, 42, 58, 0.96) 100%),
        #06152d;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
      background-size: 52px 52px;
      mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.74), transparent 82%);
    }

    .login-shell {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 0;
    }

    .login-layout {
      width: 100%;
      min-height: 100vh;
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(380px, 480px);
      overflow: hidden;
      border: 0;
      border-radius: 0;
      background: rgba(4, 15, 31, 0.72);
      box-shadow: 0 28px 80px rgba(0, 0, 0, 0.36);
      backdrop-filter: blur(18px);
    }

    .brand-side {
      position: relative;
      min-height: 100vh;
      padding: clamp(30px, 4vw, 58px);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
      background:
        linear-gradient(150deg, rgba(16, 37, 68, 0.9), rgba(7, 26, 53, 0.92)),
        url("<?= base_url() ?>assets/img/card.jpg") center/cover;
      isolation: isolate;
    }

    .brand-side::before {
      content: "";
      position: absolute;
      inset: 0;
      z-index: -1;
      background:
        linear-gradient(90deg, rgba(3, 16, 33, 0.88), rgba(3, 16, 33, 0.46)),
        linear-gradient(180deg, rgba(79, 70, 229, 0.18), rgba(57, 194, 183, 0.12));
    }

    .brand-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
    }

    .brand-mark {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .brand-logo {
      width: 46px;
      height: 46px;
      border-radius: 8px;
      display: grid;
      place-items: center;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .brand-logo img {
      width: 30px;
      height: 30px;
      object-fit: contain;
    }

    .brand-name {
      font-size: 24px;
      font-weight: 800;
      letter-spacing: 0;
      line-height: 1;
    }

    .brand-subtitle {
      margin-top: 5px;
      color: var(--ks-muted);
      font-size: 13px;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      min-height: 34px;
      padding: 0 12px;
      border: 1px solid rgba(157, 184, 255, 0.24);
      border-radius: 999px;
      color: #dbe6ff;
      background: rgba(7, 26, 53, 0.58);
      font-size: 13px;
      white-space: nowrap;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: var(--ks-teal);
      box-shadow: 0 0 0 4px rgba(57, 194, 183, 0.18);
    }

    .brand-copy {
      max-width: 560px;
      padding: 80px 0 22px;
    }

    .eyebrow {
      margin-bottom: 14px;
      color: var(--ks-soft);
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .brand-copy h1 {
      margin: 0;
      font-size: clamp(40px, 5vw, 62px);
      line-height: 1.02;
      font-weight: 850;
      letter-spacing: 0;
    }

    .brand-copy p {
      max-width: 500px;
      margin: 22px 0 0;
      color: #d7e4fb;
      font-size: 16px;
      line-height: 1.72;
    }

    .brand-metrics {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 34px;
    }

    .metric {
      min-height: 82px;
      padding: 15px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      background: rgba(3, 16, 33, 0.44);
    }

    .metric strong {
      display: block;
      color: #ffffff;
      font-size: 22px;
      line-height: 1.1;
    }

    .metric span {
      display: block;
      margin-top: 8px;
      color: var(--ks-muted);
      font-size: 12px;
      line-height: 1.35;
    }

    .form-side {
      display: flex;
      align-items: center;
      padding: clamp(30px, 4vw, 58px);
      background:
        linear-gradient(180deg, rgba(16, 37, 68, 0.82), rgba(7, 26, 53, 0.92)),
        var(--ks-surface);
      border-left: 1px solid var(--ks-border-soft);
    }

    .login-panel {
      width: 100%;
    }

    .mobile-brand {
      display: none;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
    }

    .login-heading {
      margin-bottom: 24px;
    }

    .login-heading .caption {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
      color: var(--ks-soft);
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .login-heading h2 {
      margin: 0;
      color: var(--ks-text);
      font-size: 30px;
      font-weight: 820;
      letter-spacing: 0;
    }

    .login-heading p {
      margin: 10px 0 0;
      color: var(--ks-muted);
      line-height: 1.6;
      font-size: 14px;
    }

    .alert-soft {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 20px;
      padding: 12px 14px;
      border: 1px solid rgba(255, 180, 180, 0.28);
      border-radius: 8px;
      color: #ffe3e3;
      background: rgba(134, 35, 35, 0.22);
      font-size: 14px;
    }

    .form-label {
      color: #dce6ff;
      font-weight: 700;
      margin-bottom: 8px;
      font-size: 14px;
    }

    .input-group {
      min-height: 52px;
      border: 1px solid rgba(157, 184, 255, 0.2);
      border-radius: 8px;
      overflow: hidden;
      background: rgba(3, 16, 33, 0.46);
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .input-group:focus-within {
      border-color: rgba(99, 102, 241, 0.8);
      background: rgba(3, 16, 33, 0.62);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.16);
    }

    .input-group-text,
    .form-control {
      border: 0;
      color: #ecf4ff;
      background: transparent;
    }

    .input-group-text {
      color: var(--ks-soft);
      padding: 0 15px;
      font-size: 18px;
    }

    .form-control {
      min-height: 50px;
      padding: 12px 14px 12px 0;
      font-size: 15px;
    }

    .form-control:focus {
      color: #ffffff;
      background: transparent;
      box-shadow: none;
    }

    .form-control::placeholder {
      color: #8298bc;
      opacity: 1;
    }

    .invalid-feedback {
      color: var(--ks-danger);
      padding-left: 2px;
    }

    .btn-login {
      min-height: 52px;
      margin-top: 6px;
      border: 0;
      border-radius: 8px;
      color: #fff;
      font-weight: 800;
      background: linear-gradient(135deg, var(--ks-primary-strong), var(--ks-primary));
      box-shadow: 0 14px 26px rgba(79, 70, 229, 0.28);
      transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
    }

    .btn-login:hover,
    .btn-login:focus {
      color: #fff;
      filter: brightness(1.04);
      box-shadow: 0 16px 30px rgba(79, 70, 229, 0.34);
      transform: translateY(-1px);
    }

    .forgot-link {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      color: #b7c8ff;
      text-decoration: none;
      font-size: 14px;
    }

    .forgot-link:hover {
      color: #ffffff;
      text-decoration: none;
    }

    .security-note {
      margin-top: 26px;
      padding-top: 18px;
      border-top: 1px solid var(--ks-border-soft);
      display: flex;
      align-items: center;
      gap: 10px;
      color: var(--ks-muted);
      font-size: 13px;
      line-height: 1.5;
    }

    .security-note i {
      color: var(--ks-teal);
      font-size: 18px;
    }

    @media (max-width: 991.98px) {
      .login-layout {
        grid-template-columns: 1fr;
        max-width: none;
        min-height: 100vh;
      }

      .brand-side {
        display: none;
      }

      .form-side {
        border-left: 0;
      }

      .mobile-brand {
        display: flex;
      }
    }

    @media (max-width: 575.98px) {
      .login-shell {
        padding: 0;
        align-items: stretch;
      }

      .login-layout {
        max-width: none;
        min-height: 100vh;
      }

      .form-side {
        padding: 28px 22px;
      }

      .login-heading h2 {
        font-size: 26px;
      }
    }
  </style>
</head>

<body>
  <main class="login-shell">
    <div class="login-layout">
      <section class="brand-side" aria-label="KevStore">
        <div class="brand-top">
          <div class="brand-mark">
            <div class="brand-logo" aria-hidden="true">
              <img src="<?= base_url() ?>assets/img/logo.png" alt="">
            </div>
            <div>
              <div class="brand-name">KevStore</div>
              <div class="brand-subtitle">Digital account workspace</div>
            </div>
          </div>
          <div class="status-pill">
            <span class="status-dot"></span>
            Sistem aktif
          </div>
        </div>

        <div class="brand-copy">
          <div class="eyebrow">Dashboard Operasional</div>
          <h1>Kelola akun digital dengan ritme yang lebih rapi.</h1>
          <p>Masuk untuk memantau stok, akun expired, aktivitas admin, dan notifikasi penting dalam satu ruang kerja yang tenang.</p>

          <div class="brand-metrics" aria-label="Ringkasan fitur">
            <div class="metric">
              <strong>24/7</strong>
              <span>Monitoring akun</span>
            </div>
            <div class="metric">
              <strong>WA</strong>
              <span>Notifikasi otomatis</span>
            </div>
            <div class="metric">
              <strong>Log</strong>
              <span>Riwayat aktivitas</span>
            </div>
          </div>
        </div>
      </section>

      <section class="form-side" aria-label="Form login">
        <div class="login-panel">
          <div class="mobile-brand">
            <div class="brand-logo" aria-hidden="true">
              <img src="<?= base_url() ?>assets/img/logo.png" alt="">
            </div>
            <div>
              <div class="brand-name">KevStore</div>
              <div class="brand-subtitle">Digital account workspace</div>
            </div>
          </div>

          <div class="login-heading">
            <div class="caption">
              <i class="bi bi-shield-lock"></i>
              Secure Login
            </div>
            <h2>Selamat datang kembali</h2>
            <p>Gunakan akun admin atau user yang sudah terdaftar untuk masuk ke dashboard.</p>
          </div>

          <?php if ($this->session->flashdata('error')) : ?>
            <div class="alert-soft" role="alert">
              <i class="bi bi-exclamation-circle"></i>
              <span><?= $this->session->flashdata('error') ?></span>
            </div>
          <?php endif; ?>

          <form class="row g-3 needs-validation" novalidate action="<?= base_url('auth/proses_login') ?>" method="post">
            <div class="col-12">
              <label for="yourUsername" class="form-label">Username</label>
              <div class="input-group has-validation">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" id="yourUsername" placeholder="Masukkan username" autocomplete="username" required>
                <div class="invalid-feedback">Masukkan username Anda.</div>
              </div>
            </div>

            <div class="col-12">
              <label for="yourPassword" class="form-label">Password</label>
              <div class="input-group has-validation">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control" id="yourPassword" placeholder="Masukkan password" autocomplete="current-password" required>
                <div class="invalid-feedback">Masukkan password Anda.</div>
              </div>
            </div>

            <div class="col-12">
              <button class="btn btn-login w-100" type="submit">
                Masuk Dashboard <i class="bi bi-arrow-right ms-2"></i>
              </button>
            </div>

            <div class="col-12 text-center">
              <a class="forgot-link" href="<?= base_url('auth/forgot_password') ?>">
                <i class="bi bi-question-circle"></i>
                Lupa password?
              </a>
            </div>
          </form>

          <div class="security-note">
            <i class="bi bi-lock"></i>
            <span>Akses dashboard dilindungi session login dan hanya tersedia untuk akun terdaftar.</span>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script src="<?= base_url() ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= base_url() ?>assets/js/main.js"></script>
</body>

</html>
