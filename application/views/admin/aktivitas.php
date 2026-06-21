<main id="main" class="main">

  <div class="pagetitle">
    <h1 class="text-white">Detail Akun</h1>

    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="<?= base_url('admin') ?>">
            Home
          </a>
        </li>

        <li class="breadcrumb-item">
          log aktivitas
        </li>
      </ol>
    </nav>
  </div>

  <section class="section">

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Aktivitas Sistem</h5>

        <table class="table table-borderless datatable">
          <thead>
            <tr>
              <th>Akun</th>
              <th>Action</th>
              <th>By</th>
              <th>Waktu</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($activity as $a): ?>
              <tr>
                <td><?= $a->nama_akun ?? 'Unknown' ?></td>
                <td><?= $a->action ?></td>
                <td><?= $a->changed_by ?></td>
                <td><?= $a->created_at ?></td>
                <td>
                  <a href="<?= base_url('admin/hapus_activity/' . $a->id) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus log aktivitas ini?')">
                    <i class="bi bi-trash"></i> Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>

  </section>

</main>
