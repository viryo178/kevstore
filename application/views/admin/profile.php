<style>
  .profile-card {
    background: rgba(15, 23, 42, .75);
    border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 22px;
    backdrop-filter: blur(10px);
    overflow: hidden;
  }

  .profile-header {
    padding: 32px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, .06);
    background: linear-gradient(135deg, #0f172a, #1e3a8a);
  }

  .profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #60a5fa);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: auto;
    font-size: 34px;
    color: white;
    font-weight: 700;
    box-shadow: 0 10px 25px rgba(37, 99, 235, .35);
  }

  .profile-name {
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    margin-top: 18px;
  }

  .profile-role {
    color: #93c5fd;
    font-size: 14px;
  }

  .profile-body {
    padding: 28px;
  }

  .profile-label {
    color: #94a3b8;
    font-size: 13px;
    margin-bottom: 6px;
  }

  .profile-value {
    color: #f1f5f9;
    font-size: 15px;
    font-weight: 600;
  }

  .custom-input {
    background: #0f172a !important;
    border: 1px solid rgba(255, 255, 255, .08) !important;
    color: #fff !important;
    border-radius: 14px !important;
    min-height: 50px;
  }

  .custom-input:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .15) !important;
  }

  .btn-save {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    border: none;
    border-radius: 14px;
    min-height: 48px;
    font-weight: 600;
  }

  .section-title {
    color: #60a5fa;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 22px;
  }

  .custom-input::placeholder {
    color: #94a3b8 !important;
    opacity: 1;
  }
</style>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Profile</h1>

    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?= base_url('admin') ?>">
            Home
          </a>
        </li>

        <li class="breadcrumb-item active">
          Profile
        </li>
      </ol>
    </nav>
  </div>

  <section class="section">

    <div class="row">

      <!-- PROFILE CARD -->
      <div class="col-lg-4">

        <div class="profile-card">

          <div class="profile-header">

            <div class="profile-avatar">
              <?= strtoupper(substr($this->session->userdata('username'), 0, 1)) ?>
            </div>

            <div class="profile-name">
              <?= htmlspecialchars((string)$this->session->userdata('username')) ?>
            </div>

            <div class="profile-role">
              <?= htmlspecialchars((string)$this->session->userdata('tipe_user')) ?>
            </div>

          </div>

          <div class="profile-body">

            <div class="mb-4">

              <div class="profile-label">
                Username
              </div>

              <div class="profile-value">
                <?= htmlspecialchars((string)$this->session->userdata('username')) ?>
              </div>

            </div>

            <div class="mb-4">

              <div class="profile-label">
                Nomor HP
              </div>

              <div class="profile-value">
                <?= !empty($admin->no_hp) ? $admin->no_hp : '-' ?>
              </div>

            </div>

            <div class="mb-4">

              <div class="profile-label">
                Last Login
              </div>

              <div class="profile-value">

                <?php
                $ll = $this->session->userdata('last_login_at');

                echo $ll
                  ? date('d M Y H:i:s', strtotime($ll))
                  : '-';
                ?>

              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- FORM -->
      <div class="col-lg-8">

        <!-- UPDATE PROFILE -->
        <div class="profile-card mb-4">

          <div class="profile-body">

            <h5 class="section-title">
              Update Profile
            </h5>

            <form method="POST"
              action="<?= base_url('admin/update_profile') ?>">

              <div class="row">

                <div class="col-md-6 mb-3">

                  <label class="profile-label">
                    Username
                  </label>

                  <input
                    type="text"
                    name="username"
                    value="<?= htmlspecialchars((string)$this->session->userdata('username')) ?>"
                    class="form-control custom-input">

                </div>

                <div class="col-md-6 mb-3">

                  <label class="profile-label">
                    Nomor HP
                  </label>

                  <input
                    type="text"
                    name="no_wa"
                    value="<?= !empty($admin->no_wa) ? $admin->no_wa : '' ?>"
                    class="form-control custom-input"
                    placeholder="62xxxxxxxxxx">

                </div>

                <button type="submit"
                  class="btn btn-primary btn-save">

                  Simpan Perubahan

                </button>

            </form>

          </div>

        </div>

        <!-- PASSWORD -->
        <div class="profile-card">

          <div class="profile-body">

            <h5 class="section-title">
              Ubah Password
            </h5>

            <form method="POST"
              action="<?= base_url('admin/update_password') ?>">

              <div class="mb-3">

                <label class="profile-label">
                  Password Saat Ini
                </label>

                <input
                  type="password"
                  name="current_password"
                  class="form-control custom-input"
                  required>

              </div>

              <div class="mb-3">

                <label class="profile-label">
                  Password Baru
                </label>

                <input
                  type="password"
                  name="new_password"
                  class="form-control custom-input"
                  required>

              </div>

              <div class="mb-4">

                <label class="profile-label">
                  Konfirmasi Password
                </label>

                <input
                  type="password"
                  name="confirm_password"
                  class="form-control custom-input"
                  required>

              </div>

              <button type="submit"
                class="btn btn-primary btn-save">

                Update Password

              </button>

            </form>

          </div>

        </div>

      </div>

    </div>

  </section>

</main>