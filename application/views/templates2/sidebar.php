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

  // DASHBOARD ACTIVE
  $is_dashboard = ($class == 'user' && $method == 'index');

  // KELOLA AKUN ACTIVE
  $is_kelola = in_array($method, [
    'kelola_akun',
    'tambah_akun',
    'edit_akun',
    'detail_akun'
  ]);

  $akun_produk = strtolower(trim((string) $this->input->get('search_akun')));

  // PROFILE ACTIVE
  $is_profile = ($method == 'profile');

  // NOTIF ACTIVE
  $is_notif = ($method == 'notifications');

  // AKTIVITAS ACTIVE
  $is_aktivitas = ($method == 'aktivitas');

  ?>

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Dashboard -->
    <li class="nav-item">

      <a class="nav-link <?= $is_dashboard ? '' : 'collapsed' ?>"
        href="<?= base_url('user') ?>">

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
        data-bs-toggle="collapse"
        href="#kelola-akun-nav"
        aria-expanded="<?= $is_kelola ? 'true' : 'false' ?>">

        <i class="bi bi-menu-button-wide"></i>
        <span>Kelola Akun</span>
        <i class="bi bi-chevron-down ms-auto"></i>

      </a>

      <ul id="kelola-akun-nav" class="nav-content collapse <?= $is_kelola ? 'show' : '' ?>">
        <li>
          <a href="<?= base_url('user/kelola_akun?search_akun=Leonardo&product=LEONARDO') ?>" class="<?= $akun_produk === 'leonardo' ? 'active' : '' ?>">
            <i class="bi bi-circle"></i><span>Leonardo</span>
          </a>
        </li>
        <li>
          <a href="<?= base_url('user/kelola_akun?search_akun=Spotify&product=SPOTIFY') ?>" class="<?= $akun_produk === 'spotify' ? 'active' : '' ?>">
            <i class="bi bi-circle"></i><span>Spotify</span>
          </a>
        </li>
        <li>
          <a href="<?= base_url('user/kelola_akun?search_akun=Grok&product=GROK') ?>" class="<?= $akun_produk === 'grok' ? 'active' : '' ?>">
            <i class="bi bi-circle"></i><span>Grok</span>
          </a>
        </li>
      </ul>

    </li>

    <!-- Profile -->
    <li class="nav-item">

      <a class="nav-link <?= $is_profile ? '' : 'collapsed' ?>"
        href="<?= base_url('user/profile') ?>">

        <i class="bi bi-person"></i>
        <span>Profile</span>

      </a>

    </li>

    <!-- Notifikasi -->
    <li class="nav-item">

      <a class="nav-link <?= $is_notif ? '' : 'collapsed' ?>"
        href="<?= base_url('user/notifications') ?>">

        <i class="bi bi-bell"></i>
        <span>Notifikasi</span>

      </a>

    </li>

    <!-- Aktivitas -->
    <li class="nav-item">

      <a class="nav-link <?= $is_aktivitas ? '' : 'collapsed' ?>"
        href="<?= base_url('user/aktivitas') ?>">

        <i class="bi bi-clock"></i>
        <span>Aktivitas</span>

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
