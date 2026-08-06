<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Jakarta');

        if (!$this->session->userdata('id_user')) {
            redirect('/');
        }

        if ($this->session->userdata('tipe_user') != 'admin') {
            redirect('/');
        }

        $this->load->helper('text');
        $this->load->database();
        $this->ensure_zoom_duration_column();
        $this->process_account_bin();
    }

    private function ensure_zoom_duration_column()
    {
        if ($this->db->table_exists('akun') && !$this->db->field_exists('durasi_zoom', 'akun')) {
            $this->db->query("ALTER TABLE `akun` ADD `durasi_zoom` VARCHAR(20) NULL AFTER `nama_akun`");
        }

        if ($this->db->table_exists('akun') && $this->db->field_exists('durasi_zoom', 'akun')) {
            // Akun Zoom yang dibuat sebelum fitur variasi dianggap paket 1 bulan.
            $this->db->query("UPDATE `akun` SET `durasi_zoom` = '1_bulan' WHERE UPPER(TRIM(`nama_akun`)) = 'ZOOM' AND (`durasi_zoom` IS NULL OR `durasi_zoom` = '')");
        }
    }

    private function normalize_zoom_duration($product, $duration)
    {
        if (strtoupper(trim((string) $product)) !== 'ZOOM') {
            return null;
        }

        $duration = strtolower(trim((string) $duration));
        return in_array($duration, ['14_hari', '1_bulan'], true) ? $duration : null;
    }

    private function ensure_account_bin_table()
    {
        if ($this->db->table_exists('akun_bin')) {
            return true;
        }

        $created = $this->db->query("CREATE TABLE IF NOT EXISTS `akun_bin` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `original_id` INT NULL,
            `nama_akun` VARCHAR(191) NOT NULL,
            `username` VARCHAR(191) NULL,
            `account_data` LONGTEXT NOT NULL,
            `sold_at` DATETIME NULL,
            `binned_at` DATETIME NOT NULL,
            `purge_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_akun_bin_original_id` (`original_id`),
            KEY `idx_akun_bin_purge_at` (`purge_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        return $created !== false;
    }

    private function process_account_bin()
    {
        if (!$this->ensure_account_bin_table()) {
            log_message('error', 'Tabel akun_bin tidak tersedia dan gagal dibuat. Jalankan benerin.sql.');
            return;
        }
        $now = date('Y-m-d H:i:s');
        $sold_cutoff = date('Y-m-d H:i:s', strtotime('-7 days'));

        // Data yang sudah tujuh hari berada di Bin dihapus permanen.
        $this->db->where('purge_at <=', $now)->delete('akun_bin');

        // Waktu status terjual mengikuti last_edited_at, karena setiap perubahan
        // status akun pada aplikasi memperbarui field tersebut.
        $eligible_accounts = $this->db
            ->where_in('nama_akun', ['SPOTIFY', 'LEONARDO', 'ZOOM'])
            ->where("LOWER(REPLACE(REPLACE(status, ' ', '_'), '-', '_')) = 'terjual'", null, false)
            ->where('last_edited_at IS NOT NULL', null, false)
            ->where('last_edited_at <=', $sold_cutoff)
            ->get('akun')
            ->result();

        foreach ($eligible_accounts as $account) {
            $snapshot = (array) $account;
            $bin_data = [
                'original_id' => (int) $account->id_akun,
                'nama_akun' => (string) $account->nama_akun,
                'username' => (string) ($account->username ?? ''),
                'account_data' => json_encode($snapshot),
                'sold_at' => $account->last_edited_at,
                'binned_at' => $now,
                'purge_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            ];

            $this->db->trans_start();
            $already_binned = $this->db
                ->where('original_id', (int) $account->id_akun)
                ->count_all_results('akun_bin') > 0;

            if (!$already_binned) {
                $this->db->insert('akun_bin', $bin_data);
                $this->db->where('id_akun', (int) $account->id_akun)->delete('akun');
            }
            $this->db->trans_complete();
        }
    }

    private function normalize_date($value)
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/', $value, $matches)) {
            return checkdate((int) $matches[2], (int) $matches[1], (int) $matches[3])
                ? $matches[3] . '-' . $matches[2] . '-' . $matches[1]
                : null;
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : null;
    }

    private function resolve_akun_status($kategori, $max_user, $status)
    {
        $manual_statuses = ['deactived', 'tidak_preimum', 'lainnya', 'ban', 'verif', 'terjual'];
        $status = strtolower(str_replace([' ', '-'], '_', trim((string) $status)));
        $kategori = strtolower(trim((string) $kategori));

        if (in_array($status, $manual_statuses, true)) {
            return $status;
        }

        if ($kategori === 'done') {
            return 'terjual';
        }

        $max_user = max(0, (int) $max_user);

        if ($kategori === 'private') {
            return $max_user >= 1 ? 'terjual' : 'aktif';
        }

        if ($kategori === 'sharing') {
            return $max_user >= 4 ? 'terjual' : 'aktif';
        }

        return 'aktif';
    }

    private function resolve_status_from_note($status, $note, $use_note_status = false)
    {
        if (!$use_note_status) {
            return $status;
        }

        $note = strtolower((string) $note);
        $note = str_replace(['-', '_'], ' ', $note);

        if (preg_match('/\bdisable\s*x\b/', $note)) {
            return 'tidak_preimum';
        }

        if (preg_match('/\bdisable\s*email\b/', $note)) {
            return 'lainnya';
        }

        if (preg_match('/\bban(ned)?\b/', $note)) {
            return 'ban';
        }

        return $status;
    }

    private function is_ajax_request()
    {
        return $this->input->is_ajax_request()
            || $this->input->get_request_header('X-Requested-With') === 'XMLHttpRequest';
    }

    private function username_exists($username, $exclude_id = null)
    {
        $username = trim((string) $username);

        if ($username === '') {
            return false;
        }

        $this->db->from('akun');
        $this->db->where('username', $username);

        if ($exclude_id !== null) {
            $this->db->where('id_akun !=', (int) $exclude_id);
        }

        return $this->db->count_all_results() > 0;
    }

    private function default_account_types()
    {
        return ['SPOTIFY', 'LEONARDO', 'GEMINI', 'ZOOM', 'ADOBE'];
    }

    private function account_type_slug($name)
    {
        $slug = strtolower(trim((string) $name));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        return trim((string) $slug, '-');
    }

    private function find_account_type($name)
    {
        if (!$this->db->table_exists('jenis_akun')) {
            return null;
        }

        return $this->db->query(
            "SELECT * FROM `jenis_akun` WHERE UPPER(TRIM(`nama_akun`)) = ? LIMIT 1",
            [strtoupper(trim((string) $name))]
        )->row();
    }

    private function ensure_default_account_types()
    {
        if (!$this->db->table_exists('jenis_akun')) {
            return false;
        }

        // Seed awal hanya saat tabel benar-benar kosong. Dengan begitu jenis akun
        // yang sengaja dihapus admin tidak dibuat ulang pada request berikutnya.
        if ($this->db->count_all('jenis_akun') > 0) {
            return true;
        }

        foreach ($this->default_account_types() as $name) {
            $this->db->insert('jenis_akun', [
                'nama_akun' => $name,
                'slug' => $this->account_type_slug($name),
                'website_resmi' => null,
                'status' => 'aktif',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return true;
    }

    private function get_bulk_account_types()
    {
        if (!$this->ensure_default_account_types()) {
            return $this->default_account_types();
        }

        $rows = $this->db
            ->select('nama_akun')
            ->where('status', 'aktif')
            ->order_by('nama_akun', 'ASC')
            ->get('jenis_akun')
            ->result_array();

        $types = [];
        foreach ($rows as $row) {
            $name = strtoupper(trim((string) ($row['nama_akun'] ?? '')));
            if ($name !== '') {
                $types[$name] = $name;
            }
        }

        return $types ? array_values($types) : $this->default_account_types();
    }

    private function get_account_type_id($name)
    {
        $this->ensure_default_account_types();
        $type = $this->find_account_type($name);
        return $type ? (int) $type->id_jenis_akun : null;
    }

    private function respond_akun_error($message, $redirect = 'admin/kelola_akun')
    {
        if ($this->is_ajax_request()) {
            $this->output->set_content_type('application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $message
            ]);
            return;
        }

        $this->session->set_flashdata('error', $message);
        redirect($redirect);
    }

    private function ensure_activity_snapshot_columns()
    {
        if (!$this->db->table_exists('activity_log')) {
            return;
        }

        if (!$this->db->field_exists('akun_nama_snapshot', 'activity_log')) {
            $this->db->query("ALTER TABLE `activity_log` ADD `akun_nama_snapshot` VARCHAR(191) NULL AFTER `akun_id`");
        }

        if (!$this->db->field_exists('akun_username_snapshot', 'activity_log')) {
            $this->db->query("ALTER TABLE `activity_log` ADD `akun_username_snapshot` VARCHAR(191) NULL AFTER `akun_nama_snapshot`");
        }

        if (!$this->db->field_exists('akun_username_before', 'activity_log')) {
            $this->db->query("ALTER TABLE `activity_log` ADD `akun_username_before` VARCHAR(191) NULL AFTER `akun_username_snapshot`");
        }

        if (!$this->db->field_exists('akun_username_after', 'activity_log')) {
            $this->db->query("ALTER TABLE `activity_log` ADD `akun_username_after` VARCHAR(191) NULL AFTER `akun_username_before`");
        }

        if (!$this->db->field_exists('akun_before_snapshot', 'activity_log')) {
            $this->db->query("ALTER TABLE `activity_log` ADD `akun_before_snapshot` TEXT NULL AFTER `akun_username_after`");
        }

        if (!$this->db->field_exists('akun_after_snapshot', 'activity_log')) {
            $this->db->query("ALTER TABLE `activity_log` ADD `akun_after_snapshot` TEXT NULL AFTER `akun_before_snapshot`");
        }
    }

    private function cleanup_old_activity_logs()
    {
        if (!$this->db->table_exists('activity_log')) {
            return;
        }

        $cutoff = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $this->db->where('created_at <', $cutoff)->delete('activity_log');
    }

    private function account_activity_snapshot($account)
    {
        if (!$account) {
            return [];
        }

        $account = (array) $account;
        $fields = [
            'id_akun',
            'nama_akun',
            'durasi_zoom',
            'kategori',
            'status',
            'username',
            'password',
            'website',
            'note',
            'max_user',
            'expired_password',
            'created_by',
            'last_edited_by',
            'last_edited_at',
        ];

        $snapshot = [];

        foreach ($fields as $field) {
            $snapshot[$field] = $account[$field] ?? null;
        }

        return $snapshot;
    }

    private function build_activity_changes($activity)
    {
        $before = json_decode((string) ($activity->akun_before_snapshot ?? ''), true);
        $after = json_decode((string) ($activity->akun_after_snapshot ?? ''), true);

        $before = is_array($before) ? $before : [];
        $after = is_array($after) ? $after : [];

        if (empty($before) && stripos((string) $activity->action, 'edit') !== false) {
            $before['username'] = $activity->akun_username_before ?? $activity->akun_username_snapshot ?? null;
        }

        if (empty($after) && stripos((string) $activity->action, 'edit') !== false) {
            $after['username'] = $activity->akun_username_after ?? $activity->akun_username ?? null;
        }

        $labels = [
            'nama_akun' => 'Akun',
            'durasi_zoom' => 'Variasi Zoom',
            'kategori' => 'Kategori',
            'status' => 'Status',
            'username' => 'Email / Username',
            'password' => 'Password',
            'website' => 'Website',
            'note' => 'Note',
            'max_user' => 'Max User',
            'expired_password' => 'Expired Password',
            'created_by' => 'Dibuat Oleh',
            'last_edited_by' => 'Terakhir Diedit Oleh',
            'last_edited_at' => 'Waktu Edit Akun',
        ];

        $changes = [];

        foreach ($labels as $field => $label) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ((string) $old !== (string) $new) {
                $changes[] = [
                    'field' => $field,
                    'label' => $label,
                    'before' => $old,
                    'after' => $new,
                ];
            }
        }

        return $changes;
    }

private function get_notification_data()
{
    $today = date('Y-m-d');
    $expired_date = "CASE WHEN expired_password REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN expired_password ELSE NULL END";

    // =====================================
    // EXPIRED + HAMPIR EXPIRED
    // =====================================
    $expiring_accounts = $this->db
        ->where($expired_date . ' IS NOT NULL', null, false)
        ->where(
            $expired_date . ' <= ' . $this->db->escape($today),
            null,
            false
        )
        ->order_by($expired_date, 'ASC', false)

        ->get('akun')
        ->result();

    // =====================================
    // STATUS BERMASALAH
    // =====================================
    $status_problem = $this->db

        ->where_in('status', [
            'deactived',
            'verif',
            'ban',
            'tidak_preimum',
            'lainnya'
        ])

        ->order_by('id_akun', 'DESC')

        ->get('akun')
        ->result();

    $expired_accounts = [];
    $almost_expired = [];

    foreach ($expiring_accounts as $account) {
        if (strtotime($account->expired_password) < strtotime($today)) {
            $expired_accounts[] = $account;
        } else {
            $almost_expired[] = $account;
        }
    }

    // =====================================
    // TOTAL NOTIF
    // =====================================
    $notif_count =
        count($expiring_accounts) +
        count($status_problem);

    $recent_notifications = [];

    foreach ($expired_accounts as $account) {
        $recent_notifications[] = [
            'date' => $account->expired_password,
            'sort_time' => strtotime($account->expired_password ?: '1970-01-01'),
            'title' => 'Password Expired',
            'description' => 'Akun ' . $account->nama_akun . ' sudah expired',
            'info' => [$account->username, date('d M Y', strtotime($account->expired_password))],
            'icon' => 'bi-exclamation-triangle-fill',
            'color' => 'text-danger',
            'severity' => 'notif-danger',
            'url' => base_url('admin/notifications')
        ];
    }

    foreach ($almost_expired as $account) {
        $recent_notifications[] = [
            'date' => $account->expired_password,
            'sort_time' => strtotime($account->expired_password ?: '1970-01-01'),
            'title' => 'Expired Hari Ini',
            'description' => 'Akun ' . $account->nama_akun . ' jatuh tempo hari ini',
            'info' => [$account->username, date('d M Y', strtotime($account->expired_password))],
            'icon' => 'bi-bell-fill',
            'color' => 'text-warning',
            'severity' => 'notif-warning',
            'url' => base_url('admin/notifications')
        ];
    }

    foreach ($status_problem as $account) {
        $notification_date = !empty($account->last_edited_at) ? $account->last_edited_at : date('Y-m-d H:i:s');
        $status_label = ucwords(str_replace('_', ' ', (string) $account->status));

        $recent_notifications[] = [
            'date' => $notification_date,
            'sort_time' => strtotime($notification_date),
            'title' => 'Status Bermasalah',
            'description' => 'Akun ' . $account->nama_akun . ' status ' . $status_label,
            'info' => [$account->username, $status_label],
            'icon' => 'bi-shield-exclamation',
            'color' => 'text-danger',
            'severity' => 'notif-danger',
            'url' => base_url('admin/notifications')
        ];
    }

    usort($recent_notifications, function ($a, $b) {
        return ($b['sort_time'] ?? 0) <=> ($a['sort_time'] ?? 0);
    });

    $recent_notifications = array_slice($recent_notifications, 0, 5);

    return [

        'expiring_accounts' => $expiring_accounts,

        'expired_accounts'  => $expired_accounts,

        'almost_expired'    => $almost_expired,

        'status_problem'    => $status_problem,

        'recent_notifications' => $recent_notifications,

        'notif_count'       => $notif_count
    ];
}

    private function get_recent_activity()
    {
        return $this->db
            ->order_by('last_edited_at', 'DESC')
            ->limit(5)
            ->get('akun')
            ->result();
    }

    // ==============================
    // DASHBOARD
    // ==============================
    public function index()
    {
        $data['akun'] = $this->db->get('akun')->result();
        $dashboard_product = strtoupper(trim((string) $this->input->get('produk')));
        $dashboard_product = in_array($dashboard_product, ['SPOTIFY', 'LEONARDO', 'GEMINI', 'ZOOM', 'ADOBE'], true)
            ? $dashboard_product
            : '';
        $data['dashboard_product'] = $dashboard_product;

        // akun yang masih bisa dipakai di dashboard
$available_accounts_query = $this->db->from('akun');
if ($dashboard_product !== '') {
    $available_accounts_query->where('nama_akun', $dashboard_product);
} else {
    $available_accounts_query->where_in('nama_akun', ['SPOTIFY', 'LEONARDO', 'GEMINI', 'ZOOM', 'ADOBE']);
}
$data['akun_belum_penuh'] = $available_accounts_query

    ->group_start()

        // sharing belum penuh
        ->group_start()
            ->where('kategori', 'sharing')
            ->where('max_user <', 4)
        ->group_end()

        // private belum penuh
        ->or_group_start()
            ->where('kategori', 'private')
            ->where('max_user <', 1)
        ->group_end()

        // belum terjual (INI FIX UTAMA)
        ->or_group_start()
            ->where('kategori', 'belum_terjual')
        ->group_end()

    ->group_end()

    // ❗ JANGAN CAMPUR OR DENGAN STATUS DI SINI
    ->where('status', 'aktif')
    ->order_by('id_akun', 'ASC')
    ->get()
    ->result();

        $data['recent_activity'] = $this->get_recent_activity();
        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/dashboard', $data);
        $this->load->view('templates/footer');
    }

    public function kelola_akun()
    {
        $keyword = trim((string) $this->input->get('search_akun'));
        $product = strtoupper(trim((string) $this->input->get('product')));
        $product = in_array($product, ['SPOTIFY', 'LEONARDO', 'GEMINI', 'ZOOM', 'ADOBE'], true)
            ? $product
            : '';
        $zoom_duration = $this->normalize_zoom_duration('ZOOM', $this->input->get('durasi_zoom'));
        $tanggal_mulai = $this->normalize_date($this->input->get('tanggal_mulai'));
        $tanggal_selesai = $this->normalize_date($this->input->get('tanggal_selesai'));

        if ($tanggal_mulai && $tanggal_selesai && $tanggal_mulai > $tanggal_selesai) {
            $temp = $tanggal_mulai;
            $tanggal_mulai = $tanggal_selesai;
            $tanggal_selesai = $temp;
        }

        $this->db->from('akun');

        if ($product !== '') {
            $this->db->where('UPPER(nama_akun)', $product);
        }

        if ($product === 'ZOOM' && $zoom_duration !== null) {
            $this->db->where('durasi_zoom', $zoom_duration);
        }

        if ($keyword !== '') {
            $this->db
                ->group_start()
                ->like('nama_akun', $keyword)
                ->or_like('username', $keyword)
                ->or_like('password', $keyword)
                ->or_like('kategori', $keyword)
                ->or_like('status', $keyword)
                ->or_like('website', $keyword)
                ->or_like('note', $keyword)
                ->or_like('expired_password', $keyword)
                ->group_end();
        }

        if ($tanggal_mulai && $tanggal_selesai) {
            $this->db->where('expired_password >=', $tanggal_mulai);
            $this->db->where('expired_password <=', $tanggal_selesai);
        } elseif ($tanggal_mulai) {
            $this->db->where('expired_password', $tanggal_mulai);
        } elseif ($tanggal_selesai) {
            $this->db->where('expired_password', $tanggal_selesai);
        }

        $data['akun'] = $this->db
            ->order_by('id_akun', 'DESC')
            ->get()
            ->result();

        $data['tanggal_mulai'] = $tanggal_mulai;
        $data['tanggal_selesai'] = $tanggal_selesai;
        $data['selected_product'] = $product;
        $data['selected_zoom_duration'] = $zoom_duration;
        $data['stat_akun'] = $this->db->get('akun')->result();

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/kelola_akun', $data);
        $this->load->view('templates/footer');
    }

    public function akun_penjualan()
    {
        $data['akun'] = $this->db
            ->order_by('id_akun', 'DESC')
            ->get('akun')
            ->result();

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/akun_penjualan', $data);
        $this->load->view('templates/footer');
    }

    public function bin()
    {
        $data['bin_accounts'] = $this->db->table_exists('akun_bin')
            ? $this->db->order_by('binned_at', 'DESC')->get('akun_bin')->result()
            : [];

        if (!$this->db->table_exists('akun_bin')) {
            $this->session->set_flashdata('error', 'Tabel Bin belum tersedia. Jalankan benerin.sql terlebih dahulu.');
        }
        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/bin', $data);
        $this->load->view('templates/footer');
    }

    public function pulihkan_akun_bin($bin_id)
    {
        if (!$this->input->post()) {
            redirect('admin/bin');
            return;
        }

        if (!$this->db->table_exists('akun_bin')) {
            $this->session->set_flashdata('error', 'Tabel Bin belum tersedia. Jalankan benerin.sql terlebih dahulu.');
            redirect('admin/bin');
            return;
        }

        $bin_account = $this->db->get_where('akun_bin', ['id' => (int) $bin_id])->row();
        if (!$bin_account) {
            $this->session->set_flashdata('error', 'Data Bin tidak ditemukan atau sudah terhapus otomatis.');
            redirect('admin/bin');
            return;
        }

        $account_data = json_decode((string) $bin_account->account_data, true);
        if (!is_array($account_data)) {
            $this->session->set_flashdata('error', 'Snapshot akun tidak valid dan tidak dapat dipulihkan.');
            redirect('admin/bin');
            return;
        }

        $username = trim((string) ($account_data['username'] ?? ''));
        if ($username !== '' && $this->username_exists($username)) {
            $this->session->set_flashdata('error', 'Akun tidak dapat dipulihkan karena username sudah digunakan akun lain.');
            redirect('admin/bin');
            return;
        }

        $allowed_fields = $this->db->list_fields('akun');
        $restore_data = array_intersect_key($account_data, array_flip($allowed_fields));
        $original_id = (int) ($bin_account->original_id ?? 0);

        if ($original_id <= 0 || $this->db->where('id_akun', $original_id)->count_all_results('akun') > 0) {
            unset($restore_data['id_akun']);
        } else {
            $restore_data['id_akun'] = $original_id;
        }

        // Memulai kembali masa tujuh hari bila akun dipulihkan dengan status terjual.
        if (in_array('last_edited_at', $allowed_fields, true)) {
            $restore_data['last_edited_at'] = date('Y-m-d H:i:s');
        }
        if (in_array('last_edited_by', $allowed_fields, true)) {
            $restore_data['last_edited_by'] = $this->session->userdata('nama_user');
        }

        $this->ensure_activity_snapshot_columns();
        $this->db->trans_start();
        $restored = $this->db->insert('akun', $restore_data);
        $restored_id = isset($restore_data['id_akun'])
            ? (int) $restore_data['id_akun']
            : (int) $this->db->insert_id();

        if ($restored) {
            $this->db->where('id', (int) $bin_id)->delete('akun_bin');
            $this->db->insert('activity_log', [
                'akun_id' => $restored_id,
                'akun_nama_snapshot' => $restore_data['nama_akun'] ?? $bin_account->nama_akun,
                'akun_username_snapshot' => $restore_data['username'] ?? $bin_account->username,
                'akun_after_snapshot' => json_encode($this->account_activity_snapshot($restore_data)),
                'action' => 'pulihkan akun dari Bin',
                'changed_by' => $this->session->userdata('nama_user'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->db->trans_complete();

        if (!$restored || !$this->db->trans_status()) {
            $this->session->set_flashdata('error', 'Akun gagal dipulihkan. Silakan coba kembali.');
        } else {
            $this->session->set_flashdata('success', 'Akun berhasil dipulihkan dari Bin.');
        }

        redirect('admin/bin');
    }

    public function deactived()
    {
        $status_filter = "LOWER(REPLACE(REPLACE(status, ' ', '_'), '-', '_')) IN ('deactived', 'tidak_preimum', 'lainnya', 'ban', 'verif')";

        $data['akun'] = $this->db
            ->where($status_filter, null, false)
            ->order_by('id_akun', 'DESC')
            ->get('akun')
            ->result();

        $data['page_title'] = 'Akun Bermasalah';
        $data['table_title'] = 'Data Akun Bermasalah';
        $data['selected_product'] = '';
        $data['stat_akun'] = $this->db->get('akun')->result();

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/kelola_akun', $data);
        $this->load->view('templates/footer');
    }

    public function ganti_password_exp()
    {
        $today = date('Y-m-d');
        $expired_date = "CASE WHEN expired_password REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN expired_password ELSE NULL END";

        $data['akun'] = $this->db
            ->where($expired_date . ' IS NOT NULL', null, false)
            ->where($expired_date . ' <= ' . $this->db->escape($today), null, false)
            ->order_by($expired_date, 'ASC', false)
            ->get('akun')
            ->result();

        $data['page_title'] = 'Ganti Password Exp';
        $data['table_title'] = 'Data Akun Harus Ganti Password';

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/kelola_akun', $data);
        $this->load->view('templates/footer');
    }

    public function tambah_akun()
    {
        if ($this->input->post()) {
            $kategori = $this->input->post('kategori');
            $max_user = $this->input->post('max_user');
            $username = trim((string) $this->input->post('username'));
            $note = $this->input->post('note');

            if ($this->username_exists($username)) {
                $this->respond_akun_error('Username sudah ada, gunakan username lain.');
                return;
            }

            $status = $this->resolve_status_from_note(
                $this->resolve_akun_status($kategori, $max_user, $this->input->post('status')),
                $note
            );
            $nama_akun = $this->input->post('nama_akun');
            $durasi_zoom = $this->normalize_zoom_duration($nama_akun, $this->input->post('durasi_zoom'));

            if (strtoupper(trim((string) $nama_akun)) === 'ZOOM' && $durasi_zoom === null) {
                $this->respond_akun_error('Pilih variasi Zoom: 14 Hari atau 1 Bulan.', 'admin/tambah_akun');
                return;
            }

            $data = [
                'nama_akun'        => $nama_akun,
                'durasi_zoom'      => $durasi_zoom,
                'kategori'         => $kategori,
                'status'           => $status,
                'username'         => $username,
                'password'         => $this->input->post('password'),
                'website'          => $this->input->post('website'),
                'note'             => $note,
                'max_user'         => $max_user,
                'expired_password' => $this->normalize_date($this->input->post('expired_password')),
                'created_by'       => $this->session->userdata('nama_user'),
                'last_edited_by'   => $this->session->userdata('nama_user'),
                'last_edited_at'   => date('Y-m-d H:i:s'),
            ];




            $this->db->insert('akun', $data);
            // ambil ID akun yang baru dibuat
            $id = $this->db->insert_id();

            $this->db->insert('activity_log', [
                'akun_id'    => $id,
                'action'     => 'Tambah akun',
                'changed_by' => $this->session->userdata('nama_user'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->session->set_flashdata(
                'success',
                'Akun berhasil ditambahkan oleh ' . $this->session->userdata('nama_user')
            );

            if (
                $this->input->is_ajax_request() ||
                $this->input->get_request_header('X-Requested-With') === 'XMLHttpRequest'
            ) {
                $akun_new = $this->db->get_where('akun', ['id_akun' => $id])->row();

                $this->output->set_content_type('application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Akun berhasil ditambahkan',
                    'data' => $akun_new
                ]);
                return;
            }

            redirect('admin/kelola_akun');
        }

        $data = $this->get_notification_data();

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/tambah_akun');
        $this->load->view('templates/footer');
    }

    public function bulk_tambah_akun()
    {
        $bulk_products = $this->get_bulk_account_types();

        if (!$this->input->post()) {
            $data = $this->get_notification_data();
            $bulk_product = strtoupper(trim((string) $this->input->get('product')));
            $data['bulk_products'] = $bulk_products;
            $data['bulk_product'] = in_array($bulk_product, $bulk_products, true)
                ? $bulk_product
                : ($bulk_products[0] ?? 'SPOTIFY');
            $data['bulk_zoom_duration'] = $this->normalize_zoom_duration(
                $data['bulk_product'],
                $this->input->get('durasi_zoom')
            );

            $this->load->view('templates/header');
            $this->load->view('templates/topbar', $data);
            $this->load->view('templates/sidebar');
            $this->load->view('admin/bulk_tambah_akun', $data);
            $this->load->view('templates/footer');
            return;
        }

        $bulk_accounts = (string) $this->input->post('bulk_accounts');
        $bulk_product = strtoupper(trim((string) $this->input->post('product')));
        $bulk_product = in_array($bulk_product, $bulk_products, true)
            ? $bulk_product
            : ($bulk_products[0] ?? 'SPOTIFY');
        $bulk_zoom_duration = $this->normalize_zoom_duration($bulk_product, $this->input->post('durasi_zoom'));

        if ($bulk_product === 'ZOOM' && $bulk_zoom_duration === null) {
            $this->session->set_flashdata('error', 'Pilih variasi Zoom: 14 Hari atau 1 Bulan.');
            redirect('admin/bulk_tambah_akun?product=ZOOM');
            return;
        }

        $jenis_akun_id = $this->get_account_type_id($bulk_product);
        if ($jenis_akun_id === null) {
            $this->session->set_flashdata('error', 'Jenis akun tidak ditemukan. Tambahkan jenis akun terlebih dahulu.');
            redirect('admin/bulk_tambah_akun');
            return;
        }

        if (in_array($bulk_product, ['GEMINI', 'ADOBE'], true) && !$this->db->field_exists('two_fa', 'akun')) {
            $this->session->set_flashdata('error', 'Kolom 2FA belum tersedia di database. Jalankan benerin.sql terlebih dahulu.');
            redirect('admin/bulk_tambah_akun?product=' . rawurlencode($bulk_product));
            return;
        }

        $bulk_max_user = 0;
        $rows = $this->parse_bulk_account_rows($bulk_accounts, $bulk_product);

        $created = 0;
        $skipped = 0;
        $seen_usernames = [];

        foreach ($rows as $row) {
            $row_username = $row['username'];
            $row_password = $row['password'];
            $row_note = $row['note'];
            $row_two_fa = $row['two_fa'];

            if ($row_username === '' || $row_password === '') {
                $skipped++;
                continue;
            }

            $username_key = strtolower($row_username);

            if (isset($seen_usernames[$username_key]) || $this->username_exists($row_username)) {
                $skipped++;
                continue;
            }

            $seen_usernames[$username_key] = true;

            $data = [
                'nama_akun'        => $bulk_product,
                'durasi_zoom'      => $bulk_zoom_duration,
                'jenis_akun_id'    => $jenis_akun_id,
                'kategori'         => 'belum_terjual',
                'status'           => $this->resolve_status_from_note('aktif', $row_note, true),
                'username'         => $row_username,
                'password'         => $row_password,
                'two_fa'           => in_array($bulk_product, ['GEMINI', 'ADOBE'], true) && $row_two_fa !== '' ? $row_two_fa : null,
                'website'          => '',
                'max_user'         => $bulk_max_user,
                'expired_password' => null,
                'note'             => $row_note,
                'created_by'       => $this->session->userdata('nama_user'),
                'last_edited_by'   => $this->session->userdata('nama_user'),
                'last_edited_at'   => date('Y-m-d H:i:s'),
            ];

            $this->db->insert('akun', $data);
            $id = $this->db->insert_id();

            $this->db->insert('activity_log', [
                'akun_id'    => $id,
                'action'     => 'Bulk tambah akun',
                'changed_by' => $this->session->userdata('nama_user'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $created++;
        }

        if ($created > 0) {
            $message = $created . ' akun berhasil ditambahkan lewat bulk.';
            if ($skipped > 0) {
                $message .= ' ' . $skipped . ' baris dilewati.';
            }
            $this->session->set_flashdata('success', $message);
        } else {
            $this->session->set_flashdata('error', 'Tidak ada akun yang berhasil ditambahkan.');
        }

        redirect('admin/kelola_akun?search_akun=' . rawurlencode($bulk_product) . '&product=' . rawurlencode($bulk_product));
    }

    public function jenis_akun()
    {
        if (!$this->db->table_exists('jenis_akun')) {
            $this->session->set_flashdata('error', 'Tabel jenis_akun belum tersedia. Impor database utama terlebih dahulu.');
            redirect('admin');
            return;
        }

        $this->ensure_default_account_types();

        if ($this->input->post()) {
            $name = strtoupper(trim((string) $this->input->post('nama_akun')));
            $slug_input = trim((string) $this->input->post('slug'));
            $slug = $this->account_type_slug($slug_input !== '' ? $slug_input : $name);
            $website = trim((string) $this->input->post('website_resmi'));
            $status = $this->input->post('status') === 'nonaktif' ? 'nonaktif' : 'aktif';

            if ($name === '' || $slug === '') {
                $this->session->set_flashdata('error', 'Nama jenis akun wajib diisi.');
            } elseif ($this->find_account_type($name)) {
                $this->session->set_flashdata('error', 'Jenis akun tersebut sudah tersedia.');
            } elseif ($this->db->where('slug', $slug)->count_all_results('jenis_akun') > 0) {
                $this->session->set_flashdata('error', 'Slug sudah digunakan oleh jenis akun lain.');
            } else {
                $saved = $this->db->insert('jenis_akun', [
                    'nama_akun' => $name,
                    'slug' => $slug,
                    'website_resmi' => $website !== '' ? $website : null,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $this->session->set_flashdata(
                    $saved ? 'success' : 'error',
                    $saved ? 'Jenis akun berhasil ditambahkan.' : 'Jenis akun gagal ditambahkan.'
                );
            }

            redirect('admin/jenis_akun');
            return;
        }

        $data = $this->get_notification_data();
        $data['jenis_akun'] = $this->db
            ->order_by('nama_akun', 'ASC')
            ->get('jenis_akun')
            ->result();

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/jenis_akun', $data);
        $this->load->view('templates/footer');
    }

    public function hapus_jenis_akun($id)
    {
        if (strtoupper((string) $this->input->method()) !== 'POST') {
            show_404();
            return;
        }

        $id = (int) $id;
        $type = $this->db->get_where('jenis_akun', ['id_jenis_akun' => $id])->row();
        if (!$type) {
            $this->session->set_flashdata('error', 'Jenis akun tidak ditemukan.');
            redirect('admin/jenis_akun');
            return;
        }

        $linked_accounts = $this->db
            ->where('jenis_akun_id', $id)
            ->count_all_results('akun');
        $deleted = $this->db
            ->where('id_jenis_akun', $id)
            ->delete('jenis_akun');

        if ($deleted) {
            $message = 'Jenis akun ' . $type->nama_akun . ' berhasil dihapus.';
            if ($linked_accounts > 0) {
                $message .= ' Relasi pada ' . $linked_accounts . ' akun dikosongkan, tetapi data akunnya tetap aman.';
            }
            $this->session->set_flashdata('success', $message);
        } else {
            $this->session->set_flashdata('error', 'Jenis akun gagal dihapus.');
        }

        redirect('admin/jenis_akun');
    }

    private function parse_bulk_account_rows($bulk_accounts, $bulk_product)
    {
        $rows = [];

        // Format khusus Gemini dan Adobe:
        // 1. Email: user@gmail.com
        // - Password: password 2fa : https://contoh.test/secret
        if (in_array($bulk_product, ['GEMINI', 'ADOBE'], true)) {
            preg_match_all(
                '/(?:^|\R)\s*(?:\d+\.\s*)?Email\s*:\s*([^\s\r\n]+)[^\r\n]*\R\s*(?:-\s*)?Password\s*:\s*([^\r\n]*)/iu',
                $bulk_accounts,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $password_and_two_fa = trim((string) ($match[2] ?? ''));
                $password_parts = preg_split('/\s+2fa\s*:\s*/iu', $password_and_two_fa, 2);
                $two_fa = trim((string) ($password_parts[1] ?? ''));

                // Format Markdown [URL](URL) disimpan sebagai URL saja.
                if (preg_match('/\[[^\]]*\]\((https?:\/\/[^)]+)\)/iu', $two_fa, $url_match)) {
                    $two_fa = trim((string) $url_match[1]);
                }

                $rows[] = [
                    'username' => trim((string) ($match[1] ?? '')),
                    'password' => trim((string) ($password_parts[0] ?? '')),
                    'note' => '',
                    'two_fa' => $two_fa,
                ];
            }

            if (!empty($rows)) {
                return $rows;
            }
        }

        // Format lama tetap didukung. Kolom keempat dipakai untuk Gemini/Adobe.
        $lines = preg_split('/\r\n|\r|\n/', $bulk_accounts);

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $parts = explode('|', $line, 4);
            $rows[] = [
                'username' => trim($parts[0] ?? ''),
                'password' => trim($parts[1] ?? ''),
                'note' => trim($parts[2] ?? ''),
                'two_fa' => in_array($bulk_product, ['GEMINI', 'ADOBE'], true) ? trim($parts[3] ?? '') : '',
            ];
        }

        return $rows;
    }

    public function bulk_edit_akun()
    {
        if (!$this->input->post()) {
            $ids = $this->input->get('ids');

            if (!is_array($ids) || empty($ids)) {
                $this->session->set_flashdata('error', 'Pilih akun yang ingin diedit.');
                redirect('admin/kelola_akun');
                return;
            }

            $ids = array_values(array_filter(array_map('intval', $ids)));

            if (empty($ids)) {
                $this->session->set_flashdata('error', 'Pilih akun yang ingin diedit.');
                redirect('admin/kelola_akun');
                return;
            }

            $data['akun'] = $this->db
                ->where_in('id_akun', $ids)
                ->order_by('id_akun', 'DESC')
                ->get('akun')
                ->result();

            if (empty($data['akun'])) {
                $this->session->set_flashdata('error', 'Akun yang dipilih tidak ditemukan.');
                redirect('admin/kelola_akun');
                return;
            }

            $data = array_merge($data, $this->get_notification_data());

            $this->load->view('templates/header');
            $this->load->view('templates/topbar', $data);
            $this->load->view('templates/sidebar');
            $this->load->view('admin/bulk_edit_akun', $data);
            $this->load->view('templates/footer');
            return;
        }

        $accounts = $this->input->post('akun');

        if (!is_array($accounts) || empty($accounts)) {
            if (
                $this->input->is_ajax_request() ||
                $this->input->get_request_header('X-Requested-With') === 'XMLHttpRequest'
            ) {
                $this->output->set_content_type('application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Pilih akun yang ingin diedit'
                ]);
                return;
            }

            $this->session->set_flashdata('error', 'Pilih akun yang ingin diedit.');
            redirect('admin/kelola_akun');
        }

        $updated = 0;
        $skipped = 0;
        $seen_usernames = [];
        $now = date('Y-m-d H:i:s');
        $changed_by = $this->session->userdata('nama_user');
        $this->ensure_activity_snapshot_columns();

        foreach ($accounts as $id => $row) {
            $id = (int) $id;

            if ($id <= 0 || !is_array($row)) {
                continue;
            }

            $akun = $this->db->get_where('akun', ['id_akun' => $id])->row();

            if (!$akun) {
                continue;
            }

            $kategori = $row['kategori'] ?? '';
            $max_user = $row['max_user'] ?? 0;
            $row_username = trim((string) ($row['username'] ?? ''));
            $row_note = $row['note'] ?? '';
            $status = $this->resolve_status_from_note(
                $this->resolve_akun_status($kategori, $max_user, $row['status'] ?? ''),
                $row_note
            );
            $row_product = $row['nama_akun'] ?? '';
            $row_zoom_duration = $this->normalize_zoom_duration($row_product, $row['durasi_zoom'] ?? '');

            if (strtoupper(trim((string) $row_product)) === 'ZOOM' && $row_zoom_duration === null) {
                $skipped++;
                continue;
            }

            $username_key = strtolower($row_username);

            if (
                $row_username !== ''
                && (isset($seen_usernames[$username_key]) || $this->username_exists($row_username, $id))
            ) {
                $skipped++;
                continue;
            }

            if ($row_username !== '') {
                $seen_usernames[$username_key] = true;
            }

            $update = [
                'nama_akun'        => $row_product,
                'durasi_zoom'      => $row_zoom_duration,
                'kategori'         => $kategori,
                'status'           => $status,
                'username'         => $row_username,
                'password'         => $row['password'] ?? '',
                'website'          => $row['website'] ?? '',
                'note'             => $row_note,
                'max_user'         => $max_user,
                'expired_password' => $this->normalize_date($row['expired_password'] ?? ''),
                'last_edited_by'   => $changed_by,
                'last_edited_at'   => $now
            ];

            $this->db->where('id_akun', $id);
            $this->db->update('akun', $update);

            $this->db->insert('activity_log', [
                'akun_id'                => $id,
                'akun_nama_snapshot'     => $akun->nama_akun,
                'akun_username_snapshot' => $akun->username,
                'akun_username_before'   => $akun->username,
                'akun_username_after'    => $row_username,
                'akun_before_snapshot'   => json_encode($this->account_activity_snapshot($akun)),
                'akun_after_snapshot'    => json_encode($this->account_activity_snapshot(array_merge((array) $akun, $update))),
                'action'                 => 'bulk edit akun',
                'changed_by'             => $changed_by,
                'created_at'             => $now
            ]);

            $updated++;
        }

        if ($updated > 0) {
            $message = $updated . ' akun berhasil diedit lewat bulk.';
            if ($skipped > 0) {
                $message .= ' ' . $skipped . ' akun dilewati karena data tidak valid atau username sudah ada.';
            }
            $this->session->set_flashdata('success', $message);
        } else {
            $this->session->set_flashdata('error', 'Tidak ada akun yang berhasil diedit.');
        }

        if (
            $this->input->is_ajax_request() ||
            $this->input->get_request_header('X-Requested-With') === 'XMLHttpRequest'
        ) {
            $this->output->set_content_type('application/json');
            echo json_encode([
                'status' => $updated > 0 ? 'success' : 'error',
                'message' => $updated > 0
                    ? $updated . ' akun berhasil diedit' . ($skipped > 0 ? '. ' . $skipped . ' akun dilewati karena data tidak valid atau username sudah ada.' : '')
                    : 'Tidak ada akun yang berhasil diedit'
            ]);
            return;
        }

        redirect('admin/kelola_akun');
    }


    public function hapus_akun($id)
    {
        // ambil akun
        $akun = $this->db->get_where('akun', [
            'id_akun' => $id
        ])->row();

        // cek akun
        if (!$akun) {

            if (
                $this->input->is_ajax_request() ||
                $this->input->get_request_header('X-Requested-With') === 'XMLHttpRequest'
            ) {
                $this->output->set_content_type('application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Akun tidak ditemukan'
                ]);
                return;
            }

            $this->session->set_flashdata(
                'error',
                'Akun tidak ditemukan!'
            );

            redirect('admin/kelola_akun');
        }

        $this->ensure_activity_snapshot_columns();

        // activity log
        $this->db->insert('activity_log', [

            'akun_id'    => $id,

            'akun_nama_snapshot' => $akun->nama_akun,

            'akun_username_snapshot' => $akun->username,

            'action'     => 'hapus akun',

            'changed_by' => $this->session->userdata('nama_user'),

            'created_at' => date('Y-m-d H:i:s')

        ]);

        // hapus akun
        $this->db->where('id_akun', $id);

        $this->db->delete('akun');

        $this->session->set_flashdata(
            'success',
            'Akun berhasil dihapus'
        );

        if (
            $this->input->is_ajax_request() ||
            $this->input->get_request_header('X-Requested-With') === 'XMLHttpRequest'
        ) {
            $this->output->set_content_type('application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Akun berhasil dihapus',
                'id_akun' => $id
            ]);
            return;
        }

        redirect('admin/kelola_akun');
    }
    public function detail_akun($id)
    {
        $data['akun'] = $this->db->get_where('akun', [
            'id_akun' => $id
        ])->row();

        $data = array_merge($data, $this->get_notification_data());
        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/detail_akun', $data);
        $this->load->view('templates/footer');
    }

    public function keep()
    {
        $data['notes'] = $this->db->order_by('id', 'DESC')->get('notes')->result();

        $data = array_merge($data, $this->get_notification_data());
        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/keep', $data);
        $this->load->view('templates/footer');
    }

    // ==============================
    // TAMBAH MAX USER
    // ==============================
    public function tambah_max_user($id)
    {
        date_default_timezone_set('Asia/Jakarta');

        // Ambil data akun
        $akun = $this->db->get_where('akun', [
            'id_akun' => $id
        ])->row();

        if (!$akun) {

            $this->session->set_flashdata(
                'error',
                'Akun tidak ditemukan!'
            );

            redirect('admin');
        }

        // limit berdasarkan kategori
        $product = strtoupper(trim((string) $akun->nama_akun));
        $is_single_use_product = in_array($product, ['SPOTIFY', 'LEONARDO', 'GEMINI'], true);
        $max_limit = $is_single_use_product ? 1 : (($akun->kategori == 'private') ? 1 : 4);

        // cek limit
        if ($akun->max_user >= $max_limit) {

            $this->session->set_flashdata(
                'error',
                'Akun "' . $akun->nama_akun .
                    '" sudah mencapai batas maksimal (' .
                    $max_limit . ' user)!'
            );

            redirect('admin');
        }

        $new_max = min($max_limit, (int) $akun->max_user + 1);
        $status = ($is_single_use_product && $new_max >= $max_limit)
            ? 'terjual'
            : $this->resolve_akun_status($akun->kategori, $new_max, $akun->status);

        $this->db->set('max_user', $new_max);
        $this->db->set('status', $status);
        if ($status === 'terjual') {
            $this->db->set('kategori', 'done');
        }

        $this->db->set(
            'last_edited_by',
            $this->session->userdata('nama_user')
        );

        $this->db->set(
            'last_edited_at',
            date('Y-m-d H:i:s')
        );

        $this->db->where('id_akun', $id);

        $this->db->update('akun');

        $this->session->set_flashdata(
            'success',
                'Max user akun "' .
                $akun->nama_akun .
                '" berhasil ditambah menjadi ' .
                $new_max .
                '/' . $max_limit
        );

        redirect('admin');
    }

    // ==============================
    // AJAX TAMBAH MAX USER
    // ==============================
    public function ajax_tambah_max_user($id)
    {
        $this->output->set_content_type('application/json');

        $akun = $this->db
            ->get_where('akun', ['id_akun' => $id])
            ->row();

        if (!$akun) {

            echo json_encode([
                'status' => 'error',
                'message' => 'Akun tidak ditemukan'
            ]);
            return;
        }

        $product = strtoupper(trim((string) $akun->nama_akun));
        $is_single_use_product = in_array($product, ['SPOTIFY', 'LEONARDO', 'GEMINI'], true);
        $limit = $is_single_use_product ? 1 : (($akun->kategori == 'private') ? 1 : 4);

        if ($akun->max_user >= $limit) {

            echo json_encode([
                'status' => 'error',
                'message' => 'Max user sudah penuh'
            ]);
            return;
        }

        $new_max = min($limit, (int) $akun->max_user + 1);

        $status = ($is_single_use_product && $new_max >= $limit)
            ? 'terjual'
            : $this->resolve_akun_status($akun->kategori, $new_max, $akun->status);

        $this->db
            ->where('id_akun', $id)
            ->update('akun', [

                'max_user'       => $new_max,

                'kategori'       => $status === 'terjual' ? 'done' : $akun->kategori,

                'status'         => $status,

                'last_edited_by' => $this->session->userdata('nama_user'),

                'last_edited_at' => date('Y-m-d H:i:s')

            ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Max user berhasil ditambah',
            'max_user' => $new_max,
            'limit' => $limit,
            'akun_status' => $status
        ]);
    }

    public function save_note()
    {
        $id      = $this->input->post('id');
        $title   = $this->input->post('title');
        $content = $this->input->post('content');

        $data = [
            'title'   => $title,
            'content' => $content
        ];

        if ($id) {

            $this->db->where('id', $id);
            $this->db->update('notes', $data);
        } else {

            $this->db->insert('notes', $data);
            $id = $this->db->insert_id();
        }

        echo json_encode([
            'status' => 'ok',
            'id'     => $id
        ]);
    }

    public function profile()
    {
        $data = $this->get_notification_data();
        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/profile', $data);
        $this->load->view('templates/footer');
    }

    public function update_password()
    {
        date_default_timezone_set('Asia/Jakarta');

        if ($this->input->post()) {

            $new_password     = $this->input->post('new_password');
            $confirm_password = $this->input->post('confirm_password');
            $current_password = $this->input->post('current_password');

            // Ambil user login
            $user = $this->db->get_where('users', [
                'id_user' => $this->session->userdata('id_user')
            ])->row();

            // Validasi password lama
            if (!password_verify($current_password, $user->password)) {

                $this->session->set_flashdata(
                    'error',
                    'Password saat ini tidak cocok!'
                );

                redirect('admin/profile');
            }

            // Validasi password baru
            if ($new_password !== $confirm_password) {

                $this->session->set_flashdata(
                    'error',
                    'Password baru tidak cocok!'
                );

                redirect('admin/profile');
            }

            // Update password
            $update = [
                'password' => password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                )
            ];

            $this->db->where(
                'id_user',
                $this->session->userdata('id_user')
            );

            $this->db->update('users', $update);

            $this->session->set_flashdata(
                'success',
                'Password berhasil diubah pada ' . date('d-m-Y H:i:s')
            );

            redirect('admin/profile');
        }

        redirect('admin/profile');
    }

    public function update_profile()
    {
        date_default_timezone_set('Asia/Jakarta');

        if ($this->input->post()) {

            $username = $this->input->post('username');
            $no_wa    = $this->input->post('no_wa');

            $update = [
                'username' => $username,
                'no_wa'    => $no_wa
            ];

            $this->db->where(
                'id_user',
                $this->session->userdata('id_user')
            );

            $this->db->update('users', $update);

            // update session username
            $this->session->set_userdata(
                'username',
                $username
            );

            $this->session->set_flashdata(
                'success',
                'Profile berhasil diperbarui'
            );

            redirect('admin/profile');
        }

        redirect('admin/profile');
    }

    public function search_akun()
    {
        $this->output->set_content_type('application/json');

        $keyword = $this->input->get('q');

        if (!$keyword) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Keyword tidak boleh kosong'
            ]);
            return;
        }

        $this->db
            ->group_start()
            ->like('nama_akun', $keyword)
            ->or_like('username', $keyword)
            ->or_like('password', $keyword)
            ->or_like('kategori', $keyword)
            ->or_like('status', $keyword)
            ->or_like('website', $keyword)
            ->or_like('note', $keyword)
            ->or_like('expired_password', $keyword)
            ->group_end();

        $results = $this->db->get('akun')->result();

        echo json_encode([
            'status' => 'success',
            'data' => $results
        ]);
    }

    public function notifications()
    {
        $data = $this->get_notification_data();

        // safety default (hindari undefined variable)
        $data['expired_accounts'] = $data['expired_accounts'] ?? [];
        $data['almost_expired']   = $data['almost_expired'] ?? [];
        $data['status_problem']   = $data['status_problem'] ?? [];

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/notifications', $data);
        $this->load->view('templates/footer');
    }

    // AJAX: ambil data akun sebagai JSON (digunakan modal notif)
    // ==============================
    // AJAX: ambil data akun
    // ==============================
    public function get_akun($id)
    {
        $this->output->set_content_type('application/json');

        $akun = $this->db->get_where('akun', [
            'id_akun' => $id
        ])->row();

        if (!$akun) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Akun tidak ditemukan'
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $akun
        ]);
        exit;
    }
    public function update_akun_ajax()
    {
        $this->output->set_content_type('application/json');

        $id = $this->input->post('id_akun');
        // validasi id
        if (!$id) {

            echo json_encode([
                'status' => 'error',
                'message' => 'ID akun kosong'
            ]);

            return;
        }

        $akun_old = $this->db->get_where('akun', ['id_akun' => $id])->row();

        if (!$akun_old) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Akun tidak ditemukan'
            ]);

            return;
        }

        $kategori = $this->input->post('kategori');
        $max_user = $this->input->post('max_user');
        $note = $this->input->post('note');
        $username = trim((string) $this->input->post('username'));
        $status = $this->resolve_status_from_note(
            $this->resolve_akun_status($kategori, $max_user, $this->input->post('status')),
            $note
        );
        $nama_akun = $this->input->post('nama_akun');
        $posted_zoom_duration = $this->input->post('durasi_zoom');
        $durasi_zoom = $posted_zoom_duration === null
            && strtoupper(trim((string) ($akun_old->nama_akun ?? ''))) === 'ZOOM'
            && strtoupper(trim((string) $nama_akun)) === 'ZOOM'
                ? ($akun_old->durasi_zoom ?? null)
                : $this->normalize_zoom_duration($nama_akun, $posted_zoom_duration);

        if (strtoupper(trim((string) $nama_akun)) === 'ZOOM' && $durasi_zoom === null) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Pilih variasi Zoom: 14 Hari atau 1 Bulan.'
            ]);
            return;
        }

        $data = [
            'nama_akun'        => $nama_akun,
            'durasi_zoom'      => $durasi_zoom,
            'kategori'         => $kategori,
            'status'           => $status,
            'username'         => $username,
            'password'         => $this->input->post('password'),
            'website'          => $this->input->post('website'),
            'note'             => $note,
            'max_user'         => $max_user,
            'expired_password' => $this->normalize_date($this->input->post('expired_password')),
            'last_edited_by'   => $this->session->userdata('nama_user'),
            'last_edited_at'   => date('Y-m-d H:i:s')
        ];

        $this->ensure_activity_snapshot_columns();

        $this->db->insert('activity_log', [
            'akun_id'                => $id,
            'akun_nama_snapshot'     => $akun_old->nama_akun,
            'akun_username_snapshot' => $akun_old->username,
            'akun_username_before'   => $akun_old->username,
            'akun_username_after'    => $username,
            'akun_before_snapshot'   => json_encode($this->account_activity_snapshot($akun_old)),
            'akun_after_snapshot'    => json_encode($this->account_activity_snapshot(array_merge((array) $akun_old, $data))),
            'action'                 => 'edit akun',
            'changed_by'             => $this->session->userdata('nama_user'),
            'created_at'             => date('Y-m-d H:i:s')
        ]);
        $this->db->where('id_akun', $id);
        $this->db->update('akun', $data);

        echo json_encode([
            'status' => 'success',
            'message' => 'Akun berhasil diupdate'
        ]);
        exit;
    }


    // ==============================
    // EDIT AKUN
    // ==============================
    public function edit_akun($id)
    {
        date_default_timezone_set('Asia/Jakarta');

        $data['akun'] = $this->db->get_where('akun', [
            'id_akun' => $id
        ])->row();

        if (!$data['akun']) {

            show_404();
        }

        // ==========================
        // JIKA SUBMIT
        // ==========================
        if ($this->input->post()) {

            $password_changed = false;

            $old_password = $data['akun']->password;
            $new_password = $this->input->post('password');
            $username = trim((string) $this->input->post('username'));

            if ($this->username_exists($username, $id)) {
                $this->respond_akun_error('Username sudah ada, gunakan username lain.', 'admin');
                return;
            }

            // cek password berubah
            if ($old_password !== $new_password) {
                $password_changed = true;
            }

            $kategori = $this->input->post('kategori');
            $max_user = $this->input->post('max_user');
            $note = $this->input->post('note');
            $status = $this->resolve_status_from_note(
                $this->resolve_akun_status($kategori, $max_user, $this->input->post('status')),
                $note
            );
            $nama_akun = $this->input->post('nama_akun');
            $durasi_zoom = $this->normalize_zoom_duration($nama_akun, $this->input->post('durasi_zoom'));

            if (strtoupper(trim((string) $nama_akun)) === 'ZOOM' && $durasi_zoom === null) {
                $this->respond_akun_error('Pilih variasi Zoom: 14 Hari atau 1 Bulan.', 'admin/edit_akun/' . $id);
                return;
            }

            // data update
            $update = [

                'nama_akun'        => $nama_akun,
                'durasi_zoom'      => $durasi_zoom,
                'kategori'         => $kategori,
                'status'           => $status,
                'username'         => $username,
                'password'         => $this->input->post('password'),
                'website'          => $this->input->post('website'),
                'note'             => $note,
                'max_user'         => $max_user,
                'expired_password' => $this->normalize_date($this->input->post('expired_password')),
                'last_edited_by'   => $this->session->userdata('nama_user'),
                'last_edited_at'   => date('Y-m-d H:i:s')

            ];

            $this->ensure_activity_snapshot_columns();

            $this->db->insert('activity_log', [
                'akun_id'                => $id,
                'akun_nama_snapshot'     => $data['akun']->nama_akun,
                'akun_username_snapshot' => $data['akun']->username,
                'akun_username_before'   => $data['akun']->username,
                'akun_username_after'    => $username,
                'akun_before_snapshot'   => json_encode($this->account_activity_snapshot($data['akun'])),
                'akun_after_snapshot'    => json_encode($this->account_activity_snapshot(array_merge((array) $data['akun'], $update))),
                'action'                 => 'edit akun',
                'changed_by'             => $this->session->userdata('nama_user'),
                'created_at'             => date('Y-m-d H:i:s')
            ]);

            // update database
            $this->db->where('id_akun', $id);
            $this->db->update('akun', $update);

            // ambil data terbaru
            $akun_new = $this->db->get_where('akun', [
                'id_akun' => $id
            ])->row();

            // ==========================
            // CEK APAKAH NOTIF HARUS HILANG
            // ==========================
            $notif_removed = false;

            if (
                !empty($akun_new->expired_password) &&
                strtotime($akun_new->expired_password) > strtotime(date('Y-m-d'))
            ) {

                $notif_removed = true;
            }

            // ==========================
            // AJAX RESPONSE
            // ==========================
            if (
                $this->input->is_ajax_request() ||
                $this->input->get_request_header('X-Requested-With') === 'XMLHttpRequest'
            ) {

                $this->output->set_content_type('application/json');

                echo json_encode([

                    'status' => 'success',
                    'message' => 'Akun berhasil diubah',

                    'password_changed' => $password_changed,

                    'notif_removed' => $notif_removed,

                    'data' => $akun_new

                ]);

                return;
            }

            // ==========================
            // FLASHDATA
            // ==========================
            if ($password_changed) {

                $this->session->set_flashdata(
                    'info',
                    'Password akun ' .
                        $this->input->post('nama_akun') .
                        ' telah diubah oleh ' .
                        $this->session->userdata('nama_user')
                );

                redirect('admin');
            } else {

                $this->session->set_flashdata(
                    'success',
                    'Akun berhasil diubah oleh ' .
                        $this->session->userdata('nama_user')
                );

                redirect('admin');
            }
        }

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/edit_akun', $data);
        $this->load->view('templates/footer');
    }
    public function aktivitas()
    {
        $this->ensure_activity_snapshot_columns();
        $this->cleanup_old_activity_logs();

        $tanggal_mulai = $this->normalize_date($this->input->get('tanggal_mulai'));
        $tanggal_selesai = $this->normalize_date($this->input->get('tanggal_selesai'));

        if ($tanggal_mulai && $tanggal_selesai && $tanggal_mulai > $tanggal_selesai) {
            $temp = $tanggal_mulai;
            $tanggal_mulai = $tanggal_selesai;
            $tanggal_selesai = $temp;
        }

        $activity_min_datetime = date('Y-m-d 00:00:00', strtotime('-6 days'));

        $this->db
            ->select('activity_log.*, COALESCE(activity_log.akun_nama_snapshot, akun.nama_akun) AS nama_akun, COALESCE(activity_log.akun_username_after, activity_log.akun_username_snapshot, akun.username) AS akun_username, COALESCE(users.nama_user, activity_log.changed_by) AS changed_by_name', false)
            ->from('activity_log')
            ->join('akun', 'akun.id_akun = activity_log.akun_id', 'left')
            ->join('users', 'users.username = activity_log.changed_by OR users.nama_user = activity_log.changed_by', 'left');

        $this->db->where('activity_log.created_at >=', $activity_min_datetime);

        if ($tanggal_mulai) {
            $this->db->where('activity_log.created_at >=', $tanggal_mulai . ' 00:00:00');
        }

        if ($tanggal_selesai) {
            $this->db->where('DATE(activity_log.created_at) <=', $tanggal_selesai);
        }

        $data['activity'] = $this->db
            ->order_by('activity_log.created_at', 'DESC')
            ->get()
            ->result();

        $data['tanggal_mulai'] = $tanggal_mulai;
        $data['tanggal_selesai'] = $tanggal_selesai;

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/aktivitas', $data);
        $this->load->view('templates/footer');
    }

    public function detail_activity($id)
    {
        $this->ensure_activity_snapshot_columns();

        $activity = $this->db
            ->select('activity_log.*, COALESCE(activity_log.akun_nama_snapshot, akun.nama_akun) AS nama_akun, COALESCE(activity_log.akun_username_after, activity_log.akun_username_snapshot, akun.username) AS akun_username, COALESCE(users.nama_user, activity_log.changed_by) AS changed_by_name', false)
            ->from('activity_log')
            ->join('akun', 'akun.id_akun = activity_log.akun_id', 'left')
            ->join('users', 'users.username = activity_log.changed_by OR users.nama_user = activity_log.changed_by', 'left')
            ->where('activity_log.id', (int) $id)
            ->get()
            ->row();

        if (!$activity) {
            show_404();
        }

        $data['activity'] = $activity;
        $data['changes'] = $this->build_activity_changes($activity);
        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/detail_activity', $data);
        $this->load->view('templates/footer');
    }

    public function hapus_activity($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('activity_log');

        $this->session->set_flashdata(
            'success',
            'Log aktivitas berhasil dihapus oleh ' . $this->session->userdata('nama_user')
        );

        redirect('admin/aktivitas');
    }
    // ======================================================
    // HALAMAN KEPEGAWAIAN
    // URL : admin/kepegawaian
    // ======================================================

    public function kepegawaian()
    {
        $bulan = $this->input->get('bulan');

        if (!$bulan) {
            $bulan = date('Y-m');
        }

        $data['bulan'] = $bulan;

        // ambil user dengan tipe_user = user
        $data['pegawai'] = $this->db
            ->where('tipe_user', 'user')
            ->order_by('nama_user', 'ASC')
            ->get('users')
            ->result();

        // ambil absensi berdasarkan bulan
        $data['absensi'] = $this->db
            ->where('DATE_FORMAT(tanggal, "%Y-%m") =', $bulan)
            ->get('kepegawaian')
            ->result();

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/kepegawaian', $data);
        $this->load->view('templates/footer');
    }





    // ======================================================
    // SIMPAN ABSENSI
    // URL : admin/simpan_absensi
    // ======================================================

    public function simpan_absensi()
    {
        date_default_timezone_set('Asia/Jakarta');

        $id_user = $this->input->post('id_user');
        $tanggal = $this->input->post('tanggal');
        $status  = $this->input->post('status');
        $bulan   = $this->input->post('bulan') ?: date('Y-m');

        if (is_array($status)) {
            foreach ($status as $user_id => $dates) {
                if (!is_array($dates)) {
                    continue;
                }

                foreach ($dates as $tgl => $nilai) {
                    $nilai = trim((string) $nilai);

                    $cek = $this->db
                        ->where('id_user', $user_id)
                        ->where('tanggal', $tgl)
                        ->get('kepegawaian')
                        ->row();

                    if ($nilai === '') {
                        if ($cek) {
                            $this->db->where('id', $cek->id)->delete('kepegawaian');
                        }

                        continue;
                    }

                    if ($cek) {
                        $this->db->where('id', $cek->id);
                        $this->db->update('kepegawaian', [
                            'status' => $nilai
                        ]);
                    } else {
                        $this->db->insert('kepegawaian', [
                            'id_user'   => $user_id,
                            'tanggal'   => $tgl,
                            'status'    => $nilai,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            $this->session->set_flashdata(
                'success',
                'Absensi berhasil disimpan'
            );

            redirect('admin/kepegawaian?bulan=' . $bulan);
        }

        // validasi
        if (!$id_user || !$tanggal || !$status) {

            $this->session->set_flashdata(
                'error',
                'Data absensi tidak lengkap'
            );

            redirect('admin/kepegawaian');
        }

        // cek apakah data sudah ada
        $cek = $this->db
            ->where('id_user', $id_user)
            ->where('tanggal', $tanggal)
            ->get('kepegawaian')
            ->row();

        if ($cek) {

            // update absensi
            $this->db->where('id', $cek->id);

            $this->db->update('kepegawaian', [
                'status' => $status
            ]);
        } else {

            // insert absensi baru
            $this->db->insert('kepegawaian', [
                'id_user'   => $id_user,
                'tanggal'   => $tanggal,
                'status'    => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->session->set_flashdata(
            'success',
            'Absensi berhasil disimpan'
        );

        redirect('admin/kepegawaian?bulan=' . date('Y-m', strtotime($tanggal)));
    }





    // ======================================================
    // EXPORT EXCEL
    // URL : admin/export_kepegawaian?bulan=2026-05
    // ======================================================

    public function export_kepegawaian()
    {
        $bulan = $this->input->get('bulan');

        if (!$bulan) {
            $bulan = date('Y-m');
        }

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=kepegawaian-" . $bulan . ".xls");

        $pegawai = $this->db
            ->where('tipe_user', 'user')
            ->order_by('nama_user', 'ASC')
            ->get('users')
            ->result();

        $jumlah_hari = date('t', strtotime($bulan . '-01'));

        echo '
    <html>
    <head>
        <meta charset="UTF-8">
    </head>

    <body>

    <table border="1" cellspacing="0" cellpadding="6" style="
        border-collapse:collapse;
        font-family:Arial;
        width:auto;
    ">
    ';

        // =========================================
        // JUDUL
        // =========================================
        echo '
    <tr>
        <th colspan="' . ($jumlah_hari + 6) . '" style="
            background:#2563eb;
            color:white;
            font-size:20px;
            font-weight:bold;
            text-align:center;
            vertical-align:middle;
            height:50px;
            border:2px solid black;
            padding:10px;
        ">
            REKAP ABSENSI PEGAWAI BULAN
            ' . strtoupper(date('F Y', strtotime($bulan . '-01'))) . '
        </th>
    </tr>
    ';

        // =========================================
        // HEADER
        // =========================================
        echo '
    <tr>

        <th style="
            min-width:60px;
            height:40px;
            background:#dbeafe;
            border:2px solid black;
            text-align:center;
            vertical-align:middle;
            font-weight:bold;
            padding:8px;
        ">
            NO
        </th>

        <th style="
            min-width:220px;
            background:#dbeafe;
            border:2px solid black;
            text-align:center;
            vertical-align:middle;
            font-weight:bold;
            padding:8px;
        ">
            NAMA PEGAWAI
        </th>
    ';

        // HEADER TANGGAL
        for ($i = 1; $i <= $jumlah_hari; $i++) {

            echo '
        <th style="
            min-width:50px;
            height:40px;
            background:#bfdbfe;
            border:2px solid black;
            text-align:center;
            vertical-align:middle;
            font-weight:bold;
            padding:8px;
        ">
            ' . $i . '
        </th>
        ';
        }

        echo '
        <th style="
            min-width:60px;
            background:#16a34a;
            color:white;
            border:2px solid black;
            text-align:center;
            vertical-align:middle;
            font-weight:bold;
            padding:8px;
        ">
            M
        </th>

        <th style="
            min-width:60px;
            background:#facc15;
            border:2px solid black;
            text-align:center;
            vertical-align:middle;
            font-weight:bold;
            padding:8px;
        ">
            I
        </th>

        <th style="
            min-width:60px;
            background:#06b6d4;
            border:2px solid black;
            text-align:center;
            vertical-align:middle;
            font-weight:bold;
            padding:8px;
        ">
            S
        </th>

        <th style="
            min-width:60px;
            background:#dc2626;
            color:white;
            border:2px solid black;
            text-align:center;
            vertical-align:middle;
            font-weight:bold;
            padding:8px;
        ">
            A
        </th>
        <th style="
    min-width:60px;
    background:#e5e7eb;
    color:black;
    border:2px solid black;
    text-align:center;
    font-weight:bold;
    padding:8px;
">
    L
</th>
    </tr>
    ';

        // =========================================
        // DATA PEGAWAI
        // =========================================
        $no = 1;

        foreach ($pegawai as $p) {

            $masuk = 0;
            $izin  = 0;
            $sakit = 0;
            $alpha = 0;
            $libur = 0;

            echo '
        <tr>

            <td style="
                height:40px;
                border:1px solid black;
                text-align:center;
                vertical-align:middle;
                padding:8px;
            ">
                ' . $no++ . '
            </td>

            <td style="
                border:1px solid black;
                text-align:center;
                vertical-align:middle;
                font-weight:bold;
                padding:8px;
                min-width:220px;
            ">
                ' . strtoupper($p->nama_user) . '
            </td>
        ';

            // LOOP TANGGAL
            for ($i = 1; $i <= $jumlah_hari; $i++) {

                $tgl = $bulan . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);

                $absen = $this->db
                    ->where('id_user', $p->id_user)
                    ->where('tanggal', $tgl)
                    ->get('kepegawaian')
                    ->row();

                $status = '-';
                $bg = '#ffffff';

                if ($absen) {

                    if ($absen->status == 'masuk') {
                        $status = 'M';
                        $bg = '#dcfce7';
                        $masuk++;
                    } elseif ($absen->status == 'izin') {
                        $status = 'I';
                        $bg = '#fef9c3';
                        $izin++;
                    } elseif ($absen->status == 'sakit') {
                        $status = 'S';
                        $bg = '#cffafe';
                        $sakit++;
                    } elseif ($absen->status == 'alpha') {
                        $status = 'A';
                        $bg = '#fee2e2';
                        $alpha++;
                    } elseif ($absen->status == 'libur') {
                        $status = 'L';
                        $bg = '#e5e7eb';
                        $libur++;
                    }
                }

                echo '
            <td style="
                border:1px solid black;
                background:' . $bg . ';
                text-align:center;
                vertical-align:middle;
                font-weight:bold;
                height:40px;
                min-width:50px;
                padding:8px;
            ">
                ' . $status . '
            </td>
            ';
            }

            // TOTAL
            echo '

            <td style="
                border:2px solid black;
                background:#dcfce7;
                text-align:center;
                vertical-align:middle;
                font-weight:bold;
                min-width:60px;
                height:40px;
                padding:8px;
            ">
                ' . $masuk . '
            </td>

            <td style="
                border:2px solid black;
                background:#fef9c3;
                text-align:center;
                vertical-align:middle;
                font-weight:bold;
                min-width:60px;
                height:40px;
                padding:8px;
            ">
                ' . $izin . '
            </td>

            <td style="
                border:2px solid black;
                background:#cffafe;
                text-align:center;
                vertical-align:middle;
                font-weight:bold;
                min-width:60px;
                height:40px;
                padding:8px;
            ">
                ' . $sakit . '
            </td>

           <td style="
    border:2px solid black;
    background:#fee2e2;
    text-align:center;
    vertical-align:middle;
    font-weight:bold;
    min-width:60px;
    height:40px;
    padding:8px;
">
    ' . $alpha . '
</td>

<td style="
    border:2px solid black;
    background:#e5e7eb;
    text-align:center;
    font-weight:bold;
    min-width:60px;
    height:40px;
    padding:8px;
">
    ' . $libur . '
</td>
        </tr>
        ';
        }

        echo '
    </table>
    </body>
    </html>
    ';
    }
}
