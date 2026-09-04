<style>
  .bulk-card {
    background: #0b2147;
    border: 1px solid rgba(255, 255, 255, .06);
    border-radius: 18px;
  }

  .bulk-card label {
    color: #dbe7ff;
    margin-bottom: 6px;
    font-weight: 600;
  }

  .bulk-card .form-control {
    background: #081225 !important;
    border: 1px solid #16366f !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    border-radius: 12px;
  }

  .bulk-card textarea.form-control {
    min-height: 240px;
    resize: vertical;
    caret-color: #ffffff;
    font-weight: 500;
    line-height: 1.6;
  }

  .bulk-card .form-control:focus {
    background: #081225 !important;
    border-color: #60a5fa !important;
    box-shadow: 0 0 0 3px rgba(96, 165, 250, .18) !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
  }

  .bulk-card .form-control::placeholder {
    color: #9cc3ff !important;
    -webkit-text-fill-color: #9cc3ff !important;
    opacity: 1;
  }

  .bulk-help {
    color: #8fb3e8;
    font-size: 13px;
  }

  .bulk-defaults {
    background: rgba(96, 165, 250, .12);
    border: 1px solid rgba(96, 165, 250, .18);
    border-radius: 12px;
    color: #a9c7f5;
    font-size: 13px;
    padding: 10px 12px;
  }

  .bulk-defaults strong {
    color: #dbe7ff;
  }
</style>

