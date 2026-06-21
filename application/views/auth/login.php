<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Login Admin - KevStore</title>
  <meta content="KevStore admin login" name="description">
  <meta content="kevstore, login, admin" name="keywords">

  <link href="<?= base_url() ?>assets/img/favicon.png" rel="icon">
  <link href="<?= base_url() ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="<?= base_url() ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --ks-bg: #020711;
      --ks-panel: #06111f;
      --ks-panel-soft: #071a2e;
      --ks-border: #1d3554;
      --ks-text: #f6fbff;
      --ks-muted: #9bc0ee;
      --ks-blue: #3b78f2;
      --ks-blue-strong: #2f66d8;
    }

    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      margin: 0;
      color: var(--ks-text);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:
        radial-gradient(circle at 18% 30%, rgba(43, 91, 197, 0.22), transparent 34%),
        linear-gradient(115deg, #020711 0%, #07172c 42%, #001f22 100%);
    }

    .login-shell {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 40px 18px;
    }

    .login-grid {
      width: min(1080px, 100%);
      display: grid;
      grid-template-columns: minmax(0, 1.15fr) minmax(340px, 0.85fr);
      gap: 28px;
      align-items: center;
    }

    .brand-panel,
    .login-panel {
      border: 1px solid var(--ks-border);
      background: rgba(3, 12, 25, 0.82);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.38);
    }

    .brand-panel {
      min-height: 680px;
      position: relative;
      overflow: hidden;
      padding: 40px 38px;
      display: flex;
      flex-direction: column;
    }

    .brand-panel::after {
      content: "";
      position: absolute;
      right: -18%;
      bottom: -22%;
      width: 76%;
      height: 42%;
      background: linear-gradient(135deg, rgba(55, 112, 230, 0.35), rgba(6, 76, 83, 0.25));
      transform: rotate(-10deg);
      transform-origin: center;
    }

    .brand-mark {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 800;
      font-size: 25px;
      letter-spacing: 0;
    }

    .brand-logo {
      width: 48px;
      height: 48px;
      display: grid;
      place-items: center;
      color: #fff;
      font-weight: 900;
      background: linear-gradient(135deg, #4b68ff, #2455d8);
      clip-path: polygon(12% 0, 70% 0, 70% 26%, 44% 26%, 44% 100%, 12% 100%);
    }

    .hero-copy {
      position: relative;
      z-index: 1;
      max-width: 460px;
      margin-top: 102px;
    }

    .eyebrow {
      color: #5f95ff;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      margin-bottom: 14px;
    }

    .hero-copy h1 {
      margin: 0;
      font-size: clamp(46px, 5vw, 68px);
      line-height: 0.96;
      font-weight: 900;
      letter-spacing: 0;
    }

    .hero-copy p {
      margin: 26px 0 0;
      color: var(--ks-muted);
      font-size: 17px;
      line-height: 1.65;
    }

    .login-panel {
      padding: 34px;
    }

    .login-heading {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 18px;
      margin-bottom: 28px;
    }

    .login-heading span {
      display: block;
      margin-bottom: 10px;
      color: #5f95ff;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.03em;
      text-transform: uppercase;
    }

    .login-heading h2 {
      margin: 0;
      color: var(--ks-text);
      font-size: 30px;
      font-weight: 850;
      letter-spacing: 0;
    }

    .shield-icon {
      width: 48px;
      height: 48px;
      display: grid;
      place-items: center;
      color: #72a3ff;
      background: rgba(47, 102, 216, 0.22);
      font-size: 23px;
    }

    .form-label {
      color: #dcecff;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .input-group {
      border: 1px solid var(--ks-border);
      background: #09182a;
      border-radius: 6px;
      overflow: hidden;
    }

    .input-group:focus-within {
      border-color: var(--ks-blue);
      box-shadow: 0 0 0 3px rgba(59, 120, 242, 0.18);
    }

    .input-group-text,
    .form-control {
      border: 0;
      color: #dcecff;
      background: transparent;
    }

    .input-group-text {
      color: #6fa0ff;
      padding: 0 15px;
    }

    .form-control {
      min-height: 48px;
      padding: 11px 14px;
    }

    .form-control:focus {
      color: #fff;
      background: transparent;
      box-shadow: none;
    }

    .form-control::placeholder {
      color: #6f8eae;
      opacity: 1;
    }

    .btn-login {
      min-height: 50px;
      margin-top: 8px;
      border: 0;
      border-radius: 6px;
      color: #fff;
      font-weight: 800;
      background: linear-gradient(135deg, var(--ks-blue-strong), #3e7bed);
    }

    .btn-login:hover,
    .btn-login:focus {
      color: #fff;
      background: linear-gradient(135deg, #2b5ec7, #3772df);
    }

    .forgot-link {
      color: #9fc4ff;
      text-decoration: none;
      font-size: 14px;
    }

    .forgot-link:hover {
      color: #fff;
      text-decoration: underline;
    }

    .invalid-feedback {
      color: #ffb4b4;
    }

    @media (max-width: 991.98px) {
      .login-grid {
        grid-template-columns: 1fr;
      }

      .brand-panel {
        min-height: auto;
      }

      .hero-copy {
        margin-top: 64px;
      }
    }

    @media (max-width: 575.98px) {
      .login-shell {
        padding: 18px;
      }

      .brand-panel,
      .login-panel {
        padding: 26px 22px;
      }

      .hero-copy {
        margin-top: 46px;
      }

      .login-heading h2 {
        font-size: 26px;
      }
    }
  </style>
</head>

<body>
  <main class="login-shell">
    <div class="login-grid">
      <section class="brand-panel" aria-label="KevStore">
        <div class="brand-mark">
          <div class="brand-logo" aria-hidden="true">K</div>
          <div>KevStore</div>
        </div>

        <div class="hero-copy">
          <div class="eyebrow">Digital Account Management</div>
          <h1>Kelola stok, order, dan garansi dari satu dashboard.</h1>
          <p>Masuk untuk memantau akun digital, toko, laporan, dan notifikasi operasional dengan cepat.</p>
        </div>
      </section>

      <section class="login-panel" aria-label="Login admin">
        <div class="login-heading">
          <div>
            <span>Welcome Back</span>
            <h2>Login Admin</h2>
          </div>
          <div class="shield-icon" aria-hidden="true">
            <i class="bi bi-shield-lock"></i>
          </div>
        </div>

        <form class="row g-3 needs-validation" novalidate action="<?= base_url('auth/proses_login') ?>" method="post">
          <div class="col-12">
            <label for="yourUsername" class="form-label">Username</label>
            <div class="input-group has-validation">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" name="username" class="form-control" id="yourUsername" placeholder="Masukkan username" required>
              <div class="invalid-feedback">Masukkan username Anda.</div>
            </div>
          </div>

          <div class="col-12">
            <label for="yourPassword" class="form-label">Password</label>
            <div class="input-group has-validation">
              <span class="input-group-text"><i class="bi bi-key"></i></span>
              <input type="password" name="password" class="form-control" id="yourPassword" placeholder="Masukkan password" required>
              <div class="invalid-feedback">Masukkan password Anda.</div>
            </div>
          </div>

          <div class="col-12">
            <button class="btn btn-login w-100" type="submit">
              Masuk Dashboard <i class="bi bi-arrow-right ms-2"></i>
            </button>
          </div>

          <div class="col-12 text-center">
            <a class="forgot-link" href="<?= base_url('auth/forgot_password') ?>">Lupa password?</a>
          </div>
        </form>
      </section>
    </div>
  </main>

  <script src="<?= base_url() ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= base_url() ?>assets/js/main.js"></script>
</body>

</html>
