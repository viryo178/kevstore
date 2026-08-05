
<!DOCTYPE html>
<html lang="id">
<head>
    <?php if (isset($this->security)) : ?>
        <meta name="csrf-name" content="<?= $this->security->get_csrf_token_name() ?>">
        <meta name="csrf-hash" content="<?= $this->security->get_csrf_hash() ?>">
    <?php endif; ?>
</head>
<style>

:root{
    --bg:#020817;
    --bg2:#071225;
    --card:#0f172a;
    --line:rgba(255,255,255,0.06);

    --primary:#3b82f6;
    --success:#22c55e;
    --danger:#ef4444;
    --warning:#f59e0b;

    --text:#ffffff;
    --text2:#94a3b8;
}

/* BODY */
body{
    background:
        radial-gradient(circle at top left,#0b1d44 0%,#020817 40%),
        #020817 !important;

    color: var(--text) !important;
    font-family: 'Poppins', sans-serif;
}

/* HEADER */
.header{
    background: rgba(2,8,23,0.95) !important;
    border-bottom: 1px solid var(--line);
    backdrop-filter: blur(10px);
    box-shadow: none !important;
}

.logo span{
    color: white !important;
    font-weight: 700;
}

/* SEARCH */
.search-form input{
    background: #081223 !important;
    border: 1px solid #1e3a5f !important;
    color: white !important;
    border-radius: 12px !important;
    height: 42px;
}

.search-form input::placeholder{
    color: #64748b !important;
}

.search-form button{
    color: white !important;
}

/* SIDEBAR */
.sidebar{
    background:
        linear-gradient(
            180deg,
            #030b1a,
            #071225
        ) !important;

    border-right: 1px solid rgba(255,255,255,0.05);
}

/* MENU */
.sidebar-nav .nav-link{
    background: transparent !important;
    color: #dbeafe !important;

    border-radius: 14px;

    margin-bottom: 8px;

    font-weight: 500;

    transition: 0.3s;
}

/* ICON */
.sidebar-nav .nav-link i{
    color: #8fb3ff !important;
}

/* HOVER */
.sidebar-nav .nav-link:hover{
    background: rgba(59,130,246,0.12) !important;

    color: white !important;

    transform: translateX(3px);
}

/* ACTIVE */
.sidebar-nav .nav-link:not(.collapsed){
    background:
        linear-gradient(
            90deg,
            #2563eb,
            #4f46e5
        ) !important;

    color: white !important;

    box-shadow:
        0 0 20px rgba(37,99,235,0.4);
}

.sidebar-nav .nav-link:not(.collapsed) i{
    color: white !important;
}

/* SUB MENU */
.sidebar-nav .nav-content{
    background: transparent !important;
}

.sidebar-nav .nav-content a{
    color: #94a3b8 !important;
}

.sidebar-nav .nav-content a:hover{
    color: white !important;
}

/* PAGE TITLE */
.pagetitle h1{
    color: white !important;
    font-weight: 700;
}

.breadcrumb-item,
.breadcrumb-item a{
    color: #94a3b8 !important;
}

/* MAIN */
.main{
    background: transparent !important;
}

/* CARD */
.card{
    background:
        linear-gradient(
            180deg,
            rgba(15,23,42,0.98),
            rgba(10,18,35,0.98)
        ) !important;

    border: 1px solid rgba(255,255,255,0.05) !important;

    border-radius: 22px !important;

    box-shadow:
        0 10px 30px rgba(0,0,0,0.35);

    overflow: hidden;
    
}

/* CARD TITLE */
.card-title{
    color: white !important;
    font-weight: 600;
}

/* ICON CIRCLE */
.card-icon{
    width: 62px;
    height: 62px;
    border-radius: 50% !important;
}

/* SALES */
.sales-card .card-icon{
    background: rgba(59,130,246,0.15);
    color: #dbeafe;
    
}

/* REVENUE */
.revenue-card .card-icon{
    background: rgba(34,197,94,0.15);
    color: #22c55e;
}

/* CUSTOMER */
.customers-card .card-icon{
    background: rgba(249,115,22,0.15);
    color: #f97316;
}

/* TABLE */
.table{
    color: #e2e8f0 !important;
    border-color: rgba(255,255,255,0.05) !important;
}

/* TABLE HEAD */
.table thead{
    background: rgba(255,255,255,0.03);
}

/* LIST GROUP */
.list-group {
    border: none;
}

.list-group-item {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: #e2e8f0 !important;
    padding: 14px 16px;
    margin-bottom: 10px;
    border-radius: 12px;
    transition: all 0.3s;
}

.list-group-item:hover {
    background: rgba(59, 130, 246, 0.12) !important;
    border-color: rgba(59, 130, 246, 0.3) !important;
    transform: translateY(-2px);
}

.list-group-item strong {
    color: #ffffff;
    font-weight: 600;
}

.list-group-item small {
    color: #cbd5e1;
}

.list-group-item .btn {
    padding: 6px 12px;
    font-size: 12px;
}

.list-group-item .btn-primary {
    background: #3b82f6;
    border: none;
}

.list-group-item .btn-primary:hover {
    background: #2563eb;
}

.list-group-item .btn-secondary {
    background: #1e293b;
    border: 1px solid #334155;
    color: #cbd5e1;
}

.list-group-item .btn-secondary:hover {
    background: #0f172a;
    color: #e2e8f0;
}

/* RECENT ACTIVITY TABLE */
.activity-table {
    width: 100%;
    color: #e2e8f0;
}

.activity-table thead {
    background: rgba(15, 23, 42, 0.8);
    border-bottom: 2px solid rgba(59, 130, 246, 0.3);
}

.activity-table thead th {
    color: #60a5fa;
    font-weight: 600;
    padding: 12px;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
}

.activity-table tbody td {
    padding: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    vertical-align: middle;
}

.activity-table tbody tr:hover {
    background: rgba(59, 130, 246, 0.08);
}

.activity-table .status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.activity-table .badge-new {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.activity-table .badge-edit {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

.activity-table .badge-delete {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

/* PROFILE FORM */
.profile-section {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.profile-section h5 {
    color: #60a5fa;
    margin-bottom: 18px;
    font-weight: 600;
}

.form-group label {
    color: #cbd5e1;
    font-weight: 500;
    margin-bottom: 8px;
}

/* TABLE ROW */
.table tbody tr{
    border-color: rgba(255,255,255,0.05) !important;
}

/* HOVER */
.table tbody tr:hover{
    background: rgba(59,130,246,0.06) !important;
}

/* TABLE TEXT */
.table td,
.table th{
    color:  rgba(59,130,246,0.06) !important;

}

/* DATATABLE */
.datatable-top,
.datatable-bottom{
    color: white !important;
}

/* SEARCH DATATABLE */
.datatable-input{
    background: #081223 !important;
    border: 1px solid #1e3a5f !important;
    color: white !important;
    border-radius: 10px !important;
}

/* SELECT DATATABLE */
.datatable-selector{
    background: #081223 !important;
    border: 1px solid #1e3a5f !important;
    color: white !important;
    border-radius: 10px !important;
}

/* DROPDOWN */
.dropdown-menu{
    background: #0f172a !important;
    border: 1px solid rgba(255,255,255,0.05) !important;
    border-radius: 15px !important;
}

.dropdown-item{
    color: white !important;
}

.dropdown-item:hover{
    background: rgba(59,130,246,0.15) !important;
}

/* INPUT */
input:not(.btn-close),
textarea:not(.btn-close),
select:not(.btn-close){
    background: #081223 !important;
    border: 1px solid #1e3a5f !important;
    color: white !important;
}

/* BUTTON CLOSE */
.btn-close {
    background: transparent !important;
    border: none !important;
    opacity: 0.6;
    filter: invert(1) !important;
}

.btn-close:hover {
    opacity: 1;
    filter: invert(1) !important;
}

/* FOOTER */
.footer{
    background: transparent !important;
    border-top: 1px solid rgba(255,255,255,0.05);
    color: #94a3b8 !important;
}

/* NOTIFICATION */
.notification-item{
    background: transparent !important;
    color: #e2e8f0;
    padding: 12px 16px;
    transition: all 0.3s;
    display: block !important;
    border: none;
    text-decoration: none !important;
}

.notification-item:hover {
    background: rgba(59, 130, 246, 0.12) !important;
    padding-left: 20px;
    color: #e2e8f0 !important;
}

.notification-item h4 {
    color: #ffffff !important;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 14px;
    text-decoration: none;
}

.notification-item p {
    color: #cbd5e1 !important;
    margin: 0;
    font-size: 12px;
}

.notification-item small {
    color: #94a3b8 !important;
    font-size: 11px;
    display: block;
    margin-top: 4px;
}

.notification-item i {
    margin-right: 10px;
    font-size: 18px;
    flex-shrink: 0;
}

.notification-item .bi-key-fill {
    color: #f59e0b !important;
}

.notification-item .bi-share {
    color: #22c55e !important;
}

.notification-item .bi-pencil-circle {
    color: #3b82f6 !important;
}

.notification-item .bi-info-circle {
    color: #3b82f6 !important;
}

.dropdown-header {
    color: #ffffff !important;
    padding: 12px 16px;
    background: transparent !important;
    font-weight: 600;
}

.dropdown-divider {
    border-color: rgba(255, 255, 255, 0.08) !important;
}

.dropdown-footer {
    padding: 8px 0;
    text-align: center;
}

.dropdown-footer a {
    color: #3b82f6 !important;
    font-size: 12px;
    font-weight: 500;
}

.notifications,
.messages{
    background: #0f172a !important;
}

/* PROFILE */
.nav-profile span{
    color: white !important;
}

/* BADGE */
.badge{
    border-radius: 30px !important;
}

/* ALERTS */
.alert {
    border-radius: 12px !important;
    margin-bottom: 15px;
    margin-top: 90px;
    padding: 14px 18px;
    backdrop-filter: blur(10px);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid;
}

.alert-text {
    flex: 1;
    margin: 0;
}

.alert strong {
    font-weight: 600;
}

.alert-success {
    color: #22c55e !important;
    border-color: rgba(34, 197, 94, 0.3) !important;
    background: rgba(34, 197, 94, 0.1) !important;
}

.alert-success i {
    color: #22c55e !important;
}

.alert-danger {
    color: #ef4444 !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    background: rgba(239, 68, 68, 0.1) !important;
}

.alert-danger i {
    color: #ef4444 !important;
}

.alert-warning {
    color: #f59e0b !important;
    border-color: rgba(245, 158, 11, 0.3) !important;
    background: rgba(245, 158, 11, 0.1) !important;
}

.alert-warning i {
    color: #f59e0b !important;
}

.alert-info {
    color: #3b82f6 !important;
    border-color: rgba(59, 130, 246, 0.3) !important;
    background: rgba(59, 130, 246, 0.1) !important;
}

.alert-info i {
    color: #3b82f6 !important;
}

.alert .btn-close {
    opacity: 0.8;
    margin-left: auto;
    filter: invert(1) !important;
    width: 20px;
    height: 20px;
}

.alert .btn-close:hover {
    opacity: 1;
}

/* CHART */
#reportsChart,
#trafficChart,
#budgetChart{
    filter: brightness(1.1);
}

/* SCROLLBAR */
::-webkit-scrollbar{
    width: 8px;
}

::-webkit-scrollbar-track{
    background: #020817;
}

::-webkit-scrollbar-thumb{
    background: #1e3a5f;
    border-radius: 20px;
}

::-webkit-scrollbar-thumb:hover{
    background: #2563eb;
}

/* MOBILE */
@media(max-width:768px){

    .sidebar{
        backdrop-filter: blur(10px);
    }

    .card{
        border-radius: 18px !important;
    }

}

</style>

<style>

/* ===============================
   FORCE DARK TABLE
================================= */

table,
table *{
    background: transparent !important;
    color: #dbe4ff !important;
    border-color: rgba(255,255,255,.05) !important;
}

/* HEADER TABLE */
table thead,
table thead tr,
table thead th{
    background:#13203d !important;
    color:#ffffff !important;
}

/* BODY TABLE */
table tbody,
table tbody tr,
table tbody td,
table tbody th{
    background:#0d1b36 !important;
    color:#dbe4ff !important;
}

/* HOVER */
table tbody tr:hover td{
    background:#16284d !important;
}

/* DATATABLE WRAPPER */
.dataTable-wrapper,
.dataTable-container,
.dataTable-top,
.dataTable-bottom{
    background:#0d1b36 !important;
    color:#ffffff !important;
}

/* SEARCH */
.dataTable-input{
    background:#13203d !important;
    color:#fff !important;
    border:none !important;
    border-radius:10px;
    padding:10px;
}

/* SELECT */
.dataTable-selector{
    background:#13203d !important;
    color:#fff !important;
    border:none !important;
    border-radius:10px;
}

/* PAGINATION */
.dataTable-pagination a{
    background:#13203d !important;
    color:#fff !important;
    border:none !important;
}

/* TOP SELLING FIX */
.top-selling table,
.top-selling tbody,
.top-selling tr,
.top-selling td,
.top-selling th{
    background:#0d1b36 !important;
    color:#dbe4ff !important;
}

/* RECENT SALES FIX */
.recent-sales table,
.recent-sales tbody,
.recent-sales tr,
.recent-sales td,
.recent-sales th{
    background:#0d1b36 !important;
    color:#dbe4ff !important;
}

/* REMOVE ALL WHITE */
.bg-white,
table.dataTable-table,
.dataTable-table,
table.dataTable-table tbody,
table.dataTable-table thead{
    background:#0d1b36 !important;
}

/* LINKS */
table a{
    color:#6ea8ff !important;
}

/* CARD */
.card{
    overflow:hidden;
}

</style>
<style>

/* MODAL */
.modal-content{
    background:
        linear-gradient(
            180deg,
            #0f172a,
            #0d1b36
        ) !important;

    border: 1px solid rgba(255,255,255,0.05) !important;

    border-radius: 22px !important;

    box-shadow:
        0 10px 40px rgba(0,0,0,0.45);

    color: #fff !important;
}

/* HEADER */
.modal-header{
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
    padding: 18px 22px;
}

/* BODY */
.modal-body{
    padding: 22px;
    background: transparent !important;
}

/* FOOTER */
.modal-footer{
    border-top: 1px solid rgba(255,255,255,0.05) !important;
    padding: 18px 22px;
    background: transparent !important;
}

/* TITLE */
.modal-title{
    color: #ffffff !important;
    font-weight: 600;
    font-size: 18px;
}

/* LABEL */
.modal .form-label{
    color: #cbd5e1 !important;
    font-weight: 500;
    margin-bottom: 8px;
}

/* INPUT */
.modal .form-control,
.modal .form-select,
.modal textarea{

    background: #081223 !important;

    border: 1px solid #1e3a5f !important;

    color: #ffffff !important;

    border-radius: 12px !important;

    min-height: 45px;
}

/* FOCUS */
.modal .form-control:focus,
.modal .form-select:focus,
.modal textarea:focus{

    background: #081223 !important;

    border-color: #3b82f6 !important;

    box-shadow:
        0 0 0 3px rgba(59,130,246,0.15) !important;

    color: white !important;
}

/* PLACEHOLDER */
.modal .form-control::placeholder,
.modal textarea::placeholder{
    color: #64748b !important;
}

input[type="date"]::-webkit-calendar-picker-indicator,
input[type="month"]::-webkit-calendar-picker-indicator{
    cursor: pointer;
    opacity: 1;
    filter: invert(73%) sepia(56%) saturate(1151%) hue-rotate(183deg) brightness(102%) contrast(98%);
}

/* CLOSE BUTTON */
.btn-close{
    filter: invert(1);
    opacity: .8;
}

/* BACKDROP */
.modal-backdrop.show{
    opacity: .8 !important;
}

/* BUTTON */
.modal .btn-primary{
    background: linear-gradient(
        90deg,
        #2563eb,
        #4f46e5
    ) !important;

    border: none !important;

    border-radius: 12px !important;

    padding: 10px 18px;
}

.modal .btn-secondary{
    background: #1e293b !important;
    border: none !important;
    border-radius: 12px !important;
}

/* ANIMATION */
.modal.fade .modal-dialog{
    transform: scale(.95);
    transition: .2s ease;
}

.modal.show .modal-dialog{
    transform: scale(1);
}

</style>

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Dashboard - kevstore</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="<?= base_url() ?>assets/img/favicon.png" rel="icon">
  <link href="<?= base_url() ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Vendor CSS Files -->
  <link href="<?= base_url() ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/vendor/simple-datatables/style.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/css/custom-dark.css" rel="stylesheet">
  <link href="<?= base_url() ?>assets/css/keep.css" rel="stylesheet">
  

  <!-- Template Main CSS File -->
  <link href="<?= base_url() ?>assets/css/style.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 20 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>
