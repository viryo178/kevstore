<?php
$products = ['SPOTIFY', 'LEONARDO', 'GEMINI', 'ZOOM', 'ADOBE'];
$problemStatuses = ['deactived', 'disable_x', 'disable_email', 'ban', 'verif'];
$stats = ['total' => count($akun ?? []), 'aktif' => 0, 'bermasalah' => 0];
$unsold = array_fill_keys($products, 0);

foreach (($akun ?? []) as $account) {
    $status = strtolower(str_replace([' ', '-'], '_', trim((string) ($account->status ?? ''))));
    $product = strtoupper(trim((string) ($account->nama_akun ?? '')));
    if ($status === 'aktif') $stats['aktif']++;
    if (in_array($status, $problemStatuses, true)) $stats['bermasalah']++;
    if (isset($unsold[$product]) && ($account->kategori ?? '') === 'belum_terjual' && $status === 'aktif') {
        $unsold[$product]++;
    }
}

$metricCards = [
    ['Total Seluruh Akun', $stats['total'], 'bi-collection', 'primary', base_url('admin/kelola_akun')],
    ['Akun Aktif', $stats['aktif'], 'bi-check-circle', 'success', base_url('admin/kelola_akun?search_akun=aktif')],
    ['Akun Bermasalah', $stats['bermasalah'], 'bi-exclamation-octagon', 'danger', base_url('admin/akun_bermasalah')],
];
foreach ($products as $product) {
    $metricCards[] = [ucfirst(strtolower($product)) . ' Belum Terjual', $unsold[$product], 'bi-box-seam', 'warning', base_url('admin/kelola_akun?product=' . $product . '&search_akun=belum_terjual')];
}
?>

