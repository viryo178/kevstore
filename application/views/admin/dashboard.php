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

$totalAccounts = max(1, (int) $stats['total']);
$metricCards = [
    ['Total Seluruh Akun', $stats['total'], 'bi-box', 'sales-card', base_url('admin/kelola_akun'), '100%', 'Semua akun'],
    ['Akun Aktif', $stats['aktif'], 'bi-check-circle', 'revenue-card', base_url('admin/kelola_akun?search_akun=aktif'), round(($stats['aktif'] / $totalAccounts) * 100) . '%', 'Status aktif'],
    ['Akun Bermasalah', $stats['bermasalah'], 'bi-exclamation-octagon', 'customers-card', base_url('admin/akun_bermasalah'), round(($stats['bermasalah'] / $totalAccounts) * 100) . '%', 'Perlu dicek'],
];

$productVisuals = [
    'SPOTIFY' => ['bi-music-note-beamed', 'product-card-spotify'],
    'LEONARDO' => ['bi-palette', 'product-card-leonardo'],
    'GEMINI' => ['bi-stars', 'product-card-gemini'],
    'ZOOM' => ['bi-camera-video', 'product-card-zoom'],
    'ADOBE' => ['bi-brush', 'product-card-adobe'],
];

foreach ($products as $product) {
    $visual = $productVisuals[$product] ?? ['bi-box-seam', 'product-card-default'];
    $metricCards[] = [ucfirst(strtolower($product)), $unsold[$product], $visual[0], 'customers-card ' . $visual[1], base_url('admin/kelola_akun?product=' . $product . '&search_akun=belum_terjual'), 'Belum terjual', 'Klik untuk lihat'];
}
?>

