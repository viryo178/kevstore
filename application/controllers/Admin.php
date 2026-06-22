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
        $manual_statuses = ['deactived', 'ban', 'disable_x', 'disable_email', 'verif', 'terjual'];
        $status = strtolower(str_replace([' ', '-'], '_', trim((string) $status)));

        if (in_array($status, $manual_statuses, true)) {
            return $status;
        }

        $max_user = max(0, (int) $max_user);

        if ($kategori === 'private') {
            return $max_user >= 1 ? 'terjual' : 'aktif';
        }

        if ($kategori === 'sharing') {
            return $max_user >= 5 ? 'terjual' : 'aktif';
        }

        return 'aktif';
    }

    private function resolve_status_from_note($status, $note)
    {
        $note = strtolower((string) $note);
        $note = str_replace(['-', '_'], ' ', $note);

        if (preg_match('/\bdisable\s*x\b/', $note)) {
            return 'disable_x';
        }

        if (preg_match('/\bdisable\s*email\b/', $note)) {
            return 'disable_email';
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
            'disable_x',
            'disable_email'
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

        // akun yang masih bisa dipakai di dashboard
$data['akun_belum_penuh'] = $this->db
    ->from('akun')

    ->group_start()

        // sharing belum penuh
        ->group_start()
            ->where('kategori', 'sharing')
            ->where('max_user <', 5)
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

        $this->db->from('akun');

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

        $data['akun'] = $this->db
            ->order_by('id_akun', 'DESC')
            ->get()
            ->result();

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/kelola_akun', $data);
        $this->load->view('templates/footer');
    }

    public function deactived()
    {
        $status_filter = "LOWER(REPLACE(REPLACE(status, ' ', '_'), '-', '_')) IN ('deactived', 'disable_x', 'disable_email', 'ban', 'verif')";
        $note_filter = "LOWER(REPLACE(REPLACE(note, '_', ' '), '-', ' ')) LIKE '%disable x%' OR LOWER(REPLACE(REPLACE(note, '_', ' '), '-', ' ')) LIKE '%disable email%' OR LOWER(REPLACE(REPLACE(note, '_', ' '), '-', ' ')) LIKE '%ban%'";

        $data['akun'] = $this->db
            ->group_start()
                ->where($status_filter, null, false)
                ->or_where($note_filter, null, false)
            ->group_end()
            ->order_by('id_akun', 'DESC')
            ->get('akun')
            ->result();

        $data['page_title'] = 'Deactived';
        $data['table_title'] = 'Data Akun Deactived';

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

            $data = [
                'nama_akun'        => $this->input->post('nama_akun'),
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
        if (!$this->input->post()) {
            $data = $this->get_notification_data();

            $this->load->view('templates/header');
            $this->load->view('templates/topbar', $data);
            $this->load->view('templates/sidebar');
            $this->load->view('admin/bulk_tambah_akun', $data);
            $this->load->view('templates/footer');
            return;
        }

        $bulk_accounts = (string) $this->input->post('bulk_accounts');
        $lines = preg_split('/\r\n|\r|\n/', $bulk_accounts);

        $created = 0;
        $skipped = 0;
        $seen_usernames = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $parts = explode('|', $line, 3);
            $row_username = trim($parts[0] ?? '');
            $row_password = trim($parts[1] ?? '');
            $row_note = trim($parts[2] ?? '');

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
                'nama_akun'        => 'Grok',
                'kategori'         => 'belum_terjual',
                'status'           => $this->resolve_status_from_note('aktif', $row_note),
                'username'         => $row_username,
                'password'         => $row_password,
                'website'          => '',
                'max_user'         => 0,
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

        redirect('admin/kelola_akun');
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
                'nama_akun'        => $row['nama_akun'] ?? '',
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
                'akun_id'    => $id,
                'action'     => 'bulk edit akun',
                'changed_by' => $changed_by,
                'created_at' => $now
            ]);

            $updated++;
        }

        if ($updated > 0) {
            $message = $updated . ' akun berhasil diedit lewat bulk.';
            if ($skipped > 0) {
                $message .= ' ' . $skipped . ' akun dilewati karena username sudah ada.';
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
                    ? $updated . ' akun berhasil diedit' . ($skipped > 0 ? '. ' . $skipped . ' akun dilewati karena username sudah ada.' : '')
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

        // activity log
        $this->db->insert('activity_log', [

            'akun_id'    => $id,

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
        $max_limit = ($akun->kategori == 'private') ? 1 : 5;

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

        $new_max = $akun->max_user + 1;
        $status = $this->resolve_akun_status($akun->kategori, $new_max, $akun->status);

        // increment
        $this->db->set('max_user', 'max_user+1', FALSE);
        $this->db->set('status', $status);

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

        $limit = ($akun->kategori == 'private') ? 1 : 5;

        if ($akun->max_user >= $limit) {

            echo json_encode([
                'status' => 'error',
                'message' => 'Max user sudah penuh'
            ]);
            return;
        }

        $new_max = $akun->max_user + 1;

        $status = $this->resolve_akun_status($akun->kategori, $new_max, $akun->status);

        $this->db
            ->where('id_akun', $id)
            ->update('akun', [

                'max_user'       => $new_max,

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

        $kategori = $this->input->post('kategori');
        $max_user = $this->input->post('max_user');
        $note = $this->input->post('note');
        $status = $this->resolve_status_from_note(
            $this->resolve_akun_status($kategori, $max_user, $this->input->post('status')),
            $note
        );

        $data = [
            'nama_akun'        => $this->input->post('nama_akun'),
            'kategori'         => $kategori,
            'status'           => $status,
            'username'         => $this->input->post('username'),
            'password'         => $this->input->post('password'),
            'website'          => $this->input->post('website'),
            'note'             => $note,
            'max_user'         => $max_user,
            'expired_password' => $this->normalize_date($this->input->post('expired_password')),
            'last_edited_by'   => $this->session->userdata('nama_user'),
            'last_edited_at'   => date('Y-m-d H:i:s')
        ];

        $this->db->insert('activity_log', [
            'akun_id'    => $id,
            'action'     => 'edit akun',
            'changed_by' => $this->session->userdata('nama_user'),
            'created_at' => date('Y-m-d H:i:s')
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
                $this->respond_akun_error('Username sudah ada, gunakan username lain.', 'admin/detail_akun/' . $id);
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

            // data update
            $update = [

                'nama_akun'        => $this->input->post('nama_akun'),
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

            $this->db->insert('activity_log', [
                'akun_id'    => $id,
                'action'     => 'edit akun',
                'changed_by' => $this->session->userdata('nama_user'),
                'created_at' => date('Y-m-d H:i:s')
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

                redirect('admin/detail_akun/' . $id);
            } else {

                $this->session->set_flashdata(
                    'success',
                    'Akun berhasil diubah oleh ' .
                        $this->session->userdata('nama_user')
                );

                redirect('admin/kelola_akun');
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
        $data['activity'] = $this->db
            ->select('activity_log.*, akun.nama_akun, akun.username AS akun_username')
            ->from('activity_log')
            ->join('akun', 'akun.id_akun = activity_log.akun_id', 'left')
            ->order_by('activity_log.created_at', 'DESC')
            ->get()
            ->result();

        $data = array_merge($data, $this->get_notification_data());

        $this->load->view('templates/header');
        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar');
        $this->load->view('admin/aktivitas', $data);
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
