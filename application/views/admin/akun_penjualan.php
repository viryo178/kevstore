<?php
$products = ['SPOTIFY', 'LEONARDO', 'GEMINI', 'ZOOM', 'ADOBE'];
$sales = array_fill_keys($products, 0);
$stock = array_fill_keys($products, 0);
foreach (($akun ?? []) as $account) {
    $product = strtoupper(trim((string) ($account->nama_akun ?? '')));
    $status = strtolower(str_replace([' ', '-'], '_', trim((string) ($account->status ?? ''))));
    if (!isset($sales[$product])) continue;
    if ($status === 'terjual') {
        $sales[$product]++;
    }
    if (($account->kategori ?? '') === 'belum_terjual' && $status === 'aktif') $stock[$product]++;
}
$lowStock = array_filter($stock, static function ($amount) { return $amount < 5; });
?>
<style>
.sales-layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:20px}.sales-chart-card,.stock-card{margin:0!important;height:100%}.sales-summary{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px}.sales-summary-item{background:rgba(15,35,75,.62);border:1px solid rgba(96,165,250,.16);border-radius:12px;padding:13px}.sales-summary-item span{display:block;color:#8fa5cf;font-size:11px}.sales-summary-item strong{font-size:22px;color:#fff}.stock-alert{display:flex;align-items:flex-start;gap:12px;border:1px solid;border-radius:13px;padding:14px;margin-bottom:12px}.stock-alert.warning{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.35)}.stock-alert.danger{background:rgba(239,68,68,.09);border-color:rgba(239,68,68,.4)}.stock-alert i{font-size:21px}.stock-alert.warning i{color:#fbbf24}.stock-alert.danger i{color:#f87171}.stock-alert strong{display:block;color:#fff}.stock-alert small{color:#9db1d6}.stock-ok{text-align:center;color:#8fa5cf;padding:55px 10px}.stock-ok i{display:block;color:#4ade80;font-size:38px;margin-bottom:10px}@media(max-width:991px){.sales-layout{grid-template-columns:1fr}.sales-summary{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.sales-summary{grid-template-columns:1fr}}
</style>
<main id="main" class="main">
<div class="pagetitle"><h1>Akun Penjualan</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url('admin') ?>">Admin</a></li><li class="breadcrumb-item active">Akun Penjualan</li></ol></nav></div>
<section class="section">
  <div class="sales-summary"><?php foreach ($products as $product): ?><div class="sales-summary-item"><span><?= ucfirst(strtolower($product)) ?> Terjual</span><strong><?= (int) $sales[$product] ?></strong></div><?php endforeach; ?></div>
  <div class="sales-layout">
    <div class="card sales-chart-card"><div class="card-body"><h5 class="card-title">Grafik Penjualan per Jenis Akun</h5><div id="accountSalesChart" style="min-height:360px"></div></div></div>
    <div class="card stock-card"><div class="card-body"><h5 class="card-title">Notifikasi Stok</h5>
      <?php if ($lowStock): foreach ($lowStock as $product => $amount): ?><div class="stock-alert <?= $amount === 0 ? 'danger' : 'warning' ?>"><i class="bi <?= $amount === 0 ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill' ?>"></i><div><strong><?= ucfirst(strtolower($product)) ?></strong><small><?= $amount === 0 ? 'Stok habis, butuh restok.' : 'Stok tersisa ' . $amount . ', segera tambah stok.' ?></small></div></div><?php endforeach; else: ?><div class="stock-ok"><i class="bi bi-check-circle"></i>Semua stok akun aman.</div><?php endif; ?>
    </div></div>
  </div>
</section>
<script>
document.addEventListener('DOMContentLoaded',function(){if(typeof ApexCharts==='undefined')return;new ApexCharts(document.querySelector('#accountSalesChart'),{series:[{name:'Akun Terjual',data:<?= json_encode(array_values($sales)) ?>}],chart:{type:'bar',height:360,toolbar:{show:false},background:'transparent'},plotOptions:{bar:{borderRadius:7,columnWidth:'48%'}},colors:['#3b82f6'],dataLabels:{enabled:false},xaxis:{categories:<?= json_encode(array_map('ucfirst', array_map('strtolower', $products))) ?>,labels:{style:{colors:'#8fa5cf'}}},yaxis:{min:0,forceNiceScale:true,labels:{style:{colors:'#8fa5cf'},formatter:function(v){return Math.round(v)}}},grid:{borderColor:'rgba(148,163,184,.12)'},tooltip:{theme:'dark'}}).render()});
</script>
</main>
