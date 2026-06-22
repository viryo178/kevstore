<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Jakarta');
        $this->load->database();
    }

    public function login()
    {
        $this->only_methods(['POST']);

        $payload = $this->payload();
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->json_error('Username dan password wajib diisi', 422);
        }

        $user = $this->db->get_where('users', ['username' => $username])->row_array();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->json_error('Username atau password salah', 401);
        }

        $session = [
            'id_user' => $user['id_user'],
            'username' => $user['username'],
            'nama_user' => $user['username'],
            'tipe_user' => $user['tipe_user'],
            'status' => $user['login'] ?? null,
            'last_login_at' => date('Y-m-d H:i:s'),
        ];

        $this->session->set_userdata($session);

        return $this->json_success('Login berhasil', [
            'user' => $this->public_user($user),
        ]);
    }

    public function logout()
    {
        $this->only_methods(['POST']);
        $this->session->sess_destroy();

        return $this->json_success('Logout berhasil');
    }

    public function me()
    {
        $this->require_login();

        return $this->json_success('Data user aktif', [
            'user' => [
                'id_user' => $this->session->userdata('id_user'),
                'username' => $this->session->userdata('username'),
                'nama_user' => $this->session->userdata('nama_user'),
                'tipe_user' => $this->session->userdata('tipe_user'),
                'last_login_at' => $this->session->userdata('last_login_at'),
            ],
        ]);
    }

    public function akun($id = null)
    {
        $this->require_login();
        $method = $this->input->method(TRUE);

        if ($id === null && $method === 'GET') {
            return $this->list_akun();
        }

        if ($id !== null && $method === 'GET') {
            return $this->show_akun((int) $id);
        }

        if ($id === null && $method === 'POST') {
            return $this->create_akun();
        }

        if ($id !== null && in_array($method, ['PUT', 'PATCH'], true)) {
            return $this->update_akun((int) $id);
        }

        if ($id !== null && $method === 'DELETE') {
            return $this->delete_akun((int) $id);
        }

        return $this->json_error('Method tidak didukung', 405);
    }

    public function tambah_max_user($id)
    {
        $this->require_login();
        $this->only_methods(['POST']);

        $akun = $this->db->get_where('akun', ['id_akun' => (int) $id])->row();

        if (!$akun) {
            return $this->json_error('Akun tidak ditemukan', 404);
        }

        $limit = $akun->kategori === 'private' ? 1 : 5;

        if ((int) $akun->max_user >= $limit) {
            return $this->json_error('Max user sudah penuh', 422);
        }

        $new_max = (int) $akun->max_user + 1;
        $status = $this->resolve_akun_status($akun->kategori, $new_max, $akun->status);
        $now = date('Y-m-d H:i:s');

        $this->db->where('id_akun', (int) $id)->update('akun', [
            'max_user' => $new_max,
            'status' => $status,
            'last_edited_by' => $this->actor_name(),
            'last_edited_at' => $now,
        ]);

        $updated = $this->db->get_where('akun', ['id_akun' => (int) $id])->row();

        return $this->json_success('Max user berhasil ditambah', [
            'data' => $updated,
            'limit' => $limit,
        ]);
    }

    public function notifications()
    {
        $this->require_login();

        return $this->json_success('Data notifikasi', $this->notification_data());
    }

    public function activity()
    {
        $this->require_login();

        $activity = $this->db
            ->select('activity_log.*, akun.nama_akun')
            ->from('activity_log')
            ->join('akun', 'akun.id_akun = activity_log.akun_id', 'left')
            ->order_by('activity_log.created_at', 'DESC')
            ->get()
            ->result();

        return $this->json_success('Data aktivitas', ['data' => $activity]);
    }

    public function kepegawaian()
    {
        $this->require_login();
        $method = $this->input->method(TRUE);

        if ($method === 'GET') {
            $bulan = $this->input->get('bulan') ?: date('Y-m');

            $pegawai = $this->db
                ->where('tipe_user', 'user')
                ->order_by('nama_user', 'ASC')
                ->get('users')
                ->result();

            $absensi = $this->db
                ->where('DATE_FORMAT(tanggal, "%Y-%m") =', $bulan)
                ->get('kepegawaian')
                ->result();

            return $this->json_success('Data kepegawaian', [
                'bulan' => $bulan,
                'pegawai' => $pegawai,
                'absensi' => $absensi,
            ]);
        }

        if ($method === 'POST') {
            return $this->save_absensi();
        }

        return $this->json_error('Method tidak didukung', 405);
    }

    public function chat_messages()
    {
        $this->require_login();
        $method = $this->input->method(TRUE);

        if ($method === 'GET') {
            if (!$this->db->table_exists('chat_messages')) {
                return $this->json_success('Tabel chat belum dibuat', ['data' => []]);
            }

            $limit = max(1, min(100, (int) ($this->input->get('limit') ?: 50)));
            $messages = $this->db
                ->order_by('created_at', 'ASC')
                ->limit($limit)
                ->get('chat_messages')
                ->result();

            return $this->json_success('Data pesan', ['data' => $messages]);
        }

        if ($method === 'POST') {
            if (!$this->db->table_exists('chat_messages')) {
                return $this->json_error('Tabel chat_messages belum dibuat', 500);
            }

            $payload = $this->payload();
            $message = trim((string) ($payload['message'] ?? ''));

            if ($message === '') {
                return $this->json_error('Pesan wajib diisi', 422);
            }

            $this->db->insert('chat_messages', [
                'sender' => $this->actor_name(),
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $row = $this->db->get_where('chat_messages', ['id' => $this->db->insert_id()])->row();

            return $this->json_success('Pesan berhasil dikirim', ['data' => $row], 201);
        }

        return $this->json_error('Method tidak didukung', 405);
    }

    public function notes($id = null)
    {
        $this->require_login();
        $method = $this->input->method(TRUE);

        if ($method === 'GET') {
            if ($id !== null) {
                $note = $this->db->get_where('notes', ['id' => (int) $id])->row();

                if (!$note) {
                    return $this->json_error('Catatan tidak ditemukan', 404);
                }

                return $this->json_success('Detail catatan', ['data' => $note]);
            }

            $notes = $this->db->order_by('id', 'DESC')->get('notes')->result();
            return $this->json_success('Data catatan', ['data' => $notes]);
        }

        if ($method === 'POST' && $id === null) {
            $payload = $this->payload();
            $title = trim((string) ($payload['title'] ?? ''));
            $content = (string) ($payload['content'] ?? '');

            if ($title === '') {
                return $this->json_error('Judul catatan wajib diisi', 422);
            }

            $this->db->insert('notes', [
                'title' => $title,
                'content' => $content,
            ]);

            $note = $this->db->get_where('notes', ['id' => $this->db->insert_id()])->row();
            return $this->json_success('Catatan berhasil dibuat', ['data' => $note], 201);
        }

        if ($id !== null && in_array($method, ['PUT', 'PATCH'], true)) {
            $payload = $this->payload();
            $note = $this->db->get_where('notes', ['id' => (int) $id])->row();

            if (!$note) {
                return $this->json_error('Catatan tidak ditemukan', 404);
            }

            $this->db->where('id', (int) $id)->update('notes', [
                'title' => array_key_exists('title', $payload) ? trim((string) $payload['title']) : $note->title,
                'content' => array_key_exists('content', $payload) ? (string) $payload['content'] : $note->content,
            ]);

            $updated = $this->db->get_where('notes', ['id' => (int) $id])->row();
            return $this->json_success('Catatan berhasil diupdate', ['data' => $updated]);
        }

        if ($id !== null && $method === 'DELETE') {
            $note = $this->db->get_where('notes', ['id' => (int) $id])->row();

            if (!$note) {
                return $this->json_error('Catatan tidak ditemukan', 404);
            }

            $this->db->where('id', (int) $id)->delete('notes');
            return $this->json_success('Catatan berhasil dihapus');
        }

        return $this->json_error('Method tidak didukung', 405);
    }

    public function users()
    {
        $this->require_login();
        $this->require_admin();
        $this->only_methods(['GET']);

        $users = $this->db
            ->select('id_user, username, tipe_user, no_wa, login, otp_expired')
            ->order_by('id_user', 'ASC')
            ->get('users')
            ->result();

        return $this->json_success('Data users', ['data' => $users]);
    }

    private function list_akun()
    {
        $keyword = trim((string) $this->input->get('q'));
        $status = trim((string) $this->input->get('status'));
        $kategori = trim((string) $this->input->get('kategori'));
        $limit = max(1, min(200, (int) ($this->input->get('limit') ?: 100)));
        $offset = max(0, (int) ($this->input->get('offset') ?: 0));

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

        if ($status !== '') {
            $this->db->where('status', $status);
        }

        if ($kategori !== '') {
            $this->db->where('kategori', $kategori);
        }

        $rows = $this->db
            ->order_by('id_akun', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->result();

        return $this->json_success('Data akun', [
            'data' => $rows,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    private function show_akun($id)
    {
        $akun = $this->db->get_where('akun', ['id_akun' => $id])->row();

        if (!$akun) {
            return $this->json_error('Akun tidak ditemukan', 404);
        }

        return $this->json_success('Detail akun', ['data' => $akun]);
    }

    private function create_akun()
    {
        $payload = $this->payload();
        $required = ['nama_akun', 'kategori', 'username', 'password'];

        foreach ($required as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                return $this->json_error($field . ' wajib diisi', 422);
            }
        }

        $kategori = (string) $payload['kategori'];
        $max_user = (int) ($payload['max_user'] ?? 0);
        $status = $this->resolve_akun_status($kategori, $max_user, (string) ($payload['status'] ?? 'aktif'));
        $now = date('Y-m-d H:i:s');

        $data = [
            'nama_akun' => trim((string) $payload['nama_akun']),
            'kategori' => $kategori,
            'status' => $status,
            'username' => trim((string) $payload['username']),
            'password' => (string) $payload['password'],
            'website' => (string) ($payload['website'] ?? ''),
            'note' => (string) ($payload['note'] ?? ''),
            'max_user' => $max_user,
            'expired_password' => $this->normalize_date($payload['expired_password'] ?? ''),
            'created_by' => $this->actor_name(),
            'last_edited_by' => $this->actor_name(),
            'last_edited_at' => $now,
        ];

        $this->db->insert('akun', $data);
        $id = $this->db->insert_id();
        $this->log_activity($id, 'Tambah akun');

        $akun = $this->db->get_where('akun', ['id_akun' => $id])->row();

        return $this->json_success('Akun berhasil ditambahkan', ['data' => $akun], 201);
    }

    private function update_akun($id)
    {
        $akun = $this->db->get_where('akun', ['id_akun' => $id])->row();

        if (!$akun) {
            return $this->json_error('Akun tidak ditemukan', 404);
        }

        $payload = $this->payload();
        $kategori = (string) ($payload['kategori'] ?? $akun->kategori);
        $max_user = (int) ($payload['max_user'] ?? $akun->max_user);
        $status = $this->resolve_akun_status($kategori, $max_user, (string) ($payload['status'] ?? $akun->status));

        $update = [
            'nama_akun' => array_key_exists('nama_akun', $payload) ? trim((string) $payload['nama_akun']) : $akun->nama_akun,
            'kategori' => $kategori,
            'status' => $status,
            'username' => array_key_exists('username', $payload) ? trim((string) $payload['username']) : $akun->username,
            'password' => array_key_exists('password', $payload) ? (string) $payload['password'] : $akun->password,
            'website' => array_key_exists('website', $payload) ? (string) $payload['website'] : $akun->website,
            'note' => array_key_exists('note', $payload) ? (string) $payload['note'] : $akun->note,
            'max_user' => $max_user,
            'expired_password' => array_key_exists('expired_password', $payload)
                ? $this->normalize_date($payload['expired_password'])
                : $akun->expired_password,
            'last_edited_by' => $this->actor_name(),
            'last_edited_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->where('id_akun', $id)->update('akun', $update);
        $this->log_activity($id, 'edit akun');

        $updated = $this->db->get_where('akun', ['id_akun' => $id])->row();

        return $this->json_success('Akun berhasil diupdate', ['data' => $updated]);
    }

    private function delete_akun($id)
    {
        $akun = $this->db->get_where('akun', ['id_akun' => $id])->row();

        if (!$akun) {
            return $this->json_error('Akun tidak ditemukan', 404);
        }

        $this->log_activity($id, 'hapus akun');
        $this->db->where('id_akun', $id)->delete('akun');

        return $this->json_success('Akun berhasil dihapus', ['id_akun' => $id]);
    }

    private function save_absensi()
    {
        $payload = $this->payload();
        $id_user = (int) ($payload['id_user'] ?? 0);
        $tanggal = trim((string) ($payload['tanggal'] ?? ''));
        $status = trim((string) ($payload['status'] ?? ''));
        $allowed = ['masuk', 'izin', 'sakit', 'alpha', 'libur'];

        if ($id_user <= 0 || $tanggal === '' || !in_array($status, $allowed, true)) {
            return $this->json_error('id_user, tanggal, dan status valid wajib diisi', 422);
        }

        $existing = $this->db
            ->where('id_user', $id_user)
            ->where('tanggal', $tanggal)
            ->get('kepegawaian')
            ->row();

        if ($existing) {
            $this->db->where('id', $existing->id)->update('kepegawaian', [
                'status' => $status,
            ]);
            $id = $existing->id;
        } else {
            $this->db->insert('kepegawaian', [
                'id_user' => $id_user,
                'tanggal' => $tanggal,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $id = $this->db->insert_id();
        }

        $absensi = $this->db->get_where('kepegawaian', ['id' => $id])->row();

        return $this->json_success('Absensi berhasil disimpan', ['data' => $absensi]);
    }

    private function payload()
    {
        $content_type = (string) $this->input->server('CONTENT_TYPE');
        $raw = $this->input->raw_input_stream;

        if (stripos($content_type, 'application/json') !== false && trim($raw) !== '') {
            $json = json_decode($raw, true);
            return is_array($json) ? $json : [];
        }

        if (in_array($this->input->method(TRUE), ['PUT', 'PATCH', 'DELETE'], true)) {
            parse_str($raw, $data);
            return is_array($data) ? $data : [];
        }

        return $this->input->post(NULL, true) ?: [];
    }

    private function normalize_date($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

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

    private function notification_data()
    {
        $today = date('Y-m-d');
        $expired_date = "CASE WHEN expired_password REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN expired_password ELSE NULL END";

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

        $status_problem = $this->db
            ->where_in('status', ['deactived', 'verif', 'ban', 'disable_x', 'disable_email'])
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

        return [
            'expiring_accounts' => $expiring_accounts,
            'expired_accounts' => $expired_accounts,
            'almost_expired' => $almost_expired,
            'status_problem' => $status_problem,
            'notif_count' => count($expiring_accounts) + count($status_problem),
        ];
    }

    private function log_activity($akun_id, $action)
    {
        if (!$this->db->table_exists('activity_log')) {
            return;
        }

        $this->db->insert('activity_log', [
            'akun_id' => $akun_id,
            'action' => $action,
            'changed_by' => $this->actor_name(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function public_user($user)
    {
        return [
            'id_user' => $user['id_user'],
            'username' => $user['username'],
            'tipe_user' => $user['tipe_user'],
            'no_wa' => $user['no_wa'] ?? null,
        ];
    }

    private function actor_name()
    {
        return $this->session->userdata('nama_user') ?: $this->session->userdata('username') ?: 'api';
    }

    private function require_login()
    {
        if (!$this->session->userdata('id_user')) {
            $this->json_error('Unauthorized. Silakan login terlebih dahulu.', 401);
            exit;
        }
    }

    private function require_admin()
    {
        if ($this->session->userdata('tipe_user') !== 'admin') {
            $this->json_error('Forbidden. Akses admin diperlukan.', 403);
            exit;
        }
    }

    private function only_methods(array $methods)
    {
        if (!in_array($this->input->method(TRUE), $methods, true)) {
            $this->json_error('Method tidak didukung', 405);
            exit;
        }
    }

    private function json_success($message, array $extra = [], $status_code = 200)
    {
        return $this->json(array_merge([
            'status' => 'success',
            'message' => $message,
        ], $extra), $status_code);
    }

    private function json_error($message, $status_code = 400, array $extra = [])
    {
        return $this->json(array_merge([
            'status' => 'error',
            'message' => $message,
        ], $extra), $status_code);
    }

    private function json(array $body, $status_code = 200)
    {
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($body));
    }
}