<main id="main" class="main">
  <?php
    $bulk_products = !empty($bulk_products) ? $bulk_products : ['SPOTIFY', 'LEONARDO', 'GEMINI', 'ZOOM', 'ADOBE'];
    $bulk_product = in_array(($bulk_product ?? 'SPOTIFY'), $bulk_products, true)
      ? ($bulk_product ?? 'SPOTIFY')
      : ($bulk_products[0] ?? 'SPOTIFY');
    $bulk_max_user = 0;
    $bulk_zoom_duration = $bulk_zoom_duration ?? null;
  ?>
  <div class="pagetitle">
    <h1>Bulk Tambah Akun</h1>
  </div>

  <section class="section">
    <div class="card bulk-card">
      <div class="card-body p-4">
        <form action="<?= base_url('admin/bulk_tambah_akun') ?>" method="POST">
          <div class="mb-4">
            <h5 class="card-title mb-1">Tambah Stok <span id="bulkProductTitle"><?= htmlspecialchars($bulk_product, ENT_QUOTES, 'UTF-8') ?></span></h5>
            <div class="bulk-help" id="bulkFormatHelp">
              <?= $bulk_product === 'ADOBE'
                ? 'Format Adobe: password akun lalu email:password akses:token:uuid; baris pemisah - boleh digunakan. Daftar email, baris Syarat & Ketentuan, lalu Akses email juga didukung. Format pemisah | yang lama tetap didukung.'
                : ($bulk_product === 'GEMINI'
                  ? 'Format Gemini: tempel daftar bernomor Email, Password, dan 2FA.'
                  : ($bulk_product === 'SPOTIFY'
                    ? 'Format Spotify: username|password atau format Email dan Password.'
                    : ($bulk_product === 'LEONARDO'
                      ? 'Format Leonardo: Akun 1, username/email, lalu pemisah. Hanya username yang disimpan.'
                      : 'Format: username|password. Satu akun ditulis dalam satu baris.'))) ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="bulk_product">Jenis Akun</label>
            <select class="form-control" id="bulk_product" name="product" required>
              <?php foreach ($bulk_products as $product): ?>
                <option value="<?= htmlspecialchars($product, ENT_QUOTES, 'UTF-8') ?>" <?= $bulk_product === $product ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst(strtolower($product)), ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3" id="bulkZoomDurationField" <?= $bulk_product === 'ZOOM' ? '' : 'hidden' ?>>
            <label for="bulk_zoom_duration">Variasi Zoom</label>
            <select class="form-control" id="bulk_zoom_duration" name="durasi_zoom" <?= $bulk_product === 'ZOOM' ? 'required' : '' ?>>
              <option value="">Pilih variasi</option>
              <option value="14_hari" <?= $bulk_zoom_duration === '14_hari' ? 'selected' : '' ?>>14 Hari</option>
              <option value="1_bulan" <?= $bulk_zoom_duration === '1_bulan' ? 'selected' : '' ?>>1 Bulan</option>
            </select>
          </div>

          <div class="mb-3" id="bulkLeonardoVariasiField" <?= $bulk_product === 'LEONARDO' ? '' : 'hidden' ?>>
            <label for="bulk_leonardo_variasi">Variasi Leonardo</label>
            <select class="form-control" id="bulk_leonardo_variasi" name="durasi_zoom" <?= $bulk_product === 'LEONARDO' ? 'required' : '' ?>>
              <option value="">Pilih variasi</option>
              <option value="seedance" <?= $bulk_zoom_duration === 'seedance' ? 'selected' : '' ?>>Seedance</option>
              <option value="8500_kredit" <?= $bulk_zoom_duration === '8500_kredit' ? 'selected' : '' ?>>8500 Kredit</option>
            </select>
          </div>

          <div class="mb-3">
            <label>Daftar Akun</label>
            <textarea
              id="bulkAccounts"
              name="bulk_accounts"
              class="form-control"
              placeholder="<?= $bulk_product === 'ADOBE'
                ? 'PasswordAkun123&#10;user@hotmail.com:passwordAkses123:token:uuid&#10;&#10;atau&#10;&#10;user1@example.com&#10;user2@example.com&#10;&#10;Syarat &amp; Ketentuan&#10;- Akses email mail.example.com'
                : ($bulk_product === 'GEMINI'
                  ? '1. Email: user1@gmail.com&#10;- Password: password123 2fa : https://totp.example/#/secret'
                  : ($bulk_product === 'SPOTIFY'
                    ? 'username1|password1&#10;&#10;atau&#10;&#10;Email : user@outlook.com&#10;Password : Premium123@'
                    : ($bulk_product === 'LEONARDO'
                      ? 'Akun 1&#10;user1@hotmail.com&#10;&#10;==================&#10;&#10;Akun 2&#10;user2@hotmail.com'
                      : 'username1|password1&#10;username2|password2'))) ?>"
              required></textarea>
          </div>

          <div class="bulk-defaults mb-4" id="bulkDefaults">
            Default: <strong>Nama Akun <?= htmlspecialchars($bulk_product, ENT_QUOTES, 'UTF-8') ?></strong>, <strong>Kategori Belum Terjual</strong>, <strong>Status Aktif</strong>, <strong>Max User <?= $bulk_max_user ?></strong>.<?= in_array($bulk_product, ['GEMINI', 'ADOBE'], true) ? ' Kolom 2FA disimpan untuk ' . htmlspecialchars(ucfirst(strtolower($bulk_product)), ENT_QUOTES, 'UTF-8') . ' dan boleh kosong.' : '' ?>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('admin/kelola_akun?product=' . rawurlencode($bulk_product)) ?>" class="btn btn-secondary">
              Batal
            </a>

            <button type="submit" class="btn btn-primary">
              Simpan Semua
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const productSelect = document.getElementById('bulk_product');
  const accountsInput = document.getElementById('bulkAccounts');
  const formatHelp = document.getElementById('bulkFormatHelp');
  const defaults = document.getElementById('bulkDefaults');
  const productTitle = document.getElementById('bulkProductTitle');
  const zoomDurationField = document.getElementById('bulkZoomDurationField');
  const zoomDuration = document.getElementById('bulk_zoom_duration');
  const leonardoVariasiField = document.getElementById('bulkLeonardoVariasiField');
  const leonardoVariasi = document.getElementById('bulk_leonardo_variasi');
  if (!productSelect || !accountsInput || !formatHelp || !defaults || !productTitle) return;

  function updateBulkFormat() {
    const product = String(productSelect.value || '').trim().toUpperCase();
    const isGemini = product === 'GEMINI';
    const isAdobe = product === 'ADOBE';
    const isSpotify = product === 'SPOTIFY';
    const isLeonardo = product === 'LEONARDO';
    const isZoom = product === 'ZOOM';
    const usesEmailFormat = isGemini || isAdobe;
    zoomDurationField.hidden = !isZoom;
    zoomDuration.required = isZoom;
    zoomDuration.disabled = !isZoom;
    if (!isZoom) zoomDuration.value = '';
    leonardoVariasiField.hidden = !isLeonardo;
    leonardoVariasi.required = isLeonardo;
    leonardoVariasi.disabled = !isLeonardo;
    if (!isLeonardo) leonardoVariasi.value = '';
    productTitle.textContent = product;
    formatHelp.textContent = isAdobe
      ? 'Format Adobe: password akun lalu email:password akses:token:uuid; baris pemisah - boleh digunakan. Daftar email, baris Syarat & Ketentuan, lalu Akses email juga didukung. Format pemisah | yang lama tetap didukung.'
      : (isGemini
        ? 'Format Gemini: tempel daftar bernomor Email, Password, dan 2FA.'
        : (isSpotify
          ? 'Format Spotify: username|password atau format Email dan Password.'
          : (isLeonardo
            ? 'Format Leonardo: Akun 1, username/email, lalu pemisah. Hanya username yang disimpan.'
            : 'Format: username|password. Satu akun ditulis dalam satu baris.')));
    accountsInput.placeholder = isAdobe
      ? 'PasswordAkun123\nuser@hotmail.com:passwordAkses123:token:uuid\n\natau\n\nuser1@example.com\nuser2@example.com\n\nSyarat & Ketentuan\n- Akses email mail.example.com'
      : (isGemini
        ? '1. Email: user1@gmail.com\n- Password: password123 2fa : https://totp.example/#/secret'
        : (isSpotify
          ? 'username1|password1\n\natau\n\nEmail : user@outlook.com\nPassword : Premium123@'
          : (isLeonardo
            ? 'Akun 1\nuser1@hotmail.com\n\n==================\n\nAkun 2\nuser2@hotmail.com'
            : 'username1|password1\nusername2|password2')));
    defaults.innerHTML = 'Default: <strong>Nama Akun ' + escapeBulkHtml(product) + '</strong>, <strong>Kategori Belum Terjual</strong>, <strong>Status Aktif</strong>, <strong>Max User 0</strong>.'
      + (usesEmailFormat ? ' Kolom 2FA disimpan untuk ' + (isGemini ? 'Gemini' : 'Adobe') + ' dan boleh kosong.' : '')
      + (isZoom ? ' Pilih variasi Zoom 14 Hari atau 1 Bulan.' : '')
      + (isLeonardo ? ' Pilih variasi Leonardo Seedance atau 8500 Kredit.' : '');
  }

  function escapeBulkHtml(value) {
    return String(value).replace(/[&<>"']/g, function (character) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character];
    });
  }

  productSelect.addEventListener('change', updateBulkFormat);
  updateBulkFormat();
});
</script>
