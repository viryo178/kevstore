<style>
.sidebar .nav-link.collapsed {
    background: transparent !important;
    color: #b6c8f3 !important;
}

.sidebar .nav-link:not(.collapsed) {
    background: #0d6efd !important;
    color: #fff !important;
}
</style>
<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <?php
  $class  = $this->router->fetch_class();
  $method = $this->router->fetch_method();
  $role_prefix = ($class === 'user') ? 'user' : 'admin';

  // DASHBOARD ACTIVE
  $is_dashboard = (($class == 'admin' || $class == 'user') && $method == 'index');

  // KELOLA AKUN ACTIVE
  $is_kelola = in_array($method, [
    'kelola_akun',
    'deactived',
    'tambah_akun',
    'edit_akun',
    'detail_akun'
  ]);

  // GANTI PASSWORD EXP ACTIVE
  $is_ganti_password_exp = ($method == 'ganti_password_exp');

  // PROFILE ACTIVE
  $is_profile = ($method == 'profile');

  // NOTIF ACTIVE
  $is_notif = ($method == 'notifications');

  // AKTIVITAS ACTIVE
  $is_aktivitas = ($method == 'aktivitas');

  // KEPEGAWAIAN ACTIVE
  $is_kepegawaian = ($method == 'kepegawaian');
  ?>

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Dashboard -->
    <li class="nav-item">

      <a class="nav-link <?= $is_dashboard ? '' : 'collapsed' ?>"
        href="<?= base_url($role_prefix) ?>">

        <i class="bi bi-grid"></i>
        <span>Dashboard</span>

      </a>

    </li>

    <!-- Chat AI -->
    <li class="nav-item">

      <a class="nav-link collapsed"
        href="<?= base_url('v2') ?>">

        <i class="bi bi-chat-dots"></i>
        <span>Chat AI</span>

      </a>

    </li>

    <!-- Kelola Akun -->
    <li class="nav-item">

      <a class="nav-link <?= $is_kelola ? '' : 'collapsed' ?>"
        href="<?= base_url($role_prefix . '/kelola_akun') ?>">

        <i class="bi bi-menu-button-wide"></i>
        <span>Kelola Akun</span>

      </a>

    </li>

    <!-- Profile -->
    <li class="nav-item">

      <a class="nav-link <?= $is_profile ? '' : 'collapsed' ?>"
        href="<?= base_url($role_prefix . '/profile') ?>">

        <i class="bi bi-person"></i>
        <span>Profile</span>

      </a>

    </li>

    <!-- Notifikasi -->
    <li class="nav-item">

      <a class="nav-link <?= $is_notif ? '' : 'collapsed' ?>"
        href="<?= base_url($role_prefix . '/notifications') ?>">

        <i class="bi bi-bell"></i>
        <span>Notifikasi</span>

      </a>

    </li>

    <!-- Aktivitas -->
    <li class="nav-item">

      <a class="nav-link <?= $is_aktivitas ? '' : 'collapsed' ?>"
        href="<?= base_url($role_prefix . '/aktivitas') ?>">

        <i class="bi bi-clock"></i>
        <span>Aktivitas</span>

      </a>

    </li>

    <!-- Kepegawaian -->
    <li class="nav-item">

      <a class="nav-link <?= $is_kepegawaian ? '' : 'collapsed' ?>"
        href="<?= base_url($role_prefix . '/kepegawaian') ?>">

        <i class="bi bi-people"></i>
        <span>Kepegawaian</span>

      </a>

    </li>

        <!-- Ganti Password Exp -->
    <li class="nav-item">

      <a class="nav-link <?= $is_ganti_password_exp ? '' : 'collapsed' ?>"
        href="<?= base_url($role_prefix . '/ganti_password_exp') ?>">

        <i class="bi bi-key"></i>
        <span>Ganti Password Exp</span>

      </a>

    </li>

    <!-- Logout -->
    <li class="nav-item">

      <a class="nav-link collapsed"
        href="<?= base_url('auth/logout') ?>">

        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>

      </a>

    </li>

  </ul>

</aside>
<!-- End Sidebar -->
