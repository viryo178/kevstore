<style>
  .modal {
    background: rgba(0, 0, 0, 0.6);
  }

  .modal-content {
    background: #0b1f3a;
    color: #fff;
  }
</style>

<body>

  <!-- ===== ALERT FLOAT ===== -->
  <div class="container-fluid pt-3"
    style="position: fixed; top:0; left:0; right:0; z-index:1050;">

    <?php if ($this->session->flashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <strong>Sukses!</strong> <?= $this->session->flashdata('success') ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <strong>Error!</strong> <?= $this->session->flashdata('error') ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

  </div>

  <?php
  $role_prefix = $this->session->userdata('tipe_user') === 'user' ? 'user' : 'admin';
  $today = date('Y-m-d');
  $expired_date = "CASE WHEN expired_password REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN expired_password ELSE NULL END";

  $expiring_accounts = $this->db
    ->where($expired_date . ' IS NOT NULL', null, false)
    ->where(
      $expired_date . ' <= ' . $this->db->escape($today),
      null,
      false
    )
    ->get('akun')
    ->result();

  $status_problem = $this->db
    ->where_in('status', ['deactived', 'verif', 'ban', 'disable_x', 'disable_email'])
    ->get('akun')
    ->result();

  $notif_count = count($expiring_accounts) + count($status_problem);
  ?>

  <!-- ===== HEADER ===== -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <!-- LOGO -->
    <div class="d-flex align-items-center">
      <a href="<?= base_url($role_prefix) ?>" class="logo d-flex align-items-center">
        <img src="<?= base_url() ?>assets/img/logo.png">
        <span>Kevstore</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>

    <!-- SEARCH -->
    <div class="search-bar">
      <form class="search-form d-flex align-items-center">
        <input type="text" placeholder="Cari akun...">
      </form>
    </div>

    <!-- NAV -->
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <!-- NOTIFIKASI -->
        <li class="nav-item dropdown">

          <a class="nav-link nav-icon" data-bs-toggle="dropdown">
            <i class="bi bi-bell"></i>
            <?php
            $today = date('Y-m-d');
            ?>
            <?php if ($notif_count > 0): ?>
              <span class="badge bg-primary badge-number">
                <?= $notif_count ?>
              </span>
            <?php endif; ?>

          </a>

          <ul class="dropdown-menu dropdown-menu-end notifications">

            <li class="dropdown-header">Notifikasi Sistem</li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <!-- EXPIRED -->
            <!-- EXPIRED / HAMPIR EXPIRED -->
            <?php foreach ($expiring_accounts as $ea): ?>

              <li>

                <a href="javascript:void(0)"
                  class="notification-item d-flex notif-open"
                  data-id="<?= $ea->id_akun ?>">

                  <?php if ($ea->expired_password < $today): ?>

                    <i class="bi bi-x-circle text-danger"></i>

                    <div>
                      <h4>Password Expired</h4>

                      <p><?= $ea->nama_akun ?></p>

                      <small>
                        <?= date('d M Y', strtotime($ea->expired_password)) ?>
                      </small>
                    </div>

                  <?php else: ?>

                    <i class="bi bi-key-fill text-warning"></i>

                    <div>
                      <h4>Expired Hari Ini</h4>

                      <p><?= $ea->nama_akun ?></p>

                      <small>
                        <?= date('d M Y', strtotime($ea->expired_password)) ?>
                      </small>
                    </div>

                  <?php endif; ?>

                </a>

              </li>

              <li>
                <hr class="dropdown-divider">
              </li>

            <?php endforeach; ?>
            <!-- STATUS PROBLEM -->
            <?php foreach ($status_problem as $sp): ?>
              <li>
                <a href="javascript:void(0)"
                  class="notification-item d-flex notif-open"
                  data-id="<?= $sp->id_akun ?>">

                  <i class="bi bi-exclamation-triangle text-danger"></i>

                  <div>
                    <h4>Status Bermasalah</h4>
                    <p><?= $sp->nama_akun ?> (<?= ucwords(str_replace('_', ' ', (string) $sp->status)) ?>)</p>
                  </div>

                </a>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li>
            <?php endforeach; ?>

            <?php if (empty($expiring_accounts) && empty($status_problem)): ?>
              <li>
                <a class="notification-item">
                  <i class="bi bi-check-circle text-success"></i>
                  <div>
                    <h4>Tidak ada notifikasi</h4>
                    <p>Semua akun normal</p>
                  </div>
                </a>
              </li>
            <?php endif; ?>

          </ul>
        </li>

        <!-- PROFILE -->
        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0"
            href="#"
            data-bs-toggle="dropdown">

            <img src="<?= base_url() ?>assets/img/profile-img.jpg"
              class="rounded-circle">

            <span class="d-none d-md-block dropdown-toggle ps-2">
              <?= $this->session->userdata('nama_user') ?>
            </span>

          </a>

        </li>

      </ul>
    </nav>

  </header>

  <!-- ===== MODAL EDIT (SAMA PERSIS KELOLA AKUN) ===== -->
  <div class="modal fade" id="editModal" tabindex="-1">

    <div class="modal-dialog modal-lg">
      <div class="modal-content">

        <div class="modal-header border-0">
          <h5 class="modal-title">Edit Akun</h5>
          <button type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="modal"></button>
        </div>

        <form id="formEdit" method="POST" action="<?= base_url($role_prefix . '/edit_akun/') ?>">

          <input type="hidden" name="id_akun" id="id_akun">

          <div class="modal-body">

            <div class="row">

              <div class="col-md-6 mb-3">
                <label>Nama Akun</label>
                <input type="text" name="nama_akun" class="form-control">
              </div>

              <div class="col-md-6 mb-3">
                <label>Kategori</label>
                <select name="kategori" class="form-select">
                  <option value="private">Private</option>
                  <option value="sharing">Sharing</option>
                  <option value="belum_terjual">Belum Terjual</option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label>Status</label>
                <select name="status" class="form-select">
                  <option value="aktif">Aktif</option>
                  <option value="verif">Verif</option>
                  <option value="deactived">Deactived</option>
                  <option value="ban">Ban</option>
                  <option value="disable_x">Disable X</option>
                  <option value="disable_email">Disable Email</option>
                  <option value="terjual">Terjual</option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control">
              </div>

              <div class="col-md-6 mb-3">
                <label>Password</label>
                <input type="text" name="password" class="form-control">
              </div>

              <div class="col-md-6 mb-3">
                <label>Website</label>
                <input type="text" name="website" class="form-control">
              </div>

              <div class="col-md-6 mb-3">
                <label>Max User</label>
                <input type="number" name="max_user" class="form-control">
              </div>

              <div class="col-md-6 mb-3">
                <label>Expired Password</label>
                <input type="date" name="expired_password" class="form-control">
              </div>

              <div class="col-md-12 mb-3">
                <label>Note</label>
                <textarea name="note" rows="3" class="form-control"></textarea>
              </div>

            </div>

          </div>

          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Batal
            </button>

            <button type="submit" class="btn btn-warning">
              Update
            </button>
          </div>

        </form>

      </div>
    </div>

  </div>

  <!-- ===== SCRIPT ===== -->
  <script>
    document.querySelectorAll('.notif-open').forEach(btn => {

      btn.addEventListener('click', async function() {

        const id = this.dataset.id;

        try {

          const res = await fetch("<?= base_url($role_prefix . '/get_akun/') ?>" + id);

          const json = await res.json();

          if (json.status !== 'success') {
            alert(json.message);
            return;
          }

          const d = json.data;

          document.getElementById('id_akun').value = d.id_akun;
          document.querySelector('[name="nama_akun"]').value = d.nama_akun;
          document.querySelector('[name="kategori"]').value = 'belum_terjual';
          document.querySelector('[name="status"]').value = 'aktif';
          document.querySelector('[name="username"]').value = d.username;
          document.querySelector('[name="password"]').value = d.password;
          document.querySelector('[name="website"]').value = d.website;
          document.querySelector('[name="max_user"]').value = 0;
          document.querySelector('[name="expired_password"]').value = '';
          document.querySelector('[name="note"]').value = d.note;

          new bootstrap.Modal(
            document.getElementById('editModal')
          ).show();

        } catch (e) {

          console.error(e);

          alert("Gagal ambil data dari server");

        }

      });

    });

    document.getElementById('formEdit').addEventListener('submit', async function(e) {

      e.preventDefault();

      const formData = new FormData(this);

      try {

        const res = await fetch("<?= base_url($role_prefix . '/update_akun_ajax') ?>", {
          method: "POST",
          body: formData
        });

        const json = await res.json();

        if (json.status === 'success') {

          const modal = bootstrap.Modal.getInstance(
            document.getElementById('editModal')
          );

          if (modal) {
            modal.hide();
          }

          alert(json.message || 'Akun berhasil diupdate');

        } else {

          alert(json.message);

        }

      } catch (e) {

        console.error(e);

        alert("Terjadi error server");

      }

    });
  </script>

</body>
