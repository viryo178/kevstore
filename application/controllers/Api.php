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
            'nama_user' => $user['nama_user'] ?? $user['username'],
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
        $method = $this->input->method(TRUE);

        if ($id === null && $method === 'GET') {
            return $this->list_akun();
        }

        if ($id !== null && $method === 'GET') {
            return $this->show_akun((int) $id);
        }

        $this->require_login();

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

    public function dashboard()
    {
        $this->require_login();
        $this->only_methods(['GET']);

        $akun = $this->db->get('akun')->result();
        $available = $this->available_akun_query()
            ->order_by('id_akun', 'ASC')
            ->get()
            ->result();

        $stats = [
            'total_akun' => count($akun),
            'verif' => 0,
            'aktif' => 0,
            'deactived' => 0,
            'disable_x' => 0,
            'disable_email' => 0,
            'ban' => 0,
            'terjual' => 0,
            'belum_terjual' => 0,
            'available' => count($available),
        ];

        foreach ($akun as $row) {
            $status = $this->normalize_status($row->status ?? '');

            if (array_key_exists($status, $stats)) {
                $stats[$status]++;
            }

            if (($row->kategori ?? '') === 'belum_terjual') {
                $stats['belum_terjual']++;
            }
        }

        return $this->json_success('Data dashboard', [
            'stats' => $stats,
            'available_accounts' => $available,
            'notifications' => $this->notification_data(),
        ]);
    }

    public function akun_deactived()
    {
        $this->require_login();
        $this->only_methods(['GET']);

        $rows = $this->db
            ->where($this->status_problem_filter(), null, false)
            ->order_by('id_akun', 'DESC')
            ->get('akun')
            ->result();

        return $this->json_success('Data akun deactived', ['data' => $rows]);
    }

    public function akun_ganti_password_exp()
    {
        $this->require_login();
        $this->only_methods(['GET']);

        $rows = $this->expired_akun_query()
            ->order_by($this->expired_date_expression(), 'ASC', false)
            ->get('akun')
            ->result();

        return $this->json_success('Data akun harus ganti password', ['data' => $rows]);
    }

    public function bulk_akun()
    {
        $this->require_login();
        $method = $this->input->method(TRUE);

        if ($method === 'POST') {
            return $this->bulk_create_akun();
        }

        if (in_array($method, ['PUT', 'PATCH'], true)) {
            return $this->bulk_update_akun();
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

        $limit = $akun->kategori === 'private' ? 1 : 4;

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
        $this->ensure_activity_snapshot_columns();

        $activity = $this->db
            ->select('activity_log.*, COALESCE(akun.nama_akun, activity_log.akun_nama_snapshot) AS nama_akun, COALESCE(akun.username, activity_log.akun_username_snapshot) AS akun_username, COALESCE(users.nama_user, activity_log.changed_by) AS changed_by_name', false)
            ->from('activity_log')
            ->join('akun', 'akun.id_akun = activity_log.akun_id', 'left')
            ->join('users', 'users.username = activity_log.changed_by OR users.nama_user = activity_log.changed_by', 'left')
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
            $this->ensure_chat_tables();
            $conversation_id = (int) ($this->input->get('conversation_id') ?: 0);

            if ($conversation_id > 0) {
                $messages = $this->db
                    ->where('conversation_id', $conversation_id)
                    ->order_by('id', 'ASC')
                    ->get('chat_ai_messages')
                    ->result();

                return $this->json_success('Data pesan', [
                    'messages' => $messages,
                    'data' => $messages,
                ]);
            }

            $limit = max(1, min(100, (int) ($this->input->get('limit') ?: 50)));
            $messages = $this->db->table_exists('chat_messages')
                ? $this->db->order_by('created_at', 'ASC')->limit($limit)->get('chat_messages')->result()
                : [];

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

    public function chat_conversations()
    {
        $this->require_login();
        $this->only_methods(['GET']);
        $this->ensure_chat_tables();

        $search = trim((string) $this->input->get('search'));

        $this->db
            ->from('chat_conversations')
            ->where('user_id', (int) $this->session->userdata('id_user'))
            ->where('archived', 0);

        if ($search !== '') {
            $this->db->group_start()->like('title', $search)->or_like('summary', $search)->group_end();
        }

        $conversations = $this->db
            ->order_by('last_message_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(50)
            ->get()
            ->result();

        return $this->json_success('Data percakapan', ['conversations' => $conversations]);
    }

    public function chat_send()
    {
        $this->require_login();
        $this->only_methods(['POST']);
        $this->ensure_chat_tables();

        $payload = $this->payload();
        $content = trim((string) ($payload['content'] ?? $payload['message'] ?? ''));
        $conversation_id = (int) ($payload['conversation_id'] ?? 0);

        if ($content === '') {
            return $this->json_error('Pesan wajib diisi', 422);
        }

        if ($conversation_id <= 0 || !$this->owned_conversation_exists($conversation_id)) {
            $conversation_id = $this->create_chat_conversation($content);
        }

        $user_message = $this->insert_chat_ai_message($conversation_id, 'user', $content);
        $assistant = $this->build_chat_ai_response($content, $conversation_id);
        $assistant_message = $this->insert_chat_ai_message($conversation_id, 'assistant', $assistant['content'], $assistant['metadata']);

        $title = $this->conversation_title($content);
        $this->db->where('id', $conversation_id)->update('chat_conversations', [
            'title' => $title,
            'summary' => $assistant['summary'],
            'last_message_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $conversation = $this->db->get_where('chat_conversations', ['id' => $conversation_id])->row();
        $command_run_id = $this->insert_command_run($conversation_id, $assistant['command'], $content, $assistant['status'], $assistant['error']);

        return $this->json_success('Pesan diproses', [
            'conversation' => $conversation,
            'user_message' => $user_message,
            'assistant_message' => $assistant_message,
            'command_run_id' => $command_run_id,
        ], 201);
    }

    public function chat_delete()
    {
        $this->require_login();
        $this->only_methods(['POST']);
        $this->ensure_chat_tables();

        $payload = $this->payload();
        $conversation_id = (int) ($payload['conversation_id'] ?? 0);

        if ($conversation_id <= 0 || !$this->owned_conversation_exists($conversation_id)) {
            return $this->json_error('Percakapan tidak ditemukan', 404);
        }

        $this->db->where('id', $conversation_id)->update('chat_conversations', [
            'archived' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json_success('Percakapan dihapus');
    }

    public function chat_prompts()
    {
        $this->require_login();
        $this->only_methods(['GET']);

        return $this->json_success('Prompt tersedia', [
            'prompts' => [
                [
                    'id' => 1,
                    'command' => 'berapa stok hari ini',
                    'title' => 'Cek Stok',
                    'description' => 'Melihat jumlah akun yang belum terjual dan masih aktif.',
                    'prompt_body' => 'berapa stok hari ini',
                    'icon' => 'ShoppingBag',
                    'is_public' => 1,
                ],
                [
                    'id' => 2,
                    'command' => 'tambah',
                    'title' => 'Tambah Akun',
                    'description' => 'Tambah akun dengan format username|password|catatan.',
                    'prompt_body' => "tambah\nusername|password|catatan",
                    'icon' => 'Plus',
                    'is_public' => 1,
                ],
                [
                    'id' => 3,
                    'command' => 'salin no pesanan saja',
                    'title' => 'Salin No Pesanan',
                    'description' => 'Ambil nomor pesanan Shopee dari teks order.',
                    'prompt_body' => "salin no pesanan saja\nNo. Pesanan 260630DVRB0X7G",
                    'icon' => 'Clipboard',
                    'is_public' => 1,
                ],
                [
                    'id' => 4,
                    'command' => 'use username',
                    'title' => 'Pilih Akun',
                    'description' => 'Pilih akun, lihat detail, lalu ubah akun itu jadi seperti baru.',
                    'prompt_body' => "use email@gmail.com\nubah akun ini jadi seperti baru dengan username baru@gmail.com, password pass123, note catatan",
                    'icon' => 'Edit3',
                    'is_public' => 1,
                ],
            ],
        ]);
    }

    public function chat_projects()
    {
        $this->require_login();
        $this->only_methods(['GET']);

        $now = date('Y-m-d H:i:s');
        return $this->json_success('Project tersedia', [
            'projects' => [
                [
                    'id' => 1,
                    'name' => 'Kevstore',
                    'description' => 'Assistant stok dan tambah akun.',
                    'color' => '#60a5fa',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
        ]);
    }

    public function chat_history()
    {
        $this->require_login();
        $this->only_methods(['GET']);
        $this->ensure_chat_tables();

        $history = $this->db
            ->where('user_id', (int) $this->session->userdata('id_user'))
            ->order_by('id', 'DESC')
            ->limit(50)
            ->get('chat_command_runs')
            ->result();

        return $this->json_success('Riwayat command', ['history' => $history]);
    }

    private function ensure_chat_tables()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `chat_conversations` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `user_id` INT NULL,
            `title` VARCHAR(191) NOT NULL,
            `summary` TEXT NULL,
            `model` VARCHAR(80) NOT NULL DEFAULT 'fityu-local',
            `pinned` TINYINT(1) NOT NULL DEFAULT 0,
            `archived` TINYINT(1) NOT NULL DEFAULT 0,
            `last_message_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_chat_conversations_user` (`user_id`, `archived`, `last_message_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `chat_ai_messages` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `conversation_id` INT NOT NULL,
            `user_id` INT NULL,
            `role` VARCHAR(20) NOT NULL,
            `content` TEXT NOT NULL,
            `metadata_json` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_chat_ai_messages_conversation` (`conversation_id`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `chat_command_runs` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `user_id` INT NULL,
            `conversation_id` INT NULL,
            `command` VARCHAR(80) NOT NULL,
            `input_text` TEXT NULL,
            `status` VARCHAR(30) NOT NULL,
            `error_message` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            `finished_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            KEY `idx_chat_command_runs_user` (`user_id`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->cleanup_expired_chat_data();
    }

    private function cleanup_expired_chat_data()
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-7 days'));

        $expired = $this->db
            ->select('id')
            ->group_start()
                ->where('last_message_at <', $cutoff)
                ->or_group_start()
                    ->where('last_message_at IS NULL', null, false)
                    ->where('created_at <', $cutoff)
                ->group_end()
            ->group_end()
            ->get('chat_conversations')
            ->result();

        $ids = array_map(function ($row) {
            return (int) $row->id;
        }, $expired);

        if (!empty($ids)) {
            $this->db->where_in('conversation_id', $ids)->delete('chat_ai_messages');
            $this->db->where_in('conversation_id', $ids)->delete('chat_command_runs');
            $this->db->where_in('id', $ids)->delete('chat_conversations');
        }

        $this->db->where('created_at <', $cutoff)->delete('chat_messages');
    }

    private function owned_conversation_exists($conversation_id)
    {
        return $this->db
            ->where('id', (int) $conversation_id)
            ->where('user_id', (int) $this->session->userdata('id_user'))
            ->where('archived', 0)
            ->count_all_results('chat_conversations') > 0;
    }

    private function create_chat_conversation($content)
    {
        $now = date('Y-m-d H:i:s');

        $this->db->insert('chat_conversations', [
            'user_id' => (int) $this->session->userdata('id_user'),
            'title' => $this->conversation_title($content),
            'summary' => null,
            'model' => 'fityu-local',
            'pinned' => 0,
            'archived' => 0,
            'last_message_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insert_id();
    }

    private function conversation_title($content)
    {
        $title = trim(preg_replace('/\s+/', ' ', (string) $content));
        return strlen($title) > 60 ? substr($title, 0, 57) . '...' : ($title ?: 'Chat Baru');
    }

    private function insert_chat_ai_message($conversation_id, $role, $content, array $metadata = [])
    {
        $this->db->insert('chat_ai_messages', [
            'conversation_id' => (int) $conversation_id,
            'user_id' => $role === 'user' ? (int) $this->session->userdata('id_user') : null,
            'role' => $role,
            'content' => $content,
            'metadata_json' => empty($metadata) ? null : json_encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->get_where('chat_ai_messages', ['id' => $this->db->insert_id()])->row();
    }

    private function build_chat_ai_response($content, $conversation_id)
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $content)));

        if ($this->is_cancel_command($normalized)) {
            return [
                'content' => 'Oke, fitur tambah dibatalkan.',
                'summary' => 'Command dibatalkan',
                'command' => 'batal',
                'status' => 'success',
                'error' => null,
                'metadata' => ['mode' => 'idle'],
            ];
        }

        if ($this->is_order_number_copy_command($normalized)) {
            return $this->chat_order_numbers_response($content);
        }

        if ($this->conversation_is_waiting_for_order_numbers($conversation_id) && $this->contains_order_number_label($content)) {
            return $this->chat_order_numbers_response($content);
        }

        if ($this->contains_order_number_label($content)) {
            return $this->chat_order_numbers_response($content);
        }

        if ($this->is_stock_question($normalized)) {
            return $this->chat_stock_response();
        }

        if ($this->is_use_account_command($normalized)) {
            return $this->chat_use_account_response($content);
        }

        if ($this->is_reset_selected_account_command($normalized)) {
            return $this->chat_reset_selected_account_response($content, $conversation_id);
        }

        if ($this->is_update_selected_account_command($normalized)) {
            return $this->chat_update_selected_account_response($content, $conversation_id);
        }

        if ($this->is_detail_command($normalized)) {
            return $this->chat_detail_response($content);
        }

        if ($this->conversation_is_waiting_for_detail($conversation_id)) {
            return $this->chat_detail_response($content);
        }

        if ($this->is_add_account_command($content)) {
            return $this->chat_add_accounts_response($content);
        }

        if ($this->conversation_is_waiting_for_add($conversation_id) && strpos($content, '|') !== false) {
            return $this->chat_add_accounts_response($content);
        }

        if ($normalized === 'tambah' || $normalized === '/tambah') {
            return [
                'content' => "Siap. Kirim akun dengan format:\nusername|password|catatan\n\nBisa satu baris atau banyak baris sekaligus.",
                'summary' => 'Menunggu format tambah akun',
                'command' => 'tambah',
                'status' => 'waiting',
                'error' => null,
                'metadata' => ['mode' => 'add_account'],
            ];
        }

        if ($this->is_greeting($normalized)) {
            return [
                'content' => 'Halo, ada yang bisa saya bantu?',
                'summary' => 'Sapaan',
                'command' => 'greeting',
                'status' => 'success',
                'error' => null,
                'metadata' => [],
            ];
        }

        return [
            'content' => 'Fitur ini belum tersedia, bilang ke developernya buat bikin fitur ini ya!! :3',
            'summary' => 'Fitur belum tersedia',
            'command' => 'unsupported',
            'status' => 'success',
            'error' => null,
            'metadata' => [],
        ];
    }

    private function is_stock_question($normalized)
    {
        return strpos($normalized, 'stok') !== false
            || strpos($normalized, 'stock') !== false
            || strpos($normalized, 'belum terjual') !== false;
    }

    private function is_greeting($normalized)
    {
        return in_array($normalized, [
            'halo',
            'hallo',
            'helo',
            'hello',
            'hai',
            'hi',
            'p',
            'test',
            'tes',
            'assalamualaikum',
            'assalamu alaikum',
        ], true);
    }

    private function is_cancel_command($normalized)
    {
        return in_array($normalized, [
            'batal',
            'cancel',
            'batalkan',
            'gak jadi',
            'ga jadi',
            'tidak jadi',
            'nggak jadi',
        ], true);
    }

    private function is_order_number_copy_command($normalized)
    {
        return strpos($normalized, 'salin') !== false
            && strpos($normalized, 'pesanan') !== false;
    }

    private function is_detail_command($normalized)
    {
        return $normalized === 'detail'
            || $normalized === '/detail'
            || strpos($normalized, 'detail ') === 0
            || strpos($normalized, '/detail ') === 0;
    }

    private function is_use_account_command($normalized)
    {
        return strpos($normalized, 'use ') === 0
            || strpos($normalized, '/use ') === 0;
    }

    private function is_reset_selected_account_command($normalized)
    {
        return strpos($normalized, 'ubah akun ini') !== false
            && (
                strpos($normalized, 'seperti baru') !== false
                || strpos($normalized, 'jadi baru') !== false
            );
    }

    private function is_update_selected_account_command($normalized)
    {
        return strpos($normalized, 'ubah ') === 0
            || strpos($normalized, 'ganti ') === 0
            || strpos($normalized, 'edit ') === 0;
    }

    private function chat_use_account_response($content)
    {
        $keyword = trim(preg_replace('/^\/?use\s*/i', '', (string) $content));

        if ($keyword === '') {
            return [
                'content' => 'Tulis username setelah use. Contoh: use email@gmail.com',
                'summary' => 'Use akun tanpa username',
                'command' => 'use_account',
                'status' => 'failed',
                'error' => 'Username kosong',
                'metadata' => [],
            ];
        }

        $account = $this->find_single_account_for_use($keyword);

        if (!$account) {
            return [
                'content' => 'Akun tidak ditemukan untuk: ' . $keyword,
                'summary' => 'Use akun gagal',
                'command' => 'use_account',
                'status' => 'failed',
                'error' => 'Akun tidak ditemukan',
                'metadata' => ['keyword' => $keyword],
            ];
        }

        return [
            'content' => $this->format_account_detail($account) . "\n\nAkun ini sudah dipilih. Kamu bisa ketik:\nubah akun ini jadi seperti baru dengan username ..., password ..., note ...",
            'summary' => 'Akun dipilih: ' . $account->username,
            'command' => 'use_account',
            'status' => 'success',
            'error' => null,
            'metadata' => [
                'mode' => 'selected_account',
                'selected_account_id' => (int) $account->id_akun,
                'selected_username' => $account->username,
            ],
        ];
    }

    private function chat_reset_selected_account_response($content, $conversation_id)
    {
        $selected = $this->selected_account_from_conversation($conversation_id);

        if (!$selected) {
            return [
                'content' => 'Pilih akunnya dulu dengan format: use username',
                'summary' => 'Belum ada akun dipilih',
                'command' => 'reset_selected_account',
                'status' => 'failed',
                'error' => 'Akun target belum dipilih',
                'metadata' => [],
            ];
        }

        $account = $this->db->get_where('akun', ['id_akun' => (int) $selected['id']])->row();

        if (!$account) {
            return [
                'content' => 'Akun yang tadi dipilih sudah tidak ditemukan. Pilih ulang dengan: use username',
                'summary' => 'Akun target hilang',
                'command' => 'reset_selected_account',
                'status' => 'failed',
                'error' => 'Akun target tidak ditemukan',
                'metadata' => [],
            ];
        }

        $fields = $this->parse_reset_account_fields($content);

        if (empty($fields['username']) || empty($fields['password'])) {
            return [
                'content' => "Format belum lengkap. Contoh:\nubah akun ini jadi seperti baru dengan username email@gmail.com, password pass123, note catatan",
                'summary' => 'Format ubah akun kurang lengkap',
                'command' => 'reset_selected_account',
                'status' => 'failed',
                'error' => 'Username atau password kosong',
                'metadata' => [
                    'mode' => 'selected_account',
                    'selected_account_id' => (int) $account->id_akun,
                    'selected_username' => $account->username,
                ],
            ];
        }

        if ($this->username_exists($fields['username'], (int) $account->id_akun)) {
            return [
                'content' => 'Username baru sudah dipakai akun lain: ' . $fields['username'],
                'summary' => 'Username baru duplikat',
                'command' => 'reset_selected_account',
                'status' => 'failed',
                'error' => 'Username sudah ada',
                'metadata' => [
                    'mode' => 'selected_account',
                    'selected_account_id' => (int) $account->id_akun,
                    'selected_username' => $account->username,
                ],
            ];
        }

        $update = [
            'nama_akun' => 'Grok',
            'kategori' => 'belum_terjual',
            'status' => 'aktif',
            'username' => $fields['username'],
            'password' => $fields['password'],
            'website' => '',
            'note' => $fields['note'],
            'max_user' => 0,
            'expired_password' => null,
            'last_edited_by' => $this->actor_name(),
            'last_edited_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->where('id_akun', (int) $account->id_akun)->update('akun', $update);
        $this->log_activity((int) $account->id_akun, 'Fityu chat ubah akun seperti baru', $account);

        $updated = $this->db->get_where('akun', ['id_akun' => (int) $account->id_akun])->row();

        return [
            'content' => "Akun berhasil diubah jadi seperti baru.\n\n" . $this->format_account_detail($updated),
            'summary' => 'Akun diubah jadi seperti baru',
            'command' => 'reset_selected_account',
            'status' => 'success',
            'error' => null,
            'metadata' => [
                'mode' => 'selected_account',
                'selected_account_id' => (int) $updated->id_akun,
                'selected_username' => $updated->username,
            ],
        ];
    }

    private function chat_update_selected_account_response($content, $conversation_id)
    {
        $selected = $this->selected_account_from_conversation($conversation_id);

        if (!$selected) {
            return [
                'content' => 'Pilih akunnya dulu dengan format: use username',
                'summary' => 'Belum ada akun dipilih',
                'command' => 'update_selected_account',
                'status' => 'failed',
                'error' => 'Akun target belum dipilih',
                'metadata' => [],
            ];
        }

        $account = $this->db->get_where('akun', ['id_akun' => (int) $selected['id']])->row();

        if (!$account) {
            return [
                'content' => 'Akun yang tadi dipilih sudah tidak ditemukan. Pilih ulang dengan: use username',
                'summary' => 'Akun target hilang',
                'command' => 'update_selected_account',
                'status' => 'failed',
                'error' => 'Akun target tidak ditemukan',
                'metadata' => [],
            ];
        }

        $changes = $this->parse_update_account_fields($content);

        if (empty($changes)) {
            return [
                'content' => "Field belum terbaca. Contoh:\nubah status jadi aktif\nubah password jadi pass123\nubah note jadi catatan baru\nubah username jadi email@gmail.com",
                'summary' => 'Format update akun belum terbaca',
                'command' => 'update_selected_account',
                'status' => 'failed',
                'error' => 'Field update kosong',
                'metadata' => [
                    'mode' => 'selected_account',
                    'selected_account_id' => (int) $account->id_akun,
                    'selected_username' => $account->username,
                ],
            ];
        }

        if (isset($changes['username']) && $this->username_exists($changes['username'], (int) $account->id_akun)) {
            return [
                'content' => 'Username baru sudah dipakai akun lain: ' . $changes['username'],
                'summary' => 'Username baru duplikat',
                'command' => 'update_selected_account',
                'status' => 'failed',
                'error' => 'Username sudah ada',
                'metadata' => [
                    'mode' => 'selected_account',
                    'selected_account_id' => (int) $account->id_akun,
                    'selected_username' => $account->username,
                ],
            ];
        }

        $changes['last_edited_by'] = $this->actor_name();
        $changes['last_edited_at'] = date('Y-m-d H:i:s');

        $this->db->where('id_akun', (int) $account->id_akun)->update('akun', $changes);
        $this->log_activity((int) $account->id_akun, 'Fityu chat ubah akun', $account);

        $updated = $this->db->get_where('akun', ['id_akun' => (int) $account->id_akun])->row();

        return [
            'content' => "Akun berhasil diubah.\n\n" . $this->format_account_detail($updated),
            'summary' => 'Akun berhasil diubah',
            'command' => 'update_selected_account',
            'status' => 'success',
            'error' => null,
            'metadata' => [
                'mode' => 'selected_account',
                'selected_account_id' => (int) $updated->id_akun,
                'selected_username' => $updated->username,
            ],
        ];
    }

    private function chat_detail_response($content)
    {
        $keyword = trim(preg_replace('/^\/?detail\s*/i', '', (string) $content));

        if ($keyword === '') {
            return [
                'content' => 'Silakan kirim username atau kata yang ada di catatan akun.',
                'summary' => 'Menunggu keyword detail akun',
                'command' => 'detail',
                'status' => 'waiting',
                'error' => null,
                'metadata' => ['mode' => 'detail_account'],
            ];
        }

        $accounts = $this->find_accounts_for_detail($keyword);

        if (empty($accounts)) {
            return [
                'content' => 'Akun tidak ditemukan untuk keyword: ' . $keyword,
                'summary' => 'Detail akun tidak ditemukan',
                'command' => 'detail',
                'status' => 'failed',
                'error' => 'Akun tidak ditemukan',
                'metadata' => ['keyword' => $keyword],
            ];
        }

        $lines = ['Detail akun ditemukan:'];

        foreach ($accounts as $index => $account) {
            if ($index > 0) {
                $lines[] = '';
            }

            $lines[] = 'Akun #' . ($index + 1);
            $lines[] = 'Nama: ' . ($account->nama_akun ?? '-');
            $lines[] = 'Username: ' . ($account->username ?? '-');
            $lines[] = 'Password: ' . ($account->password ?? '-');
            $lines[] = 'Kategori: ' . ($account->kategori ?? '-');
            $lines[] = 'Status: ' . ($account->status ?? '-');
            $lines[] = 'Max user: ' . (isset($account->max_user) ? $account->max_user : '-');
            $lines[] = 'Expired password: ' . (!empty($account->expired_password) ? $account->expired_password : '-');
            $lines[] = 'Website: ' . (!empty($account->website) ? $account->website : '-');
            $lines[] = 'Catatan: ' . (!empty($account->note) ? $account->note : '-');
        }

        if (count($accounts) >= 5) {
            $lines[] = '';
            $lines[] = 'Saya tampilkan 5 hasil pertama. Perjelas keyword kalau ingin hasil lebih spesifik.';
        }

        return [
            'content' => implode("\n", $lines),
            'summary' => 'Menampilkan detail akun',
            'command' => 'detail',
            'status' => 'success',
            'error' => null,
            'metadata' => ['keyword' => $keyword, 'count' => count($accounts)],
        ];
    }

    private function find_accounts_for_detail($keyword)
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return [];
        }

        return $this->db
            ->group_start()
                ->where('username', $keyword)
                ->or_like('username', $keyword)
                ->or_like('note', $keyword)
            ->group_end()
            ->order_by('id_akun', 'DESC')
            ->limit(5)
            ->get('akun')
            ->result();
    }

    private function find_single_account_for_use($keyword)
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return null;
        }

        $exact = $this->db
            ->where('username', $keyword)
            ->get('akun')
            ->row();

        if ($exact) {
            return $exact;
        }

        return $this->db
            ->group_start()
                ->like('username', $keyword)
                ->or_like('note', $keyword)
            ->group_end()
            ->order_by('id_akun', 'DESC')
            ->limit(1)
            ->get('akun')
            ->row();
    }

    private function selected_account_from_conversation($conversation_id)
    {
        $message = $this->db
            ->where('conversation_id', (int) $conversation_id)
            ->where('role', 'assistant')
            ->where('metadata_json IS NOT NULL', null, false)
            ->order_by('id', 'DESC')
            ->limit(20)
            ->get('chat_ai_messages')
            ->result();

        foreach ($message as $row) {
            $metadata = json_decode($row->metadata_json, true);

            if (!is_array($metadata)) {
                continue;
            }

            if (($metadata['mode'] ?? '') === 'idle') {
                return null;
            }

            if (!empty($metadata['selected_account_id'])) {
                return [
                    'id' => (int) $metadata['selected_account_id'],
                    'username' => $metadata['selected_username'] ?? null,
                ];
            }
        }

        return null;
    }

    private function format_account_detail($account)
    {
        return implode("\n", [
            'Detail akun:',
            'Nama: ' . ($account->nama_akun ?? '-'),
            'Username: ' . ($account->username ?? '-'),
            'Password: ' . ($account->password ?? '-'),
            'Kategori: ' . ($account->kategori ?? '-'),
            'Status: ' . ($account->status ?? '-'),
            'Max user: ' . (isset($account->max_user) ? $account->max_user : '-'),
            'Expired password: ' . (!empty($account->expired_password) ? $account->expired_password : '-'),
            'Website: ' . (!empty($account->website) ? $account->website : '-'),
            'Catatan: ' . (!empty($account->note) ? $account->note : '-'),
        ]);
    }

    private function parse_reset_account_fields($content)
    {
        return [
            'username' => $this->extract_named_value($content, 'username'),
            'password' => $this->extract_named_value($content, 'password'),
            'note' => $this->extract_named_value($content, 'note'),
        ];
    }

    private function parse_update_account_fields($content)
    {
        $fields = [
            'username' => ['username', 'user'],
            'password' => ['password', 'pass', 'pw'],
            'note' => ['note', 'catatan'],
            'status' => ['status'],
            'kategori' => ['kategori', 'tipe'],
            'nama_akun' => ['nama akun', 'nama'],
            'website' => ['website', 'web'],
            'max_user' => ['max user', 'max_user', 'slot'],
            'expired_password' => ['expired password', 'expired_password', 'exp password', 'exp'],
        ];

        $changes = [];

        foreach ($fields as $field => $aliases) {
            foreach ($aliases as $alias) {
                $value = $this->extract_update_value($content, $alias);

                if ($value === '') {
                    continue;
                }

                if ($field === 'max_user') {
                    $changes[$field] = max(0, (int) $value);
                } elseif ($field === 'expired_password') {
                    $changes[$field] = $this->normalize_date($value);
                } elseif ($field === 'status') {
                    $changes[$field] = $this->normalize_status($value);
                } elseif ($field === 'kategori') {
                    $changes[$field] = strtolower(str_replace(' ', '_', trim($value)));
                } else {
                    $changes[$field] = trim($value);
                }

                break;
            }
        }

        return array_filter($changes, function ($value) {
            return $value !== '' && $value !== null;
        });
    }

    private function extract_update_value($content, $name)
    {
        $all_aliases = 'username|user|password|pass|pw|note|catatan|status|kategori|tipe|nama akun|nama|website|web|max user|max_user|slot|expired password|expired_password|exp password|exp';
        $name_pattern = str_replace('\ ', '\s+', preg_quote($name, '/'));
        $boundary_pattern = str_replace(' ', '\s+', $all_aliases);
        $pattern = '/\b' . $name_pattern . '\b\s*(?:jadi|menjadi|ke|=|:)?\s*(?:"([^"]*)"|\'([^\']*)\'|(.+?))(?=\s*(?:,|\bdan\b)?\s+\b(?:' . $boundary_pattern . ')\b|\s*$)/i';

        if (!preg_match($pattern, (string) $content, $matches)) {
            return '';
        }

        if (isset($matches[1]) && $matches[1] !== '') {
            return trim($matches[1]);
        }

        if (isset($matches[2]) && $matches[2] !== '') {
            return trim($matches[2]);
        }

        return isset($matches[3]) ? trim($matches[3], " \t\n\r\0\x0B,.") : '';
    }

    private function extract_named_value($content, $name)
    {
        $pattern = '/\b' . preg_quote($name, '/') . '\s*(?:=|:)?\s*(?:"([^"]*)"|\'([^\']*)\'|(.+?))(?=\s*(?:,|\bdan\b)?\s+\b(?:username|password|note)\b|\s*$)/i';

        if (!preg_match($pattern, (string) $content, $matches)) {
            return '';
        }

        $value = '';

        if (isset($matches[1]) && $matches[1] !== '') {
            $value = $matches[1];
        } elseif (isset($matches[2]) && $matches[2] !== '') {
            $value = $matches[2];
        } elseif (isset($matches[3])) {
            $value = $matches[3];
        }

        return trim($value, " \t\n\r\0\x0B,.");
    }

    private function chat_order_numbers_response($content)
    {
        $order_numbers = $this->extract_order_numbers($content);

        if (empty($order_numbers)) {
            return [
                'content' => "Saya akan menyalin no pesanan, tapi masukkan dulu teks yang ada no pesanannya. Ini belum ada isinya.",
                'summary' => 'Menunggu teks order Shopee',
                'command' => 'salin_no_pesanan',
                'status' => 'waiting',
                'error' => null,
                'metadata' => ['mode' => 'order_numbers'],
            ];
        }

        return [
            'content' => implode("\n", $order_numbers),
            'summary' => 'Menyalin ' . count($order_numbers) . ' nomor pesanan',
            'command' => 'salin_no_pesanan',
            'status' => 'success',
            'error' => null,
            'metadata' => ['order_numbers' => $order_numbers],
        ];
    }

    private function contains_order_number_label($content)
    {
        return preg_match('/No\.\s*Pesanan\s+[A-Z0-9]+/i', (string) $content) === 1;
    }

    private function extract_order_numbers($content)
    {
        preg_match_all('/No\.\s*Pesanan\s+([A-Z0-9]+)/i', (string) $content, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $numbers = [];
        foreach ($matches[1] as $number) {
            $number = strtoupper(trim($number));
            if ($number !== '' && !in_array($number, $numbers, true)) {
                $numbers[] = $number;
            }
        }

        return $numbers;
    }

    private function is_add_account_command($content)
    {
        $trimmed = trim((string) $content);
        $normalized = strtolower($trimmed);

        return strpos($trimmed, '|') !== false
            || strpos($normalized, "tambah\n") === 0
            || strpos($normalized, "tambah ") === 0
            || strpos($normalized, "/tambah\n") === 0
            || strpos($normalized, "/tambah ") === 0;
    }

    private function conversation_is_waiting_for_add($conversation_id)
    {
        $last = $this->db
            ->where('conversation_id', (int) $conversation_id)
            ->where('role', 'assistant')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('chat_ai_messages')
            ->row();

        if (!$last || empty($last->metadata_json)) {
            return false;
        }

        $metadata = json_decode($last->metadata_json, true);
        return is_array($metadata) && ($metadata['mode'] ?? '') === 'add_account';
    }

    private function conversation_is_waiting_for_order_numbers($conversation_id)
    {
        $last = $this->db
            ->where('conversation_id', (int) $conversation_id)
            ->where('role', 'assistant')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('chat_ai_messages')
            ->row();

        if (!$last || empty($last->metadata_json)) {
            return false;
        }

        $metadata = json_decode($last->metadata_json, true);
        return is_array($metadata) && ($metadata['mode'] ?? '') === 'order_numbers';
    }

    private function conversation_is_waiting_for_detail($conversation_id)
    {
        $last = $this->db
            ->where('conversation_id', (int) $conversation_id)
            ->where('role', 'assistant')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('chat_ai_messages')
            ->row();

        if (!$last || empty($last->metadata_json)) {
            return false;
        }

        $metadata = json_decode($last->metadata_json, true);
        return is_array($metadata) && ($metadata['mode'] ?? '') === 'detail_account';
    }

    private function chat_stock_response()
    {
        $belum_terjual = $this->db
            ->where('kategori', 'belum_terjual')
            ->where('status', 'aktif')
            ->count_all_results('akun');

        $available = $this->available_akun_query()->count_all_results();

        $examples = $this->db
            ->select('username, note, last_edited_at')
            ->where('kategori', 'belum_terjual')
            ->where('status', 'aktif')
            ->order_by('id_akun', 'DESC')
            ->limit(5)
            ->get('akun')
            ->result();

        $lines = [
            'Stok hari ini:',
            '- Belum terjual aktif: ' . $belum_terjual . ' akun',
            '- Total akun tersedia termasuk sharing/private yang belum penuh: ' . $available . ' akun',
        ];

        if (!empty($examples)) {
            $lines[] = '';
            $lines[] = 'Contoh akun terbaru:';
            foreach ($examples as $row) {
                $note = trim((string) ($row->note ?? ''));
                $lines[] = '- ' . $row->username . ($note !== '' ? ' - ' . $note : '');
            }
        }

        return [
            'content' => implode("\n", $lines),
            'summary' => 'Cek stok akun belum terjual',
            'command' => 'stok',
            'status' => 'success',
            'error' => null,
            'metadata' => [
                'belum_terjual' => $belum_terjual,
                'available' => $available,
            ],
        ];
    }

    private function chat_add_accounts_response($content)
    {
        $rows = $this->parse_chat_account_rows($content);

        if (empty($rows)) {
            return [
                'content' => "Format belum terbaca. Pakai format:\nusername|password|catatan",
                'summary' => 'Format tambah akun salah',
                'command' => 'tambah',
                'status' => 'failed',
                'error' => 'Format akun tidak valid',
                'metadata' => ['mode' => 'add_account'],
            ];
        }

        $created = [];
        $skipped = [];
        $seen = [];
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $index => $row) {
            $username = trim((string) ($row['username'] ?? ''));
            $password = trim((string) ($row['password'] ?? ''));
            $note = trim((string) ($row['note'] ?? ''));

            if ($username === '' || $password === '') {
                $skipped[] = 'Baris ' . ($index + 1) . ': username/password kosong';
                continue;
            }

            $username_key = strtolower($username);
            if (isset($seen[$username_key]) || $this->username_exists($username)) {
                $skipped[] = $username . ': username sudah ada';
                continue;
            }

            $seen[$username_key] = true;

            $data = [
                'nama_akun' => 'Grok',
                'kategori' => 'belum_terjual',
                'status' => $this->resolve_status_from_note('aktif', $note, true),
                'username' => $username,
                'password' => $password,
                'website' => '',
                'note' => $note,
                'max_user' => 0,
                'expired_password' => null,
                'created_by' => $this->actor_name(),
                'last_edited_by' => $this->actor_name(),
                'last_edited_at' => $now,
            ];

            $this->db->insert('akun', $data);
            $id = $this->db->insert_id();
            $this->log_activity($id, 'Fityu chat tambah akun');
            $created[] = $username;
        }

        $lines = ['Tambah akun selesai.'];
        $lines[] = '- Berhasil: ' . count($created);
        $lines[] = '- Dilewati: ' . count($skipped);

        if (!empty($created)) {
            $lines[] = '';
            $lines[] = 'Akun masuk: ' . implode(', ', array_slice($created, 0, 10));
        }

        if (!empty($skipped)) {
            $lines[] = '';
            $lines[] = 'Catatan dilewati:';
            foreach (array_slice($skipped, 0, 10) as $skip) {
                $lines[] = '- ' . $skip;
            }
        }

        return [
            'content' => implode("\n", $lines),
            'summary' => 'Tambah ' . count($created) . ' akun dari chat',
            'command' => 'tambah',
            'status' => count($created) > 0 ? 'success' : 'failed',
            'error' => count($created) > 0 ? null : 'Tidak ada akun yang berhasil ditambahkan',
            'metadata' => [
                'created' => $created,
                'skipped' => $skipped,
            ],
        ];
    }

    private function parse_chat_account_rows($content)
    {
        $content = preg_replace('/^\/?tambah\s*/i', '', trim((string) $content));
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            $parts = explode('|', $line, 3);
            $rows[] = [
                'username' => trim($parts[0] ?? ''),
                'password' => trim($parts[1] ?? ''),
                'note' => trim($parts[2] ?? ''),
            ];
        }

        return $rows;
    }

    private function resolve_status_from_note($status, $note, $use_note_status = false)
    {
        if (!$use_note_status) {
            return $status;
        }

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

    private function insert_command_run($conversation_id, $command, $input_text, $status, $error)
    {
        $this->db->insert('chat_command_runs', [
            'user_id' => (int) $this->session->userdata('id_user'),
            'conversation_id' => (int) $conversation_id,
            'command' => $command,
            'input_text' => $input_text,
            'status' => $status,
            'error_message' => $error,
            'created_at' => date('Y-m-d H:i:s'),
            'finished_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insert_id();
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
        $username = trim((string) $payload['username']);

        if ($this->username_exists($username)) {
            return $this->json_error('Username sudah ada, gunakan username lain', 409);
        }

        $status = $this->resolve_akun_status($kategori, $max_user, (string) ($payload['status'] ?? 'aktif'));
        $now = date('Y-m-d H:i:s');

        $data = [
            'nama_akun' => trim((string) $payload['nama_akun']),
            'kategori' => $kategori,
            'status' => $status,
            'username' => $username,
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
        $username = array_key_exists('username', $payload) ? trim((string) $payload['username']) : $akun->username;

        if ($this->username_exists($username, $id)) {
            return $this->json_error('Username sudah ada, gunakan username lain', 409);
        }

        $status = $this->resolve_akun_status($kategori, $max_user, (string) ($payload['status'] ?? $akun->status));

        $update = [
            'nama_akun' => array_key_exists('nama_akun', $payload) ? trim((string) $payload['nama_akun']) : $akun->nama_akun,
            'kategori' => $kategori,
            'status' => $status,
            'username' => $username,
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

    private function bulk_create_akun()
    {
        $payload = $this->payload();
        $accounts = $this->bulk_create_rows($payload);

        if (empty($accounts)) {
            return $this->json_error('Data akun bulk kosong', 422);
        }

        $created = [];
        $skipped = [];
        $seen = [];
        $now = date('Y-m-d H:i:s');

        foreach ($accounts as $index => $row) {
            $username = trim((string) ($row['username'] ?? ''));
            $password = trim((string) ($row['password'] ?? ''));

            if ($username === '' || $password === '') {
                $skipped[] = ['index' => $index, 'reason' => 'Username atau password kosong'];
                continue;
            }

            $username_key = strtolower($username);

            if (isset($seen[$username_key]) || $this->username_exists($username)) {
                $skipped[] = ['index' => $index, 'username' => $username, 'reason' => 'Username sudah ada'];
                continue;
            }

            $seen[$username_key] = true;

            $data = [
                'nama_akun' => trim((string) ($row['nama_akun'] ?? 'Grok')),
                'kategori' => (string) ($row['kategori'] ?? 'belum_terjual'),
                'status' => $this->resolve_akun_status(
                    (string) ($row['kategori'] ?? 'belum_terjual'),
                    (int) ($row['max_user'] ?? 0),
                    (string) ($row['status'] ?? 'aktif')
                ),
                'username' => $username,
                'password' => $password,
                'website' => (string) ($row['website'] ?? ''),
                'note' => (string) ($row['note'] ?? ''),
                'max_user' => (int) ($row['max_user'] ?? 0),
                'expired_password' => $this->normalize_date($row['expired_password'] ?? ''),
                'created_by' => $this->actor_name(),
                'last_edited_by' => $this->actor_name(),
                'last_edited_at' => $now,
            ];

            $this->db->insert('akun', $data);
            $id = $this->db->insert_id();
            $this->log_activity($id, 'Bulk tambah akun');
            $created[] = $this->db->get_where('akun', ['id_akun' => $id])->row();
        }

        $response = [
            'created_count' => count($created),
            'skipped_count' => count($skipped),
            'data' => $created,
            'skipped' => $skipped,
        ];

        if (count($created) === 0) {
            return $this->json_error('Tidak ada akun yang berhasil ditambahkan', 422, $response);
        }

        return $this->json_success('Bulk tambah akun selesai', $response, 201);
    }

    private function bulk_update_akun()
    {
        $payload = $this->payload();
        $accounts = $payload['accounts'] ?? $payload['akun'] ?? [];

        if (!is_array($accounts) || empty($accounts)) {
            return $this->json_error('Data akun bulk kosong', 422);
        }

        $updated = [];
        $skipped = [];
        $seen = [];

        foreach ($accounts as $key => $row) {
            if (!is_array($row)) {
                $skipped[] = ['index' => $key, 'reason' => 'Format akun tidak valid'];
                continue;
            }

            $id = (int) ($row['id_akun'] ?? $row['id'] ?? $key);

            if ($id <= 0) {
                $skipped[] = ['index' => $key, 'reason' => 'ID akun kosong'];
                continue;
            }

            $akun = $this->db->get_where('akun', ['id_akun' => $id])->row();

            if (!$akun) {
                $skipped[] = ['id_akun' => $id, 'reason' => 'Akun tidak ditemukan'];
                continue;
            }

            $username = array_key_exists('username', $row) ? trim((string) $row['username']) : $akun->username;
            $username_key = strtolower($username);

            if ($username !== '' && (isset($seen[$username_key]) || $this->username_exists($username, $id))) {
                $skipped[] = ['id_akun' => $id, 'username' => $username, 'reason' => 'Username sudah ada'];
                continue;
            }

            if ($username !== '') {
                $seen[$username_key] = true;
            }

            $kategori = (string) ($row['kategori'] ?? $akun->kategori);
            $max_user = (int) ($row['max_user'] ?? $akun->max_user);
            $status = $this->resolve_akun_status($kategori, $max_user, (string) ($row['status'] ?? $akun->status));

            $update = [
                'nama_akun' => array_key_exists('nama_akun', $row) ? trim((string) $row['nama_akun']) : $akun->nama_akun,
                'kategori' => $kategori,
                'status' => $status,
                'username' => $username,
                'password' => array_key_exists('password', $row) ? (string) $row['password'] : $akun->password,
                'website' => array_key_exists('website', $row) ? (string) $row['website'] : $akun->website,
                'note' => array_key_exists('note', $row) ? (string) $row['note'] : $akun->note,
                'max_user' => $max_user,
                'expired_password' => array_key_exists('expired_password', $row)
                    ? $this->normalize_date($row['expired_password'])
                    : $akun->expired_password,
                'last_edited_by' => $this->actor_name(),
                'last_edited_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->where('id_akun', $id)->update('akun', $update);
            $this->log_activity($id, 'bulk edit akun');
            $updated[] = $this->db->get_where('akun', ['id_akun' => $id])->row();
        }

        $response = [
            'updated_count' => count($updated),
            'skipped_count' => count($skipped),
            'data' => $updated,
            'skipped' => $skipped,
        ];

        if (count($updated) === 0) {
            return $this->json_error('Tidak ada akun yang berhasil diedit', 422, $response);
        }

        return $this->json_success('Bulk edit akun selesai', $response);
    }

    private function bulk_create_rows(array $payload)
    {
        if (isset($payload['accounts']) && is_array($payload['accounts'])) {
            return $payload['accounts'];
        }

        $bulk = (string) ($payload['bulk_accounts'] ?? '');

        if ($bulk === '') {
            return [];
        }

        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', $bulk);

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $parts = explode('|', $line, 3);
            $rows[] = [
                'username' => trim($parts[0] ?? ''),
                'password' => trim($parts[1] ?? ''),
                'note' => trim($parts[2] ?? ''),
                'nama_akun' => 'Grok',
                'kategori' => 'belum_terjual',
                'status' => 'aktif',
                'max_user' => 0,
            ];
        }

        return $rows;
    }

    private function delete_akun($id)
    {
        $akun = $this->db->get_where('akun', ['id_akun' => $id])->row();

        if (!$akun) {
            return $this->json_error('Akun tidak ditemukan', 404);
        }

        $this->log_activity($id, 'hapus akun', $akun);
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
        $status = $this->normalize_status($status);

        if (in_array($status, $manual_statuses, true)) {
            return $status;
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

    private function normalize_status($status)
    {
        return strtolower(str_replace([' ', '-'], '_', trim((string) $status)));
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

    private function status_problem_filter()
    {
        return "LOWER(REPLACE(REPLACE(status, ' ', '_'), '-', '_')) IN ('deactived', 'disable_x', 'disable_email', 'ban', 'verif')";
    }

    private function expired_date_expression()
    {
        return "CASE WHEN expired_password REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN expired_password ELSE NULL END";
    }

    private function expired_akun_query()
    {
        $today = date('Y-m-d');
        $expired_date = $this->expired_date_expression();

        return $this->db
            ->where($expired_date . ' IS NOT NULL', null, false)
            ->where($expired_date . ' <= ' . $this->db->escape($today), null, false);
    }

    private function available_akun_query()
    {
        return $this->db
            ->from('akun')
            ->group_start()
                ->group_start()
                    ->where('kategori', 'sharing')
                    ->where('max_user <', 4)
                ->group_end()
                ->or_group_start()
                    ->where('kategori', 'private')
                    ->where('max_user <', 1)
                ->group_end()
                ->or_group_start()
                    ->where('kategori', 'belum_terjual')
                ->group_end()
            ->group_end()
            ->where('status', 'aktif');
    }

    private function notification_data()
    {
        $today = date('Y-m-d');
        $expired_date = $this->expired_date_expression();

        $expiring_accounts = $this->expired_akun_query()
            ->order_by($expired_date, 'ASC', false)
            ->get('akun')
            ->result();

        $status_problem = $this->db
            ->where($this->status_problem_filter(), null, false)
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
    }

    private function log_activity($akun_id, $action, $akun = null)
    {
        if (!$this->db->table_exists('activity_log')) {
            return;
        }

        $this->ensure_activity_snapshot_columns();

        $data = [
            'akun_id' => $akun_id,
            'action' => $action,
            'changed_by' => $this->actor_name(),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($akun) {
            $data['akun_nama_snapshot'] = $akun->nama_akun ?? null;
            $data['akun_username_snapshot'] = $akun->username ?? null;
        }

        $this->db->insert('activity_log', $data);
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
            $this->abort_json_error('Unauthorized. Silakan login terlebih dahulu.', 401);
        }
    }

    private function require_admin()
    {
        if ($this->session->userdata('tipe_user') !== 'admin') {
            $this->abort_json_error('Forbidden. Akses admin diperlukan.', 403);
        }
    }

    private function only_methods(array $methods)
    {
        if (!in_array($this->input->method(TRUE), $methods, true)) {
            $this->abort_json_error('Method tidak didukung', 405);
        }
    }

    private function abort_json_error($message, $status_code = 400, array $extra = [])
    {
        $this->json_error($message, $status_code, $extra);
        $this->output->_display();
        exit;
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
