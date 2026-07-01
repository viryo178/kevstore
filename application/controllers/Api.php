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

        if ($this->is_delete_current_chat_command($content)) {
            if ($conversation_id <= 0 || !$this->owned_conversation_exists($conversation_id)) {
                return $this->json_success('Tidak ada riwayat chat yang dipilih', [
                    'deleted_conversation' => false,
                    'deleted_conversation_id' => null,
                ]);
            }

            $this->hard_delete_chat_conversation($conversation_id);

            return $this->json_success('Riwayat chat dihapus', [
                'deleted_conversation' => true,
                'deleted_conversation_id' => $conversation_id,
            ]);
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

        $this->hard_delete_chat_conversation($conversation_id);

        return $this->json_success('Percakapan dihapus permanen', [
            'deleted_conversation' => true,
            'deleted_conversation_id' => $conversation_id,
        ]);
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

    private function is_delete_current_chat_command($content)
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $content)));
        $normalized = preg_replace('/[.!?]+$/', '', $normalized);

        return in_array($normalized, [
            'hapus riwayat chat ini',
            'hapus chat ini',
            'hapus chat ini history ini',
            'hapus percakapan ini',
            'hapus conversation ini',
            'delete chat ini',
            'delete this chat',
            'bersihkan chat ini',
        ], true);
    }

    private function hard_delete_chat_conversation($conversation_id)
    {
        $conversation_id = (int) $conversation_id;

        $this->db->where('conversation_id', $conversation_id)->delete('chat_ai_messages');
        $this->db->where('conversation_id', $conversation_id)->delete('chat_command_runs');
        $this->db
            ->where('id', $conversation_id)
            ->where('user_id', (int) $this->session->userdata('id_user'))
            ->delete('chat_conversations');
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
        $normalized = trim(preg_replace('/[?!.,;:]+$/', '', $normalized));

        if ($this->is_cancel_command($normalized)) {
            return [
                'content' => 'Oke, saya batalkan ya. Kita mulai lagi dari awal kalau kamu mau.',
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

        if ($this->is_take_unsold_account_command($normalized)) {
            return $this->chat_take_unsold_account_response();
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
                'content' => "Siap, kirim datanya dengan format ini ya:\nusername|password|catatan\n\nBoleh satu akun atau banyak akun sekaligus.",
                'summary' => 'Menunggu format tambah akun',
                'command' => 'tambah',
                'status' => 'waiting',
                'error' => null,
                'metadata' => ['mode' => 'add_account'],
            ];
        }

        if ($this->is_greeting($normalized)) {
            return [
                'content' => 'Halo, saya di sini. Mau cek stok, cari detail akun, atau tambah akun?',
                'summary' => 'Sapaan',
                'command' => 'greeting',
                'status' => 'success',
                'error' => null,
                'metadata' => [],
            ];
        }

        $introduced_name = $this->extract_user_introduced_name($normalized);
        if ($introduced_name !== null) {
            return $this->chat_user_name_intro_response($introduced_name);
        }

        if ($this->is_user_name_question($normalized)) {
            return $this->chat_user_name_question_response($conversation_id);
        }

        if ($this->is_ai_studio_status_command($normalized)) {
            return $this->chat_ai_studio_status_response();
        }

        if ($this->is_feature_list_command($normalized)) {
            return $this->chat_feature_list_response();
        }

        if ($this->is_date_question($normalized)) {
            return $this->chat_date_response();
        }

        if ($this->is_deactived_count_question($normalized)) {
            return $this->chat_deactived_count_response();
        }

        if ($this->is_available_account_question($normalized)) {
            return $this->chat_stock_response();
        }

        if ($this->is_yesterday_created_accounts_question($normalized)) {
            return $this->chat_yesterday_created_accounts_response();
        }

        if ($this->is_stock_question($normalized)) {
            return $this->chat_stock_response();
        }

        if ($this->is_basic_question($normalized)) {
            return $this->chat_basic_response($normalized);
        }

        return $this->chat_google_search_response($content);
    }

    private function is_stock_question($normalized)
    {
        return strpos($normalized, 'stok') !== false
            || strpos($normalized, 'stock') !== false
            || strpos($normalized, 'belum terjual') !== false;
    }

    private function is_take_unsold_account_command($normalized)
    {
        $asks_for_account = strpos($normalized, 'akun') !== false
            && (
                strpos($normalized, 'bawa') !== false
                || strpos($normalized, 'ambil') !== false
                || strpos($normalized, 'kasih') !== false
                || strpos($normalized, 'berikan') !== false
                || strpos($normalized, 'minta') !== false
                || strpos($normalized, 'butuh') !== false
            );

        return $asks_for_account && strpos($normalized, 'belum terjual') !== false;
    }

    private function chat_take_unsold_account_response()
    {
        $account = $this->db
            ->where('kategori', 'belum_terjual')
            ->where('status', 'aktif')
            ->order_by('id_akun', 'ASC')
            ->limit(1)
            ->get('akun')
            ->row();

        if (!$account) {
            return [
                'content' => 'Belum ada akun belum terjual yang aktif saat ini.',
                'summary' => 'Akun belum terjual kosong',
                'command' => 'take_unsold_account',
                'status' => 'success',
                'error' => null,
                'metadata' => ['found' => false],
            ];
        }

        return [
            'content' => "Saya ambilkan satu akun yang belum terjual:\n\n" . $this->format_account_detail($account),
            'summary' => 'Ambil akun belum terjual',
            'command' => 'take_unsold_account',
            'status' => 'success',
            'error' => null,
            'metadata' => [
                'found' => true,
                'id_akun' => (int) $account->id_akun,
                'username' => $account->username,
            ],
        ];
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

    private function is_basic_question($normalized)
    {
        if ($normalized === '') {
            return false;
        }

        $basic_exact = [
            'apa kabar',
            'apa kabar mu',
            'apa kabarmu',
            'bagaimana kabar mu',
            'bagaimana kabarmu',
            'kamu siapa',
            'siapa kamu',
            'nama kamu siapa',
            'siapa nama kamu',
            'nama saya siapa',
            'siapa nama saya',
            'terima kasih',
            'makasih',
            'thanks',
            'thank you',
            'oke',
            'ok',
            'siap',
            'apa itu violence ai',
            'violence ai itu apa',
            'kamu bisa apa',
            'bisa apa',
        ];

        if (in_array($normalized, $basic_exact, true)) {
            return true;
        }

        if (preg_match('/^(berapa\s+)?[0-9]+(\s*[\+\-\*\/x]\s*[0-9]+)+$/', $normalized) === 1) {
            return true;
        }

        return false;
    }

    private function extract_user_introduced_name($normalized)
    {
        $patterns = [
            '/(?:halo|hai|hi|hallo|hello)?\s*nama saya\s+([a-z0-9 ._-]{2,40})$/i',
            '/(?:halo|hai|hi|hallo|hello)?\s*namaku\s+([a-z0-9 ._-]{2,40})$/i',
            '/(?:halo|hai|hi|hallo|hello)?\s*saya bernama\s+([a-z0-9 ._-]{2,40})$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches) !== 1) {
                continue;
            }

            $name = trim($matches[1]);
            if ($this->looks_like_name_value($name)) {
                return $this->format_user_name($name);
            }
        }

        return null;
    }

    private function is_user_name_question($normalized)
    {
        return in_array($normalized, [
            'nama saya siapa',
            'siapa nama saya',
            'kamu ingat nama saya',
            'ingat nama saya',
        ], true);
    }

    private function chat_user_name_intro_response($name)
    {
        return [
            'content' => 'Halo ' . $name . ', saya ingat nama kamu. Mau saya bantu apa hari ini?',
            'summary' => 'Mengingat nama user',
            'command' => 'remember_user_name',
            'status' => 'success',
            'error' => null,
            'metadata' => ['user_name' => $name],
        ];
    }

    private function chat_user_name_question_response($conversation_id)
    {
        $name = $this->find_user_name_from_conversation($conversation_id);

        if ($name === null) {
            $content = 'Saya belum tahu nama kamu. Kenalin dulu ya, contoh: nama saya Viryo';
            $metadata = [];
        } else {
            $content = 'Nama kamu ' . $name . '. Saya masih ingat.';
            $metadata = ['user_name' => $name];
        }

        return [
            'content' => $content,
            'summary' => 'Menjawab nama user',
            'command' => 'user_name_question',
            'status' => 'success',
            'error' => null,
            'metadata' => $metadata,
        ];
    }

    private function is_ai_studio_status_command($normalized)
    {
        return in_array($normalized, [
            'cek ai',
            'cek ai studio',
            'cek gemini',
            'test ai',
            'test gemini',
        ], true);
    }

    private function chat_ai_studio_status_response()
    {
        $api_key = $this->google_ai_studio_api_key();

        if ($api_key === '') {
            return [
                'content' => implode("\n", [
                    'AI Studio belum aktif. API key belum terbaca oleh backend.',
                    '',
                    'Saya sudah cek lokasi ini:',
                    '- ' . FCPATH . 'ai_studio_key.php: ' . (file_exists(FCPATH . 'ai_studio_key.php') ? 'ada' : 'tidak ada'),
                    '- ' . APPPATH . 'config/local.php: ' . (file_exists(APPPATH . 'config/local.php') ? 'ada' : 'tidak ada'),
                    '- environment GOOGLE_AI_STUDIO_API_KEY: ' . (getenv('GOOGLE_AI_STUDIO_API_KEY') ? 'ada' : 'tidak ada'),
                    '',
                    'Buat salah satu file di atas, lalu isi API key di sana.',
                ]),
                'summary' => 'AI Studio belum aktif',
                'command' => 'ai_studio_status',
                'status' => 'failed',
                'error' => 'API key kosong',
                'metadata' => ['configured' => false],
            ];
        }

        $answer = $this->google_ai_studio_answer('Jawab singkat: koneksi Violence AI ke Gemini sudah aktif.');

        if ($answer === null) {
            return [
                'content' => 'API key sudah terbaca, tapi Gemini belum berhasil menjawab. Coba restart Apache/Laragon, lalu cek lagi.',
                'summary' => 'AI Studio gagal menjawab',
                'command' => 'ai_studio_status',
                'status' => 'failed',
                'error' => 'Gemini tidak merespons',
                'metadata' => ['configured' => true],
            ];
        }

        return [
            'content' => "AI Studio sudah aktif dan bisa dipakai.\n\nTes Gemini: " . $answer,
            'summary' => 'AI Studio aktif',
            'command' => 'ai_studio_status',
            'status' => 'success',
            'error' => null,
            'metadata' => ['configured' => true],
        ];
    }

    private function find_user_name_from_conversation($conversation_id)
    {
        $messages = $this->db
            ->where('conversation_id', (int) $conversation_id)
            ->where('role', 'user')
            ->order_by('id', 'DESC')
            ->limit(20)
            ->get('chat_ai_messages')
            ->result();

        foreach ($messages as $message) {
            $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $message->content)));
            $normalized = trim(preg_replace('/[?!.,;:]+$/', '', $normalized));
            $name = $this->extract_user_introduced_name($normalized);

            if ($name !== null) {
                return $name;
            }
        }

        return null;
    }

    private function looks_like_name_value($name)
    {
        $blocked = [
            'siapa', 'apa', 'kabar', 'baik', 'buruk', 'admin', 'user', 'akun',
            'password', 'stok', 'stock', 'lirik', 'lagu', 'tolong', 'bantu',
        ];

        $name = strtolower(trim($name));

        if ($name === '' || in_array($name, $blocked, true)) {
            return false;
        }

        return preg_match('/^[a-z][a-z0-9 ._-]{1,39}$/i', $name) === 1;
    }

    private function format_user_name($name)
    {
        $name = strtolower(trim(preg_replace('/\s+/', ' ', $name)));

        return ucwords($name);
    }

    private function chat_basic_response($normalized)
    {
        if (in_array($normalized, ['apa kabar', 'apa kabar mu', 'apa kabarmu', 'bagaimana kabar mu', 'bagaimana kabarmu'], true)) {
            $content = 'Kabar saya baik. Kamu mau saya bantu cek stok, tambah akun, atau cari detail akun?';
            $summary = 'Menjawab kabar';
        } elseif (in_array($normalized, ['kamu siapa', 'siapa kamu', 'nama kamu siapa', 'siapa nama kamu'], true)) {
            $content = 'Saya Violence AI, asisten chat kamu untuk bantu kelola akun Kevstore.';
            $summary = 'Identitas asisten';
        } elseif (in_array($normalized, ['terima kasih', 'makasih', 'thanks', 'thank you'], true)) {
            $content = 'Sama-sama. Kalau butuh stok atau detail akun, tinggal panggil saya.';
            $summary = 'Ucapan terima kasih';
        } elseif (in_array($normalized, ['oke', 'ok', 'siap'], true)) {
            $content = 'Siap.';
            $summary = 'Konfirmasi singkat';
        } elseif (in_array($normalized, ['apa itu violence ai', 'violence ai itu apa'], true)) {
            $content = 'Violence AI itu asisten chat untuk Kevstore. Saya bisa bantu cek stok, tambah akun, cari detail akun, dan bantu beberapa perintah operasional.';
            $summary = 'Penjelasan Violence AI';
        } elseif (in_array($normalized, ['kamu bisa apa', 'bisa apa'], true)) {
            return $this->chat_feature_list_response();
        } elseif (preg_match('/^(berapa\s+)?([0-9]+(\s*[\+\-\*\/x]\s*[0-9]+)+)$/', $normalized, $matches) === 1) {
            $expression = str_replace(['berapa', 'x', ' '], ['', '*', ''], $matches[2]);
            $content = 'Hasilnya: ' . $this->safe_calculate_expression($expression);
            $summary = 'Kalkulasi sederhana';
        } else {
            $content = 'Saya bisa bantu pertanyaan dasar dan kebutuhan Kevstore. Untuk hal di luar konteks, saya akan bantu arahkan ke Google.';
            $summary = 'Pertanyaan dasar';
        }

        return [
            'content' => $content,
            'summary' => $summary,
            'command' => 'basic_answer',
            'status' => 'success',
            'error' => null,
            'metadata' => [],
        ];
    }

    private function safe_calculate_expression($expression)
    {
        if (preg_match('/^[0-9\+\-\*\/\.]+$/', $expression) !== 1) {
            return 'format hitungan belum didukung';
        }

        preg_match_all('/\d+(?:\.\d+)?|[\+\-\*\/]/', $expression, $matches);
        $tokens = $matches[0];

        if (empty($tokens) || count($tokens) % 2 === 0) {
            return 'hitungan tidak valid';
        }

        $values = [(float) array_shift($tokens)];
        $operators = [];

        while (!empty($tokens)) {
            $operator = array_shift($tokens);
            $value = (float) array_shift($tokens);

            if ($operator === '*') {
                $values[count($values) - 1] *= $value;
                continue;
            }

            if ($operator === '/') {
                if ($value == 0.0) {
                    return 'tidak bisa dibagi 0';
                }

                $values[count($values) - 1] /= $value;
                continue;
            }

            $operators[] = $operator;
            $values[] = $value;
        }

        $result = $values[0];
        foreach ($operators as $index => $operator) {
            $next_value = $values[$index + 1];
            $result = $operator === '-' ? $result - $next_value : $result + $next_value;
        }

        return rtrim(rtrim(number_format($result, 6, '.', ''), '0'), '.');
    }

    private function chat_google_search_response($content)
    {
        $query = trim((string) $content);
        $google_url = 'https://www.google.com/search?q=' . rawurlencode($query);
        $search = $this->search_web_for_answer($query);

        if ($this->is_lyrics_request($query)) {
            return $this->chat_lyrics_search_response($query, $search, $google_url);
        }

        $ai_answer = $this->google_ai_studio_answer($query);
        if ($ai_answer !== null) {
            return [
                'content' => $ai_answer,
                'summary' => 'Jawaban Google AI Studio',
                'command' => 'google_ai_studio',
                'status' => 'success',
                'error' => null,
                'metadata' => [
                    'query' => $query,
                    'provider' => 'google_ai_studio',
                ],
            ];
        }

        if (!empty($search['results'])) {
            return [
                'content' => $this->compose_web_answer($search['results']),
                'summary' => 'Jawaban dari web',
                'command' => 'web_search',
                'status' => 'success',
                'error' => null,
                'metadata' => [
                    'query' => $query,
                    'source' => $search['source'],
                    'results' => $search['results'],
                ],
            ];
        }

        return [
            'content' => "Saya sudah coba cari, tapi hasilnya belum bisa saya baca dengan jelas.\n\nKamu bisa cek lewat Google ini:\n" . $google_url,
            'summary' => 'Fallback Google Search',
            'command' => 'google_search',
            'status' => 'success',
            'error' => null,
            'metadata' => [
                'query' => $query,
                'google_url' => $google_url,
            ],
        ];
    }

    private function search_web_for_answer($query)
    {
        $query = trim((string) $query);

        if ($query === '') {
            return ['source' => 'none', 'results' => []];
        }

        $sources = [
            'google_custom_search' => $this->google_custom_search_results($query),
            'google' => $this->google_html_search_results($query),
            'bing_fallback' => $this->bing_search_results($query),
        ];

        $results = [];
        $used_sources = [];

        foreach ($sources as $source => $items) {
            if (empty($items)) {
                continue;
            }

            $used_sources[] = $source;
            foreach ($items as $item) {
                $results[] = $item;
            }
        }

        $results = $this->rank_search_results($results, $query);

        return [
            'source' => empty($used_sources) ? 'none' : implode(',', $used_sources),
            'results' => $results,
        ];
    }

    private function google_ai_studio_answer($query)
    {
        $api_key = $this->google_ai_studio_api_key();
        $token = trim((string) $this->config->item('google_ai_studio_token'));

        if ($api_key === '' && $token === '') {
            return null;
        }

        $model = trim((string) $this->config->item('google_ai_studio_model'));
        if ($model === '') {
            $model = 'gemini-2.5-flash';
        }

        $system_prompt = implode("\n", [
            'Kamu adalah Violence AI, asisten ramah untuk Kevstore.',
            'Jawab dalam bahasa Indonesia yang santai, jelas, dan membantu.',
            'Kalau pertanyaan tentang akun Kevstore, arahkan user memakai command: stok, detail, use, tambah, atau bantuan.',
            'Kalau butuh informasi terbaru, gunakan kemampuan pencarian Google bila tersedia.',
            'Jangan berikan lirik lagu lengkap atau teks berhak cipta panjang. Untuk lirik, arahkan ke sumber dan bantu jelaskan makna atau terjemahkan bagian pendek.',
            'Jawaban maksimal 5 paragraf pendek.',
        ]);

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $system_prompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $query],
                    ],
                ],
            ],
            'tools' => [
                ['google_search' => new stdClass()],
            ],
            'generationConfig' => [
                'temperature' => 0.45,
                'maxOutputTokens' => 700,
            ],
        ];

        $response = $this->google_ai_studio_generate_content($model, $api_key, $token, $payload);

        if ($response === null) {
            unset($payload['tools']);
            $response = $this->google_ai_studio_generate_content($model, $api_key, $token, $payload);
        }

        if ($response === null) {
            return null;
        }

        $answer = $this->extract_google_ai_text($response);

        return $answer !== '' ? $answer : null;
    }

    private function google_ai_studio_api_key()
    {
        $api_key = trim((string) $this->config->item('google_ai_studio_api_key'));

        if ($api_key !== '') {
            return $api_key;
        }

        $root_key = $this->read_ai_studio_key_file(FCPATH . 'ai_studio_key.php');
        if ($root_key !== '') {
            return $root_key;
        }

        $local_key = $this->read_ai_studio_local_config(APPPATH . 'config/local.php');
        if ($local_key !== '') {
            return $local_key;
        }

        return '';
    }

    private function read_ai_studio_local_config($path)
    {
        if (!file_exists($path)) {
            return '';
        }

        $config = include $path;
        if (is_array($config) && !empty($config['google_ai_studio_api_key'])) {
            return trim((string) $config['google_ai_studio_api_key']);
        }

        return $this->extract_ai_studio_key_from_text((string) file_get_contents($path));
    }

    private function read_ai_studio_key_file($path)
    {
        if (!file_exists($path)) {
            return '';
        }

        $key = include $path;
        if (is_string($key) && trim($key) !== '') {
            return trim($key);
        }

        return $this->extract_ai_studio_key_from_text((string) file_get_contents($path));
    }

    private function extract_ai_studio_key_from_text($text)
    {
        if (preg_match('/(AQ\.[A-Za-z0-9_\-]+|AIza[A-Za-z0-9_\-]+)/', $text, $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }

    private function google_ai_studio_generate_content($model, $api_key, $token, array $payload)
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model)
            . ':generateContent';

        $headers = [
            'Content-Type: application/json',
        ];

        if ($api_key !== '') {
            $headers[] = 'x-goog-api-key: ' . $api_key;
        } elseif ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            log_message('error', 'Google AI Studio request failed. HTTP status: ' . $status . '. Body: ' . substr((string) $body, 0, 300));
            return null;
        }

        $json = json_decode($body, true);

        return is_array($json) ? $json : null;
    }

    private function extract_google_ai_text(array $response)
    {
        $parts = $response['candidates'][0]['content']['parts'] ?? [];

        if (!is_array($parts)) {
            return '';
        }

        $texts = [];
        foreach ($parts as $part) {
            if (!empty($part['text'])) {
                $texts[] = $part['text'];
            }
        }

        return trim(implode("\n", $texts));
    }

    private function google_custom_search_results($query)
    {
        $api_key = getenv('GOOGLE_CSE_API_KEY');
        $cx = getenv('GOOGLE_CSE_ID');

        if (!$api_key || !$cx) {
            return [];
        }

        $url = 'https://www.googleapis.com/customsearch/v1?key=' . rawurlencode($api_key)
            . '&cx=' . rawurlencode($cx)
            . '&num=3&hl=id&q=' . rawurlencode($query);
        $body = $this->http_get($url, 8);

        if ($body === null) {
            return [];
        }

        $json = json_decode($body, true);
        if (empty($json['items']) || !is_array($json['items'])) {
            return [];
        }

        $results = [];
        foreach (array_slice($json['items'], 0, 3) as $item) {
            $results[] = [
                'title' => isset($item['title']) ? $this->clean_search_text($item['title']) : '',
                'snippet' => isset($item['snippet']) ? $this->clean_search_text($item['snippet']) : '',
                'url' => isset($item['link']) ? $item['link'] : '',
            ];
        }

        return $this->filter_search_results($results);
    }

    private function google_html_search_results($query)
    {
        $url = 'https://www.google.com/search?hl=id&num=5&q=' . rawurlencode($query);
        $html = $this->http_get($url, 8);

        if ($html === null) {
            return [];
        }

        $results = [];
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//a[h3]');

        foreach ($nodes as $node) {
            $title_node = $xpath->query('.//h3', $node)->item(0);
            $href = $node->getAttribute('href');
            $title = $title_node ? $this->clean_search_text($title_node->textContent) : '';
            $snippet = $this->find_nearby_search_snippet($xpath, $node);

            if (strpos($href, '/url?') === 0) {
                parse_str(parse_url($href, PHP_URL_QUERY), $params);
                $href = isset($params['q']) ? $params['q'] : $href;
            }

            $results[] = [
                'title' => $title,
                'snippet' => $snippet !== '' ? $snippet : $title,
                'url' => $href,
            ];

            if (count($results) >= 3) {
                break;
            }
        }

        libxml_clear_errors();
        return $this->filter_search_results($results);
    }

    private function bing_search_results($query)
    {
        $url = 'https://www.bing.com/search?format=rss&q=' . rawurlencode($query);
        $xml_body = $this->http_get($url, 8);

        if ($xml_body === null || !function_exists('simplexml_load_string')) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_body);

        if (!$xml || empty($xml->channel->item)) {
            libxml_clear_errors();
            return [];
        }

        $results = [];
        foreach ($xml->channel->item as $item) {
            $results[] = [
                'title' => $this->clean_search_text((string) $item->title),
                'snippet' => $this->clean_search_text((string) $item->description),
                'url' => trim((string) $item->link),
            ];

            if (count($results) >= 3) {
                break;
            }
        }

        libxml_clear_errors();
        return $this->filter_search_results($results);
    }

    private function find_nearby_search_snippet(DOMXPath $xpath, DOMNode $node)
    {
        $container = $node->parentNode;

        for ($i = 0; $i < 4 && $container; $i++) {
            $text = $this->clean_search_text($container->textContent);
            if (strlen($text) > 80) {
                return substr($text, 0, 260);
            }

            $container = $container->parentNode;
        }

        return '';
    }

    private function filter_search_results(array $results)
    {
        $filtered = [];
        $seen = [];

        foreach ($results as $result) {
            $snippet = isset($result['snippet']) ? $this->clean_search_text($result['snippet']) : '';
            $url = isset($result['url']) ? trim($result['url']) : '';
            $title = isset($result['title']) ? $this->clean_search_text($result['title']) : '';
            $key = strtolower($url ?: $title);

            if ($snippet === '' || $url === '' || strpos($url, 'http') !== 0 || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $filtered[] = [
                'title' => $title,
                'snippet' => $snippet,
                'url' => $url,
            ];
        }

        return $filtered;
    }

    private function rank_search_results(array $results, $query)
    {
        $results = $this->filter_search_results($results);
        $terms = $this->important_search_terms($query);

        foreach ($results as $index => $result) {
            $haystack_title = strtolower($result['title']);
            $haystack_text = strtolower($result['title'] . ' ' . $result['snippet'] . ' ' . $result['url']);
            $score = max(0, 20 - $index);

            foreach ($terms as $term) {
                if (strpos($haystack_title, $term) !== false) {
                    $score += 8;
                }

                if (strpos($haystack_text, $term) !== false) {
                    $score += 3;
                }
            }

            if (preg_match('/kumpulan|terbaru|terpopuler|berbagai artis|various artists|favorite music/i', $haystack_text)) {
                $score -= 12;
            }

            $results[$index]['score'] = $score;
        }

        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($results, 0, 3);
    }

    private function important_search_terms($query)
    {
        $query = strtolower($this->clean_search_text($query));
        $query = preg_replace('/[^a-z0-9\s]/', ' ', $query);
        $words = preg_split('/\s+/', $query);
        $stopwords = [
            'apa', 'itu', 'yang', 'dan', 'atau', 'dari', 'untuk', 'dengan', 'ke',
            'di', 'ini', 'dong', 'tolong', 'cari', 'carikan', 'lirik', 'lagu',
            'lyrics', 'lyric', 'full', 'lengkap',
        ];
        $terms = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) < 3 || in_array($word, $stopwords, true)) {
                continue;
            }

            $terms[] = $word;
        }

        return array_values(array_unique($terms));
    }

    private function compose_web_answer(array $results)
    {
        $best = $results[0];
        $lines = [];
        $lines[] = 'Saya cari dari web, ini jawaban yang paling nyambung:';
        $lines[] = $this->friendly_web_snippet($best['snippet']);
        $lines[] = '';
        $lines[] = 'Sumber yang saya pakai: ' . ($best['title'] ?: $best['url']);
        $lines[] = $best['url'];

        if (count($results) > 1) {
            $lines[] = '';
            $lines[] = 'Yang masih terkait:';

            foreach (array_slice($results, 1) as $result) {
                $lines[] = '- ' . ($result['title'] ?: $result['url']);
            }
        }

        return implode("\n", $lines);
    }

    private function friendly_web_snippet($snippet)
    {
        $snippet = $this->trim_answer_text($snippet);

        if (preg_match('/^The\s+(.+?)\s+is\s+(an?|the)\s+(.+)/i', $snippet, $matches) === 1) {
            return $matches[1] . ' adalah ' . $this->lowercase_first($matches[3]);
        }

        if (preg_match('/^(.+?)\s+is\s+(an?|the)\s+(.+)/i', $snippet, $matches) === 1) {
            return $matches[1] . ' adalah ' . $this->lowercase_first($matches[3]);
        }

        if (preg_match('/\b(is|are|represents|organization|scientific|professional|United States)\b/i', $snippet) === 1) {
            return 'Dari sumber yang saya temukan: ' . $snippet;
        }

        return $snippet;
    }

    private function lowercase_first($text)
    {
        $text = trim((string) $text);

        if ($text === '') {
            return $text;
        }

        return strtolower(substr($text, 0, 1)) . substr($text, 1);
    }

    private function is_lyrics_request($query)
    {
        return preg_match('/\b(lirik|lyrics|lyric)\b/i', $query) === 1;
    }

    private function chat_lyrics_search_response($query, array $search, $google_url)
    {
        if (empty($search['results'])) {
            return [
                'content' => "Saya belum menemukan sumber lirik yang cocok.\n\nCoba cek lewat Google ini:\n" . $google_url,
                'summary' => 'Fallback lirik Google',
                'command' => 'lyrics_search',
                'status' => 'success',
                'error' => null,
                'metadata' => [
                    'query' => $query,
                    'google_url' => $google_url,
                ],
            ];
        }

        $best = $search['results'][0];
        $lines = [];
        $lines[] = 'Ketemu, ini sumber lirik yang paling cocok:';
        $lines[] = $best['title'] ?: $best['snippet'];
        $lines[] = '';
        $lines[] = 'Buka liriknya di sini:';
        $lines[] = $best['url'];
        $lines[] = '';
        $lines[] = 'Kalau mau, kirim bagian pendeknya. Saya bisa bantu jelaskan makna atau terjemahannya.';

        if (count($search['results']) > 1) {
            $lines[] = '';
            $lines[] = 'Yang masih terkait:';

            foreach (array_slice($search['results'], 1) as $result) {
                $lines[] = '- ' . ($result['title'] ?: $result['url']);
            }
        }

        return [
            'content' => implode("\n", $lines),
            'summary' => 'Pencarian lirik',
            'command' => 'lyrics_search',
            'status' => 'success',
            'error' => null,
            'metadata' => [
                'query' => $query,
                'source' => $search['source'],
                'results' => $search['results'],
            ],
        ];
    }

    private function trim_answer_text($text)
    {
        $text = $this->clean_search_text($text);

        if (strlen($text) <= 420) {
            return $text;
        }

        return rtrim(substr($text, 0, 417)) . '...';
    }

    private function clean_search_text($text)
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function http_get($url, $timeout = 8)
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept-Language: id-ID,id;q=0.9,en;q=0.8',
            ],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            return null;
        }

        return $body;
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

    private function is_feature_list_command($normalized)
    {
        return in_array($normalized, [
            'fitur',
            'fitur apa saja',
            'ada fitur apa saja',
            'apa saja fiturnya',
            'list fitur',
            'daftar fitur',
            'help',
            'bantuan',
        ], true);
    }

    private function chat_feature_list_response()
    {
        return [
            'content' => implode("\n", [
                'Saya bisa bantu beberapa hal ini:',
                '1. halo',
                '2. berapa stok hari ini',
                '3. tambah',
                '4. detail username atau note',
                '5. use username',
                '6. ubah status/password/username/note akun yang sudah di-use',
                '7. salin no pesanan saja',
                '8. hapus riwayat chat ini',
                '9. sekarang tanggal berapa',
                '10. berapa deactived',
                '11. tunjukan data akun yang dibuat kemarin',
                '12. bawakan saya sebuah akun yang belum terjual',
            ]),
            'summary' => 'Daftar fitur chat',
            'command' => 'features',
            'status' => 'success',
            'error' => null,
            'metadata' => [],
        ];
    }

    private function is_date_question($normalized)
    {
        return strpos($normalized, 'tanggal') !== false
            && (
                strpos($normalized, 'berapa') !== false
                || strpos($normalized, 'sekarang') !== false
                || strpos($normalized, 'hari ini') !== false
            );
    }

    private function chat_date_response()
    {
        return [
            'content' => 'Sekarang tanggal ' . date('d-m-Y') . ', jam ' . date('H:i') . ' WIB.',
            'summary' => 'Tanggal hari ini',
            'command' => 'date',
            'status' => 'success',
            'error' => null,
            'metadata' => ['date' => date('Y-m-d'), 'time' => date('H:i:s')],
        ];
    }

    private function is_deactived_count_question($normalized)
    {
        return strpos($normalized, 'deactived') !== false
            || strpos($normalized, 'deactivated') !== false
            || strpos($normalized, 'disable') !== false
            || strpos($normalized, 'ban') !== false
            || strpos($normalized, 'verif') !== false;
    }

    private function chat_deactived_count_response()
    {
        $count = $this->db
            ->where($this->status_problem_filter(), null, false)
            ->count_all_results('akun');

        return [
            'content' => 'Saat ini ada ' . $count . ' akun deactived atau bermasalah.',
            'summary' => 'Jumlah akun deactived',
            'command' => 'deactived_count',
            'status' => 'success',
            'error' => null,
            'metadata' => ['count' => $count],
        ];
    }

    private function is_available_account_question($normalized)
    {
        return (strpos($normalized, 'akun tersedia') !== false)
            || (strpos($normalized, 'tersedia') !== false && strpos($normalized, 'berapa') !== false);
    }

    private function is_yesterday_created_accounts_question($normalized)
    {
        return (strpos($normalized, 'kemarin') !== false)
            && (
                strpos($normalized, 'dibuat') !== false
                || strpos($normalized, 'ditambah') !== false
                || strpos($normalized, 'tambah') !== false
            )
            && strpos($normalized, 'akun') !== false;
    }

    private function chat_yesterday_created_accounts_response()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $activity = [];

        if ($this->db->table_exists('activity_log')) {
            $this->ensure_activity_snapshot_columns();

            $activity = $this->db
                ->select('activity_log.*, COALESCE(akun.username, activity_log.akun_username_snapshot) AS akun_username, COALESCE(akun.nama_akun, activity_log.akun_nama_snapshot) AS nama_akun', false)
                ->from('activity_log')
                ->join('akun', 'akun.id_akun = activity_log.akun_id', 'left')
                ->where('DATE(activity_log.created_at) =', $yesterday)
                ->where('LOWER(activity_log.action) LIKE', '%tambah%')
                ->order_by('activity_log.created_at', 'DESC')
                ->limit(20)
                ->get()
                ->result();
        }

        if (empty($activity)) {
            return [
                'content' => 'Kemarin (' . date('d-m-Y', strtotime($yesterday)) . ') belum ada akun baru yang tercatat.',
                'summary' => 'Akun kemarin kosong',
                'command' => 'yesterday_created_accounts',
                'status' => 'success',
                'error' => null,
                'metadata' => ['date' => $yesterday, 'count' => 0],
            ];
        }

        $lines = ['Ini akun yang dibuat kemarin (' . date('d-m-Y', strtotime($yesterday)) . '):'];

        foreach ($activity as $row) {
            $username = $row->akun_username ?: '-';
            $name = $row->nama_akun ?: '-';
            $lines[] = '- ' . $username . ' | ' . $name . ' | ' . $row->created_at;
        }

        return [
            'content' => implode("\n", $lines),
            'summary' => 'Akun dibuat kemarin',
            'command' => 'yesterday_created_accounts',
            'status' => 'success',
            'error' => null,
            'metadata' => ['date' => $yesterday, 'count' => count($activity)],
        ];
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
        return $normalized === 'use'
            || $normalized === '/use'
            || strpos($normalized, 'use ') === 0
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
            $accounts = $this->search_accounts_for_chat('', 10);

            return [
                'content' => $this->format_account_search_results($accounts, 'Saya temukan beberapa akun. Mau pakai yang mana?', 'use'),
                'summary' => 'Menampilkan pilihan akun untuk use',
                'command' => 'use_account',
                'status' => 'waiting',
                'error' => null,
                'metadata' => ['mode' => 'use_account_search', 'count' => count($accounts)],
            ];
        }

        $account = $this->find_single_account_for_use($keyword);

        if (!$account) {
            $accounts = $this->search_accounts_for_chat($keyword, 10);

            if (!empty($accounts)) {
                return [
                    'content' => $this->format_account_search_results($accounts, 'Saya nemu akun yang mirip dengan "' . $keyword . '":', 'use'),
                    'summary' => 'Menampilkan hasil search akun untuk use',
                    'command' => 'use_account',
                    'status' => 'waiting',
                    'error' => null,
                    'metadata' => ['mode' => 'use_account_search', 'keyword' => $keyword, 'count' => count($accounts)],
                ];
            }

            return [
                'content' => 'Saya belum menemukan akun untuk: ' . $keyword . '. Coba pakai username, nama akun, note, atau ID akun.',
                'summary' => 'Use akun gagal',
                'command' => 'use_account',
                'status' => 'failed',
                'error' => 'Akun tidak ditemukan',
                'metadata' => ['keyword' => $keyword],
            ];
        }

        return [
            'content' => $this->format_account_detail($account) . "\n\nAkun ini sudah saya pilih. Kalau mau reset jadi akun baru, ketik:\nubah akun ini jadi seperti baru dengan username ..., password ..., note ...",
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
                'content' => 'Pilih akunnya dulu ya. Ketik `use username` atau `use #ID`.',
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
                'content' => 'Akun yang tadi dipilih sudah tidak ketemu. Pilih ulang ya dengan `use username` atau `use #ID`.',
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
                'content' => "Formatnya hampir benar, tapi masih kurang username atau password. Contoh:\nubah akun ini jadi seperti baru dengan username email@gmail.com, password pass123, note catatan",
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
                'content' => 'Username ini sudah dipakai akun lain: ' . $fields['username'] . '. Coba username lain ya.',
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
            'content' => "Beres, akun ini sudah saya ubah jadi seperti baru.\n\n" . $this->format_account_detail($updated),
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
                'content' => 'Pilih akunnya dulu ya. Ketik `use username` atau `use #ID`.',
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
                'content' => 'Akun yang tadi dipilih sudah tidak ketemu. Pilih ulang ya dengan `use username` atau `use #ID`.',
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
                'content' => "Saya belum menangkap field yang mau diubah. Contoh:\nubah status jadi aktif\nubah password jadi pass123\nubah note jadi catatan baru\nubah username jadi email@gmail.com",
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
                'content' => 'Username ini sudah dipakai akun lain: ' . $changes['username'] . '. Coba username lain ya.',
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
            'content' => "Beres, akun berhasil saya ubah.\n\n" . $this->format_account_detail($updated),
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
            $accounts = $this->search_accounts_for_chat('', 10);

            return [
                'content' => $this->format_account_search_results($accounts, 'Saya temukan beberapa akun. Mau lihat detail yang mana?', 'detail'),
                'summary' => 'Menampilkan pilihan detail akun',
                'command' => 'detail',
                'status' => 'waiting',
                'error' => null,
                'metadata' => ['mode' => 'detail_account', 'count' => count($accounts)],
            ];
        }

        $accounts = $this->find_accounts_for_detail($keyword);

        if (empty($accounts)) {
            return [
                'content' => 'Saya belum menemukan akun untuk keyword: ' . $keyword . '. Coba ketik username, nama akun, note, atau ID akun.',
                'summary' => 'Detail akun tidak ditemukan',
                'command' => 'detail',
                'status' => 'failed',
                'error' => 'Akun tidak ditemukan',
                'metadata' => ['keyword' => $keyword],
            ];
        }

        $lines = ['Ini detail akun yang saya temukan:'];

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
            $lines[] = 'Saya tampilkan 5 hasil pertama dulu. Kalau mau lebih pas, coba keyword yang lebih spesifik.';
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

        return $this->search_accounts_for_chat($keyword, 5);
    }

    private function find_single_account_for_use($keyword)
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return null;
        }

        $exact = $this->db
            ->where('BINARY `username` = ' . $this->db->escape($keyword), null, false)
            ->get('akun')
            ->row();

        if ($exact) {
            return $exact;
        }

        $prefix = $this->db
            ->like('username', $keyword, 'after')
            ->order_by('id_akun', 'DESC')
            ->limit(1)
            ->get('akun')
            ->row();

        if ($prefix) {
            return $prefix;
        }

        $accounts = $this->search_accounts_for_chat($keyword, 1);

        return !empty($accounts) ? $accounts[0] : null;
    }

    private function search_accounts_for_chat($keyword = '', $limit = 10)
    {
        $keyword = trim((string) $keyword);

        $this->db->from('akun');

        if ($keyword !== '') {
            if (preg_match('/^#?\d+$/', $keyword) === 1) {
                $this->db->where('id_akun', (int) ltrim($keyword, '#'));
            } else {
                $this->db
                    ->group_start()
                        ->like('username', $keyword)
                        ->or_like('nama_akun', $keyword)
                        ->or_like('note', $keyword)
                        ->or_like('website', $keyword)
                        ->or_like('kategori', $keyword)
                        ->or_like('status', $keyword)
                    ->group_end();
            }
        }

        return $this->db
            ->order_by('id_akun', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result();
    }

    private function format_account_search_results(array $accounts, $heading, $command)
    {
        if (empty($accounts)) {
            return 'Saya belum menemukan akun yang bisa ditampilkan.';
        }

        $lines = [$heading];

        foreach ($accounts as $account) {
            $id = isset($account->id_akun) ? '#' . $account->id_akun : '#-';
            $name = !empty($account->nama_akun) ? $account->nama_akun : '-';
            $username = !empty($account->username) ? $account->username : '-';
            $status = !empty($account->status) ? $account->status : '-';
            $category = !empty($account->kategori) ? $account->kategori : '-';

            $lines[] = $id . ' | ' . $name . ' | ' . $username . ' | ' . $category . ' | ' . $status;
        }

        $lines[] = '';

        if ($command === 'use') {
            $lines[] = 'Ketik `use username` atau `use #ID` untuk pilih akun.';
        } else {
            $lines[] = 'Ketik `detail username` atau `detail #ID` untuk lihat lengkapnya.';
        }

        return implode("\n", $lines);
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
            'Detail akun yang saya temukan:',
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
                'content' => "Formatnya belum kebaca. Pakai format ini ya:\nusername|password|catatan",
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
                $skipped[] = $username . ': username sudah ada. Kalau mau pakai akun ini, ketik `use ' . $username . '` atau `detail ' . $username . '`.';
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

        $lines = ['Beres, proses tambah akun selesai.'];
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

            if ($line === '') {
                continue;
            }

            $parsed = $this->parse_account_line($line);
            $rows[] = [
                'username' => $parsed['username'],
                'password' => $parsed['password'],
                'note' => $parsed['note'],
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
            return $this->json_error($this->bulk_create_error_message($skipped), 422, $response);
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

            $parsed = $this->parse_account_line($line);

            $rows[] = [
                'username' => $parsed['username'],
                'password' => $parsed['password'],
                'note' => $parsed['note'],
                'nama_akun' => 'Grok',
                'kategori' => 'belum_terjual',
                'status' => 'aktif',
                'max_user' => 0,
            ];
        }

        return $rows;
    }

    private function parse_account_line($line)
    {
        $line = trim((string) $line);

        if ($line === '') {
            return ['username' => '', 'password' => '', 'note' => ''];
        }

        if (strpos($line, '|') !== false) {
            $parts = explode('|', $line, 3);
        } elseif (strpos($line, "\t") !== false) {
            $parts = preg_split('/\t+/', $line, 3);
        } elseif (substr_count($line, ':') >= 1 && preg_match('/^\S+@\S+:\S+/', $line) === 1) {
            $parts = explode(':', $line, 3);
        } else {
            $parts = preg_split('/\s+/', $line, 3);
        }

        return [
            'username' => trim($parts[0] ?? ''),
            'password' => trim($parts[1] ?? ''),
            'note' => trim($parts[2] ?? ''),
        ];
    }

    private function bulk_create_error_message(array $skipped)
    {
        if (empty($skipped)) {
            return 'Belum ada akun yang berhasil ditambahkan. Cek lagi format datanya ya.';
        }

        $first = $skipped[0];
        $reason = is_array($first) ? (string) ($first['reason'] ?? 'Format belum valid') : (string) $first;
        $username = is_array($first) && !empty($first['username']) ? ' (' . $first['username'] . ')' : '';

        return 'Belum ada akun yang berhasil ditambahkan. Penyebab pertama: ' . $reason . $username . '.';
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
        $this->db->where('BINARY `username` = ' . $this->db->escape($username), null, false);

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
