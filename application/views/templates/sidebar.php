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
  $is_penjualan = ($method == 'akun_penjualan');
  $is_bin = in_array($method, ['bin', 'pulihkan_akun_bin'], true);
  $is_jenis_akun = ($method === 'jenis_akun');

  // KELOLA AKUN ACTIVE
  $is_kelola = in_array($method, [
    'kelola_akun',
    'deactived',
    'tambah_akun',
    'edit_akun',
    'detail_akun'
  ]);

  $akun_produk = strtolower(trim((string) $this->input->get('product')));
  $sidebar_products = $role_prefix === 'admin'
    ? ['LEONARDO' => 'Leonardo', 'SPOTIFY' => 'Spotify', 'GEMINI' => 'Gemini', 'ZOOM' => 'Zoom', 'ADOBE' => 'Adobe']
    : ['LEONARDO' => 'Leonardo', 'SPOTIFY' => 'Spotify', 'GROK' => 'Grok'];

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

    <?php if ($role_prefix === 'admin'): ?>
    <li class="nav-item">
      <a class="nav-link <?= $is_penjualan ? '' : 'collapsed' ?>"
        href="<?= base_url('admin/akun_penjualan') ?>">
        <i class="bi bi-graph-up-arrow"></i>
        <span>Akun Penjualan</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if ($role_prefix === 'admin'): ?>
    <li class="nav-item">
      <a class="nav-link <?= $is_bin ? '' : 'collapsed' ?>" href="<?= base_url('admin/bin') ?>">
        <i class="bi bi-trash3"></i><span>Bin</span>
      </a>
    </li>
    <?php endif; ?>

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
          <a href="<?= base_url($role_prefix . '/kelola_akun') ?>"
            class="<?= $akun_produk === '' && $method === 'kelola_akun' ? 'active' : '' ?>">
            <i class="bi bi-circle"></i>
            <span>Kelola Semua Akun</span>
          </a>
        </li>
        <?php foreach ($sidebar_products as $product_code => $product_label): ?>
          <?php $product_url = $role_prefix === 'admin'
            ? $role_prefix . '/kelola_akun?product=' . rawurlencode($product_code)
            : $role_prefix . '/kelola_akun?search_akun=' . rawurlencode($product_label) . '&product=' . rawurlencode($product_code); ?>
          <li>
            <a href="<?= base_url($product_url) ?>" class="<?= $akun_produk === strtolower($product_code) ? 'active' : '' ?>">
              <i class="bi bi-circle"></i><span><?= htmlspecialchars($product_label, ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

    </li>

    <?php if ($role_prefix === 'admin'): ?>
    <li class="nav-item">
      <a class="nav-link <?= $is_jenis_akun ? '' : 'collapsed' ?>" href="<?= base_url('admin/jenis_akun') ?>">
        <i class="bi bi-tags"></i>
        <span>Tambahkan Jenis Akun</span>
      </a>
    </li>
    <?php endif; ?>

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