<style>
.admin-dashboard-grid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start}.dashboard-main-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}.metric-link{text-decoration:none!important}.metric-link.metric-summary{grid-column:span 4}.metric-link.metric-product{grid-column:span 3}.metric-card{margin:0!important;min-height:126px;transition:.2s}.metric-card:hover{transform:translateY(-3px);border-color:rgba(96,165,250,.38)!important}.metric-card .card-body{padding:20px}.metric-card .card-title{margin:0 0 14px;font-size:14px}.metric-card .card-title span{color:#94a3b8!important;font-size:12px;font-weight:500}.metric-product .metric-card{min-height:112px}.metric-product .metric-card .card-body{padding:18px}.metric-copy h6{font-size:30px;font-weight:800;color:#fff!important;margin:0 0 4px;line-height:1}.metric-copy span{font-size:12px}.metric-product .metric-copy h6{font-size:28px}.dashboard .metric-card .card-icon{width:56px;height:56px;border-radius:50%!important;flex:0 0 56px;font-size:24px}.metric-product .card-icon{width:46px!important;height:46px!important;flex-basis:46px;font-size:20px}.dashboard .sales-card .card-icon{background:rgba(59,130,246,.15)!important;color:#60a5fa!important;box-shadow:0 0 22px rgba(59,130,246,.24)}.dashboard .revenue-card .card-icon{background:rgba(34,197,94,.15)!important;color:#22c55e!important;box-shadow:0 0 22px rgba(34,197,94,.22)}.dashboard .customers-card .card-icon{background:rgba(239,68,68,.15)!important;color:#f87171!important;box-shadow:0 0 22px rgba(239,68,68,.18)}.product-card-spotify{border-color:rgba(34,197,94,.25)!important}.product-card-spotify .card-icon{background:rgba(34,197,94,.16)!important;color:#4ade80!important}.product-card-leonardo{border-color:rgba(249,115,22,.3)!important}.product-card-leonardo .card-icon{background:rgba(249,115,22,.16)!important;color:#fb923c!important}.product-card-gemini{border-color:rgba(96,165,250,.3)!important}.product-card-gemini .card-icon{background:rgba(96,165,250,.16)!important;color:#93c5fd!important}.product-card-zoom{border-color:rgba(45,212,191,.3)!important}.product-card-zoom .card-icon{background:rgba(45,212,191,.16)!important;color:#5eead4!important}.product-card-adobe{border-color:rgba(248,113,113,.3)!important}.product-card-adobe .card-icon{background:rgba(248,113,113,.16)!important;color:#f87171!important}.dashboard-table-card{grid-column:1/-1;margin:0!important}.dashboard-table-card .card-title span{color:#94a3b8!important;font-size:12px;font-weight:500}.dashboard-table-card #tableAkun thead{background:rgba(255,255,255,.03)!important}.dashboard-table-card #tableAkun th{white-space:nowrap}.dashboard-table-card code.text-info{color:#67e8f9!important;font-weight:600}.bg-border-success{background-color:rgba(34,197,94,.12)!important;color:#4ade80!important;border:1px solid #22c55e!important;padding:4px 10px!important;border-radius:8px!important;font-size:11px!important;font-weight:600!important;display:inline-block!important}.bg-border-danger{background-color:rgba(239,68,68,.12)!important;color:#f87171!important;border:1px solid #ef4444!important;padding:4px 10px!important;border-radius:8px!important;font-size:11px!important;font-weight:600!important;display:inline-block!important}.badge-private{background-color:rgba(59,130,246,.12)!important;color:#60a5fa!important;border:1px solid #3b82f6!important;padding:4px 10px!important;border-radius:8px!important;font-size:11px!important;font-weight:600!important;display:inline-block!important}.badge-sharing{background-color:rgba(234,179,8,.12)!important;color:#facc15!important;border:1px solid #eab308!important;padding:4px 10px!important;border-radius:8px!important;font-size:11px!important;font-weight:600!important;display:inline-block!important}.dashboard-action-btn{width:34px;height:34px;border-radius:10px!important;display:inline-flex!important;align-items:center;justify-content:center;margin-right:4px}.dashboard-notifications{margin:0!important;height:calc(100vh - 142px);min-height:520px;overflow:hidden}.dashboard-notifications .card-body{height:100%;display:flex;flex-direction:column}.dashboard-notifications .card-title{margin-bottom:16px}.dashboard-notif-list{overflow:auto;min-height:0;padding-right:4px;scrollbar-width:none;-ms-overflow-style:none}.dashboard-notif-list::-webkit-scrollbar{display:none}.notif-group{margin-bottom:12px}.notif-card{width:100%;border:1px solid rgba(255,255,255,.08);background:rgba(8,18,35,.82);border-radius:16px;padding:12px;display:grid;grid-template-columns:38px 1fr auto auto;gap:10px;align-items:center;text-align:left;color:#fff;transition:.2s}.notif-card:hover{border-color:rgba(96,165,250,.32);background:rgba(15,32,59,.88)}.notif-card[aria-expanded=true] .notif-chevron{transform:rotate(180deg)}.notif-icon{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:rgba(239,68,68,.13);color:#f87171}.notif-title{font-size:13px;font-weight:700;color:#fff}.notif-desc,.notif-info{font-size:12px;color:#94a3b8}.notif-count{background:#ef4444;color:#fff!important;border-radius:999px;min-width:24px;height:24px;padding:0 7px;display:grid;place-items:center;font-size:11px;font-weight:700}.notif-chevron{color:#94a3b8;transition:.2s}.notif-account-list{padding:8px 4px 2px 48px}.notif-account-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid rgba(148,163,184,.12)}.notif-account-item:last-child{border-bottom:0}.notif-account-name{color:#fff;font-size:13px;font-weight:700}.notif-account-meta{color:#8fa5cf;font-size:12px}.dashboard-toolbar{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:16px}.dashboard-controls{display:flex;gap:10px;flex-wrap:wrap}.dashboard-controls select,.dashboard-controls input{background:#081225!important;border:1px solid #16366f!important;color:#fff!important;border-radius:9px;padding:8px 10px}.dashboard-controls input{min-width:220px}.dashboard-pagination{display:flex;justify-content:space-between;align-items:center;color:#8fa5cf;font-size:12px;margin-top:14px}.dashboard-pagination button{background:#12234a;color:#cfe0ff;border:1px solid #244783;border-radius:7px;padding:6px 10px;margin-left:5px}.dashboard-pagination button:disabled{opacity:.4}.empty-notification{text-align:center;padding:42px 10px;color:#8095bf}.empty-notification i{font-size:34px;color:#4ade80;display:block;margin-bottom:10px}@media(max-width:1199px){.admin-dashboard-grid{grid-template-columns:1fr}.dashboard-notifications{height:auto;min-height:0}.dashboard-main-grid{grid-template-columns:repeat(6,minmax(0,1fr))}.metric-link.metric-summary,.metric-link.metric-product{grid-column:span 2}}@media(max-width:767px){.metric-link.metric-summary,.metric-link.metric-product{grid-column:span 3}.notif-account-list{padding-left:0}}@media(max-width:575px){.dashboard-main-grid{grid-template-columns:1fr}.metric-link.metric-summary,.metric-link.metric-product{grid-column:1/-1}.dashboard-controls,.dashboard-controls input,.dashboard-controls select{width:100%}.dashboard-pagination{align-items:flex-start;flex-direction:column;gap:10px}.notif-card{grid-template-columns:38px 1fr auto}.notif-chevron{display:none}}
</style>
<style>
.dashboard-main-grid .metric-card{height:100%;min-height:174px}.dashboard-main-grid .metric-card .card-body{height:100%;display:flex;flex-direction:column}.dashboard-main-grid .metric-card .card-title{min-height:36px;display:flex;align-items:flex-start;gap:4px;line-height:1.15}.dashboard-main-grid .metric-card .card-title span{white-space:nowrap}.dashboard-main-grid .metric-card .d-flex.align-items-center{display:grid!important;grid-template-columns:64px minmax(0,1fr);gap:10px;align-items:center!important;flex:1}.dashboard-main-grid .metric-product .metric-card{min-height:278px}.dashboard-main-grid .metric-product .metric-card .card-title{min-height:54px;font-size:13px}.dashboard-main-grid .metric-product .metric-card .d-flex.align-items-center{grid-template-columns:58px minmax(0,1fr);align-items:center!important}.dashboard-main-grid .metric-card .card-icon{margin:0 auto}.metric-copy{padding-left:0!important;min-width:0}.metric-copy h6{font-size:30px!important}.metric-copy span{display:inline;line-height:1.45}.metric-copy .text-muted{overflow-wrap:anywhere}.metric-product .metric-copy span{display:block}.metric-product .metric-copy h6{margin-bottom:8px}.dashboard-table-card .dashboard-toolbar{align-items:flex-start}.dashboard-filter-row{margin:14px 0 16px}.dashboard-filter-row .form-label{color:#fff;font-size:13px;font-weight:700;margin-bottom:6px}.dashboard-filter-row .form-control{min-width:180px;background:#081225!important;color:#fff!important;border:1px solid #16366f!important;border-radius:7px!important}.dashboard-manual-table-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.dashboard-manual-table-top .datatable-dropdown label{color:#dbe7ff!important;font-size:13px}.dashboard-manual-table-top .datatable-selector,.dashboard-manual-table-top .datatable-input{background:#081225!important;color:#fff!important;border:1px solid #16366f!important;border-radius:7px!important}.dashboard-manual-table-top .datatable-input{min-width:220px}.dashboard-table-card .table-responsive{overflow-x:visible}.dashboard-table-card #dashboardTable{table-layout:fixed;width:100%;min-width:0}.dashboard-table-card #dashboardTable th,.dashboard-table-card #dashboardTable td{vertical-align:middle;font-size:12px;padding:12px 8px}.dashboard-table-card #dashboardTable th:nth-child(1),.dashboard-table-card #dashboardTable td:nth-child(1){width:15%}.dashboard-table-card #dashboardTable th:nth-child(2),.dashboard-table-card #dashboardTable td:nth-child(2){width:24%;white-space:normal;overflow-wrap:anywhere;word-break:break-word}.dashboard-table-card #dashboardTable th:nth-child(3),.dashboard-table-card #dashboardTable td:nth-child(3){width:25%;white-space:normal;overflow-wrap:anywhere;word-break:break-word;line-height:1.35}.dashboard-table-card #dashboardTable th:nth-child(4),.dashboard-table-card #dashboardTable td:nth-child(4){width:12%}.dashboard-table-card #dashboardTable th:nth-child(5),.dashboard-table-card #dashboardTable td:nth-child(5){width:13%}.dashboard-table-card #dashboardTable th:nth-child(6),.dashboard-table-card #dashboardTable td:nth-child(6){width:11%}.dashboard-table-card .dashboard-pagination{display:flex}.dashboard-table-card .dashboard-pagination button{font-size:12px}@media(max-width:1199px){.dashboard-main-grid .metric-product .metric-card{min-height:190px}}@media(max-width:767px){.dashboard-manual-table-top{align-items:flex-start;flex-direction:column}.dashboard-manual-table-top .datatable-search,.dashboard-manual-table-top .datatable-input{width:100%}.dashboard-table-card #dashboardTable th,.dashboard-table-card #dashboardTable td{font-size:11px;padding:10px 6px}.dashboard-action-btn{width:30px;height:30px}}@media(max-width:575px){.dashboard-main-grid .metric-card,.dashboard-main-grid .metric-product .metric-card{min-height:150px}.dashboard-main-grid .metric-card .d-flex.align-items-center,.dashboard-main-grid .metric-product .metric-card .d-flex.align-items-center{grid-template-columns:56px minmax(0,1fr)}}
</style>
<style>
.dashboard-main-grid .metric-card{min-height:150px!important}.dashboard-main-grid .metric-card .card-body{justify-content:space-between}.dashboard-main-grid .metric-card .card-title{display:block!important;min-height:0!important;margin-bottom:16px!important;line-height:1.25!important;white-space:normal}.dashboard-main-grid .metric-card .card-title span{display:inline!important;white-space:nowrap}.dashboard-main-grid .metric-card .d-flex.align-items-center{display:grid!important;grid-template-columns:64px minmax(0,1fr);gap:12px;align-items:center!important;flex:0!important}.dashboard-main-grid .metric-product .metric-card{min-height:160px!important}.dashboard-main-grid .metric-product .metric-card .card-body{padding:18px!important}.dashboard-main-grid .metric-product .metric-card .card-title{font-size:14px!important;margin-bottom:14px!important}.dashboard-main-grid .metric-product .metric-card .d-flex.align-items-center{grid-template-columns:52px minmax(0,1fr)}.metric-copy h6{font-size:28px!important;margin-bottom:4px!important}.metric-product .metric-copy h6{font-size:26px!important;margin-bottom:5px!important}.metric-product .metric-copy span{display:inline!important;font-size:11px!important}.metric-product .metric-copy .text-muted{display:block!important;margin-top:2px}.dashboard .metric-product .card-icon{width:44px!important;height:44px!important;flex-basis:44px!important}.metric-link.metric-product{grid-column:span 3}@media(max-width:1199px){.dashboard-main-grid .metric-product .metric-card{min-height:150px!important}}@media(max-width:575px){.dashboard-main-grid .metric-card,.dashboard-main-grid .metric-product .metric-card{min-height:140px!important}}
</style>
<style>
.dashboard-main-grid .metric-card{min-height:140px!important;height:140px!important}.dashboard-main-grid .metric-card .card-body{display:grid!important;grid-template-rows:auto 1fr!important;gap:12px!important;justify-content:normal!important;padding:18px 20px!important}.dashboard-main-grid .metric-card .card-title{margin:0!important;min-height:0!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}.dashboard-main-grid .metric-card .d-flex.align-items-center{align-self:center!important;display:grid!important;grid-template-columns:58px minmax(0,1fr)!important;gap:12px!important}.dashboard-main-grid .metric-product .metric-card{height:140px!important;min-height:140px!important}.dashboard-main-grid .metric-product .metric-card .card-body{padding:18px!important}.dashboard-main-grid .metric-product .metric-card .card-title{font-size:14px!important;line-height:1.2!important}.dashboard-main-grid .metric-product .metric-card .d-flex.align-items-center{grid-template-columns:50px minmax(0,1fr)!important}.dashboard .metric-card .card-icon{width:48px!important;height:48px!important;flex-basis:48px!important}.dashboard .metric-product .card-icon{width:42px!important;height:42px!important;flex-basis:42px!important}.metric-copy h6{font-size:26px!important;line-height:1!important;margin:0 0 4px!important}.metric-copy span{font-size:11px!important;line-height:1.35!important}.metric-copy .text-muted{display:inline!important;margin:0 0 0 4px!important}.metric-product .metric-copy .text-muted{display:block!important;margin:2px 0 0!important}.metric-product .metric-copy span{display:inline!important}@media(max-width:575px){.dashboard-main-grid .metric-card,.dashboard-main-grid .metric-product .metric-card{height:auto!important;min-height:132px!important}.dashboard-main-grid .metric-card .card-title{white-space:normal!important}}
</style>
<style>
.dashboard-main-grid .metric-card,.dashboard-main-grid .metric-product .metric-card{height:156px!important;min-height:156px!important}.dashboard-main-grid .metric-card .card-body,.dashboard-main-grid .metric-product .metric-card .card-body{grid-template-rows:max-content max-content!important;align-content:center!important;justify-content:stretch!important;gap:16px!important;padding:18px 20px!important}.dashboard-main-grid .metric-card .d-flex.align-items-center,.dashboard-main-grid .metric-product .metric-card .d-flex.align-items-center{align-self:auto!important}.dashboard-main-grid .metric-product .metric-card .card-title{margin-bottom:0!important}.dashboard .metric-product .card-icon{width:44px!important;height:44px!important;flex-basis:44px!important}@media(max-width:575px){.dashboard-main-grid .metric-card,.dashboard-main-grid .metric-product .metric-card{height:auto!important;min-height:148px!important}}
</style>
<style>
.metric-summary .metric-card .d-flex.align-items-center{grid-template-columns:54px minmax(0,1fr)!important;gap:16px!important}.dashboard .metric-summary .card-icon{width:46px!important;height:46px!important;flex:0 0 46px!important;margin:0!important;border-radius:14px!important;font-size:22px!important;box-shadow:none!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:0!important;line-height:1!important}.dashboard .metric-summary .card-icon i{display:block!important;width:1em!important;height:1em!important;line-height:1!important;margin:0!important;position:static!important;transform:none!important}.dashboard .metric-summary .sales-card .card-icon{background:rgba(99,102,241,.14)!important;color:#6366f1!important}.dashboard .metric-summary .revenue-card .card-icon{background:rgba(34,197,94,.14)!important;color:#22c55e!important}.dashboard .metric-summary .customers-card .card-icon{background:rgba(249,115,22,.14)!important;color:#fb923c!important}
</style>
<style>
.dashboard .metric-summary .card-icon i{font-size:22px!important;color:inherit!important;display:flex!important;align-items:center!important;justify-content:center!important;width:22px!important;height:22px!important}.dashboard .metric-summary .card-icon i::before{display:block!important;width:22px!important;height:22px!important;line-height:22px!important;margin:0!important;text-align:center!important;vertical-align:0!important}.dashboard .metric-summary .sales-card .card-icon i{color:#6366f1!important}.dashboard .metric-summary .revenue-card .card-icon i{color:#22c55e!important}.dashboard .metric-summary .customers-card .card-icon i{color:#f97316!important}
</style>
<style>
.dashboard .metric-summary .card-icon{position:relative!important;width:56px!important;height:56px!important;flex:0 0 56px!important;border-radius:16px!important;overflow:hidden!important;display:block!important}.dashboard .metric-summary .card-icon i{position:absolute!important;inset:0!important;width:100%!important;height:100%!important;display:grid!important;place-items:center!important;font-size:30px!important;line-height:1!important;margin:0!important}.dashboard .metric-summary .card-icon i::before{display:block!important;width:auto!important;height:auto!important;line-height:1!important;margin:0!important;text-align:center!important;vertical-align:0!important}.metric-summary .metric-card .d-flex.align-items-center{grid-template-columns:56px minmax(0,1fr)!important}
</style>
<style>
.dashboard-main-grid .metric-summary .metric-card .card-body{gap:8px!important}.dashboard-main-grid .metric-summary .metric-card .card-title{margin-bottom:0!important}.dashboard-main-grid .metric-summary .metric-card .d-flex.align-items-center{align-self:start!important}
</style>
<style>
.dashboard-main-grid .metric-summary .metric-card .card-body{gap:2px!important}.dashboard-main-grid .metric-summary .metric-card .d-flex.align-items-center{margin-top:-2px!important}
</style>
<style>
.dashboard-main-grid .metric-summary .metric-card .card-body{align-content:start!important;padding-top:7px!important}
</style>

<main id="main" class="main">
<div class="pagetitle"><h1>Dashboard</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url('admin') ?>">Admin</a></li><li class="breadcrumb-item active">Dashboard</li></ol></nav></div>

<section class="section dashboard">
  <div class="admin-dashboard-grid">
    <div class="dashboard-main-grid">
      <?php foreach ($metricCards as $index => $card): ?>
        <a class="metric-link <?= $index < 3 ? 'metric-summary' : 'metric-product' ?>" href="<?= $card[4] ?>">
          <div class="card info-card metric-card <?= $card[3] ?>"><div class="card-body">
            <h5 class="card-title">
              <?= htmlspecialchars($card[0], ENT_QUOTES, 'UTF-8') ?>
              <span>| <?= $index < 3 ? 'Total' : 'Produk' ?></span>
            </h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi <?= $card[2] ?>"></i>
              </div>
              <div class="metric-copy ps-3">
                <h6><?= (int) $card[1] ?></h6>
                <span class="<?= $index === 1 ? 'text-success' : ($index === 2 ? 'text-danger' : 'text-warning') ?> small pt-1 fw-bold">
                  <?= htmlspecialchars($card[5], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="text-muted small ps-1"><?= htmlspecialchars($card[6], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
          </div></div>
        </a>
      <?php endforeach; ?>

      <div class="card dashboard-table-card recent-sales"><div class="card-body">
        <div class="dashboard-toolbar">
          <h5 class="card-title mb-0">
            <span id="availableAccountsTitle">Akun Tersedia</span>
            <span>| Max User &lt; 4</span>
          </h5>
        </div>
        <div class="kelola-date-filter dashboard-filter-row">
          <div>
            <label class="form-label" for="dashboardProduct">Jenis akun</label>
            <select class="form-control" id="dashboardProduct">
              <option value="">Semua jenis akun</option>
              <?php foreach ($products as $product): ?>
                <option value="<?= $product ?>"><?= ucfirst(strtolower($product)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="datatable-top kelola-manual-table-top dashboard-manual-table-top">
          <div class="datatable-dropdown">
            <label>
              <select class="datatable-selector" id="dashboardPerPage" aria-label="Pilih jumlah data per halaman">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="-1">All</option>
              </select>
              entries per page
            </label>
          </div>
          <div class="datatable-search">
            <input class="datatable-input" id="dashboardSearch" type="search" placeholder="Search..." aria-label="Search" autocomplete="off">
          </div>
        </div>
        <?php if (!empty($akun_belum_penuh)): ?>
          <div class="table-responsive"><table class="table table-borderless align-middle" id="dashboardTable">
            <thead><tr><th>Nama Akun</th><th>Username</th><th>Password</th><th>Max User</th><th>Kategori</th><th>Aksi</th></tr></thead>
            <tbody><?php foreach (($akun_belum_penuh ?? []) as $account): ?>
              <?php
                $product = strtoupper(trim((string) ($account->nama_akun ?? '')));
                $category = (string) ($account->kategori ?? '');
                $limit = in_array($product, ['SPOTIFY', 'LEONARDO'], true) ? 1 : ($category === 'private' ? 1 : 4);
                $maxUser = (int) ($account->max_user ?? 0);
              ?>
              <tr id="akun-item-<?= (int) $account->id_akun ?>" data-product="<?= htmlspecialchars($product, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower(implode(' ', [$product, $account->username ?? '', $account->password ?? '', $category, $maxUser])), ENT_QUOTES, 'UTF-8') ?>">
                <td><strong><?= htmlspecialchars($account->nama_akun ?? '-', ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td><?= htmlspecialchars($account->username ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><code class="text-info"><?= htmlspecialchars($account->password ?? '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><span class="<?= $maxUser >= $limit ? 'bg-border-danger' : 'bg-border-success' ?>"><?= $maxUser ?> / <?= $limit ?></span></td>
                <td>
                  <?php if ($category === 'private'): ?>
                    <span class="badge-private">Private</span>
                  <?php elseif ($category === 'sharing'): ?>
                    <span class="badge-sharing">Sharing</span>
                  <?php else: ?>
                    <span class="badge-sharing">Belum Terjual</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn-sm btn-primary dashboard-action-btn" <?= $maxUser >= $limit ? 'disabled' : '' ?> type="button"><i class="bi bi-clipboard"></i></button>
                  <a class="btn btn-sm btn-warning dashboard-action-btn" href="<?= base_url('admin/edit_akun/' . (int) $account->id_akun) ?>"><i class="bi bi-pencil-square"></i></a>
                </td>
              </tr>
            <?php endforeach; ?></tbody>
          </table></div>
          <div class="dashboard-pagination"><span id="dashboardInfo"></span><div><button id="dashboardPrev">Sebelumnya</button><button id="dashboardNext">Berikutnya</button></div></div>
        <?php else: ?>
          <div class="alert alert-danger mb-0">Tidak ada akun tersedia</div>
        <?php endif; ?>
      </div></div>
    </div>

    <aside><div class="card dashboard-notifications"><div class="card-body">
      <h5 class="card-title">Notifikasi Terbaru</h5>
      <div class="dashboard-notif-list">
        <?php
          $dashboard_notification_groups = [];
          $expired_dashboard_accounts = array_merge($expired_accounts ?? [], $almost_expired ?? []);
          if (!empty($expired_dashboard_accounts)) {
              $dashboard_notification_groups[] = [
                  'id' => 'expired',
                  'title' => 'Akun Expired',
                  'description' => count($expired_dashboard_accounts) . ' akun expired',
                  'icon' => 'bi-exclamation-triangle-fill',
                  'severity' => 'notif-danger',
                  'accounts' => $expired_dashboard_accounts,
              ];
          }
        ?>

        <?php if (!empty($dashboard_notification_groups)): ?>
          <?php foreach ($dashboard_notification_groups as $index => $notification): ?>
            <?php $collapse_id = 'dashboardNotif' . ucfirst($notification['id']); ?>
            <div class="notif-group">
              <button type="button" class="notif-card <?= htmlspecialchars((string) $notification['severity'], ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="<?= $collapse_id ?>">
                <div class="notif-icon"><i class="bi <?= htmlspecialchars((string) $notification['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <div class="notif-content">
                  <div class="notif-title"><?= htmlspecialchars((string) $notification['title'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="notif-desc"><?= htmlspecialchars((string) $notification['description'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="notif-info"><span><?= count($notification['accounts']) ?> akun</span></div>
                </div>
                <span class="notif-count"><?= count($notification['accounts']) ?></span>
                <i class="bi bi-chevron-down notif-chevron"></i>
              </button>

              <div id="<?= $collapse_id ?>" class="collapse <?= $index === 0 ? 'show' : '' ?>">
                <div class="notif-account-list">
                  <?php foreach ($notification['accounts'] as $account): ?>
                    <div class="notif-account-item">
                      <div>
                        <div class="notif-account-name"><?= htmlspecialchars((string) ($account->nama_akun ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="notif-account-meta">
                          <?= htmlspecialchars((string) ($account->username ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                          - <?= !empty($account->expired_password) ? date('d M Y', strtotime($account->expired_password)) : '-' ?>
                        </div>
                      </div>
                      <a class="btn btn-sm btn-warning" href="<?= base_url('admin/edit_akun/' . (int) $account->id_akun) ?>">Edit</a>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-muted">Tidak ada notifikasi</div>
        <?php endif; ?>
      </div>
    </div></div></aside>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded',function(){
 const rows=Array.from(document.querySelectorAll('#dashboardTable tbody tr')),search=document.getElementById('dashboardSearch'),product=document.getElementById('dashboardProduct'),perPage=document.getElementById('dashboardPerPage'),info=document.getElementById('dashboardInfo'),prev=document.getElementById('dashboardPrev'),next=document.getElementById('dashboardNext');let page=1;
 if(!search||!product||!perPage||!info||!prev||!next)return;
 function render(){const q=search.value.toLowerCase().trim(),p=product.value,filtered=rows.filter(r=>(!q||r.dataset.search.includes(q))&&(!p||r.dataset.product===p)),selectedSize=Number(perPage.value),size=selectedSize===-1?Math.max(filtered.length,1):selectedSize,pages=Math.max(1,Math.ceil(filtered.length/size));page=Math.min(page,pages);rows.forEach(r=>r.style.display='none');filtered.slice((page-1)*size,page*size).forEach(r=>r.style.display='');info.textContent=filtered.length?`Menampilkan ${(page-1)*size+1}-${Math.min(page*size,filtered.length)} dari ${filtered.length} entries`:'Tidak ada data';prev.disabled=page<=1;next.disabled=page>=pages}
 [search,product,perPage].forEach(el=>el.addEventListener(el===search?'input':'change',()=>{page=1;render()}));prev.addEventListener('click',()=>{page--;render()});next.addEventListener('click',()=>{page++;render()});render();
});
</script>
</main>