<style>
.admin-dashboard-grid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start}.dashboard-main-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}.metric-link{text-decoration:none}.metric-link.metric-summary{grid-column:span 4}.metric-link.metric-product{grid-column:span 2}.metric-link.metric-product:last-of-type{grid-column:span 4}.metric-card{margin:0!important;min-height:126px;border:1px solid rgba(96,165,250,.12);transition:.2s}.metric-card:hover{transform:translateY(-2px);border-color:rgba(96,165,250,.38)}.metric-card .card-body{padding:20px}.metric-product .metric-card{min-height:112px}.metric-product .card-body{padding:18px}.metric-label{color:#a9bde6;font-size:13px;font-weight:600;margin-bottom:16px}.metric-value{font-size:28px;font-weight:800;color:#fff;line-height:1}.metric-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:22px}.metric-product .metric-icon{width:42px;height:42px;border-radius:13px;font-size:20px}.metric-icon.primary{background:rgba(59,130,246,.14);color:#60a5fa}.metric-icon.success{background:rgba(34,197,94,.14);color:#4ade80}.metric-icon.danger{background:rgba(239,68,68,.14);color:#f87171}.metric-icon.warning{background:rgba(245,158,11,.14);color:#fbbf24}.dashboard-table-card{grid-column:1/-1;margin:0!important}.dashboard-notifications{margin:0!important;height:calc(100vh - 142px);min-height:520px;overflow:hidden}.dashboard-notifications .card-body{height:100%;display:flex;flex-direction:column}.notification-heading{display:flex;align-items:center;justify-content:space-between}.notification-list{overflow:auto;min-height:0;padding-right:4px;scrollbar-width:none;-ms-overflow-style:none}.notification-list::-webkit-scrollbar{display:none}.notification-count{background:#ef4444;color:#fff;border-radius:999px;min-width:24px;height:24px;padding:0 7px;display:grid;place-items:center;font-size:11px;font-weight:700}.notification-item{display:flex;gap:12px;padding:13px 0;border-bottom:1px solid rgba(148,163,184,.12)}.notification-item:last-child{border:0}.notification-item i{color:#f59e0b;font-size:18px}.notification-item strong{display:block;color:#fff;font-size:13px}.notification-item small{color:#8fa5cf}.dashboard-toolbar{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:16px}.dashboard-controls{display:flex;gap:10px;flex-wrap:wrap}.dashboard-controls select,.dashboard-controls input{background:#081225!important;border:1px solid #16366f!important;color:#fff!important;border-radius:9px;padding:8px 10px}.dashboard-controls input{min-width:220px}.dashboard-pagination{display:flex;justify-content:space-between;align-items:center;color:#8fa5cf;font-size:12px;margin-top:14px}.dashboard-pagination button{background:#12234a;color:#cfe0ff;border:1px solid #244783;border-radius:7px;padding:6px 10px;margin-left:5px}.dashboard-pagination button:disabled{opacity:.4}.empty-notification{text-align:center;padding:42px 10px;color:#8095bf}.empty-notification i{font-size:34px;color:#4ade80;display:block;margin-bottom:10px}@media(max-width:1399px){.metric-link.metric-product{grid-column:span 3}.metric-link.metric-product:last-of-type{grid-column:span 6}}@media(max-width:1199px){.admin-dashboard-grid{grid-template-columns:1fr}.dashboard-notifications{height:auto;min-height:0}.dashboard-main-grid{grid-template-columns:repeat(6,minmax(0,1fr))}.metric-link.metric-summary,.metric-link.metric-product,.metric-link.metric-product:last-of-type{grid-column:span 2}}@media(max-width:767px){.metric-link.metric-summary,.metric-link.metric-product,.metric-link.metric-product:last-of-type{grid-column:span 3}}@media(max-width:575px){.dashboard-main-grid{grid-template-columns:1fr}.metric-link.metric-summary,.metric-link.metric-product,.metric-link.metric-product:last-of-type{grid-column:1/-1}.dashboard-controls,.dashboard-controls input,.dashboard-controls select{width:100%}.dashboard-pagination{align-items:flex-start;flex-direction:column;gap:10px}}
</style>

<main id="main" class="main">
<div class="pagetitle"><h1>Dashboard</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url('admin') ?>">Admin</a></li><li class="breadcrumb-item active">Dashboard</li></ol></nav></div>

<section class="section dashboard">
  <div class="admin-dashboard-grid">
    <div class="dashboard-main-grid">
      <?php foreach ($metricCards as $index => $card): ?>
        <a class="metric-link <?= $index < 3 ? 'metric-summary' : 'metric-product' ?>" href="<?= $card[4] ?>">
          <div class="card metric-card"><div class="card-body">
            <div class="metric-label"><?= htmlspecialchars($card[0], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="d-flex align-items-center justify-content-between">
              <div class="metric-value"><?= (int) $card[1] ?></div>
              <div class="metric-icon <?= $card[3] ?>"><i class="bi <?= $card[2] ?>"></i></div>
            </div>
          </div></div>
        </a>
      <?php endforeach; ?>

      <div class="card dashboard-table-card recent-sales"><div class="card-body">
        <div class="dashboard-toolbar">
          <h5 class="card-title mb-0">Daftar Akun Tersedia</h5>
          <div class="dashboard-controls">
            <select id="dashboardProduct"><option value="">Semua jenis akun</option><?php foreach ($products as $product): ?><option value="<?= $product ?>"><?= ucfirst(strtolower($product)) ?></option><?php endforeach; ?></select>
            <select id="dashboardPerPage"><option value="5">5 per page</option><option value="10" selected>10 per page</option><option value="25">25 per page</option></select>
            <input id="dashboardSearch" type="search" placeholder="Cari akun..." aria-label="Cari akun">
          </div>
        </div>
        <div class="table-responsive"><table class="table table-borderless align-middle" id="dashboardTable">
          <thead><tr><th>Jenis Akun</th><th>Username</th><th>Kategori</th><th>Status</th><th>Expired</th><th>Aksi</th></tr></thead>
          <tbody><?php foreach (($akun_belum_penuh ?? []) as $account): $product = strtoupper(trim((string) ($account->nama_akun ?? ''))); ?>
            <tr data-product="<?= htmlspecialchars($product, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower(implode(' ', [$product, $account->username ?? '', $account->kategori ?? '', $account->status ?? ''])), ENT_QUOTES, 'UTF-8') ?>">
              <td><strong><?= htmlspecialchars($account->nama_akun ?? '-', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($account->username ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $account->kategori ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="badge bg-success">Aktif</span></td>
              <td><?= !empty($account->expired_password) ? date('d-m-Y', strtotime($account->expired_password)) : '-' ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/detail_akun/' . (int) $account->id_akun) ?>"><i class="bi bi-eye"></i></a></td>
            </tr>
          <?php endforeach; ?></tbody>
        </table></div>
        <div class="dashboard-pagination"><span id="dashboardInfo"></span><div><button id="dashboardPrev">Sebelumnya</button><button id="dashboardNext">Berikutnya</button></div></div>
      </div></div>
    </div>

    <aside><div class="card dashboard-notifications"><div class="card-body">
      <?php $notificationTotal = (int) ($notif_count ?? count($recent_notifications ?? [])); ?>
      <div class="notification-heading"><h5 class="card-title">Notifikasi</h5><span class="notification-count"><?= $notificationTotal ?></span></div>
      <div class="notification-list">
        <?php if (!empty($recent_notifications)): foreach ($recent_notifications as $notification): ?>
          <div class="notification-item"><i class="bi bi-exclamation-circle"></i><div><strong><?= htmlspecialchars($notification['title'] ?? 'Pemberitahuan akun', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($notification['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></div></div>
        <?php endforeach; else: ?><div class="empty-notification"><i class="bi bi-check-circle"></i>Tidak ada notifikasi baru</div><?php endif; ?>
      </div>
      <a href="<?= base_url('admin/notifications') ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">Lihat Semua Notifikasi</a>
    </div></div></aside>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded',function(){
 const rows=Array.from(document.querySelectorAll('#dashboardTable tbody tr')),search=document.getElementById('dashboardSearch'),product=document.getElementById('dashboardProduct'),perPage=document.getElementById('dashboardPerPage'),info=document.getElementById('dashboardInfo'),prev=document.getElementById('dashboardPrev'),next=document.getElementById('dashboardNext');let page=1;
 function render(){const q=search.value.toLowerCase().trim(),p=product.value,filtered=rows.filter(r=>(!q||r.dataset.search.includes(q))&&(!p||r.dataset.product===p)),size=Number(perPage.value),pages=Math.max(1,Math.ceil(filtered.length/size));page=Math.min(page,pages);rows.forEach(r=>r.style.display='none');filtered.slice((page-1)*size,page*size).forEach(r=>r.style.display='');info.textContent=filtered.length?`Menampilkan ${(page-1)*size+1}-${Math.min(page*size,filtered.length)} dari ${filtered.length} akun`:'Tidak ada data';prev.disabled=page<=1;next.disabled=page>=pages}
 [search,product,perPage].forEach(el=>el.addEventListener(el===search?'input':'change',()=>{page=1;render()}));prev.addEventListener('click',()=>{page--;render()});next.addEventListener('click',()=>{page++;render()});render();
});
</script>
</main>
