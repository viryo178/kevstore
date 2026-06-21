<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Webhook extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Jakarta');
        $this->load->database();
        $this->config->load('fonnte');
    }

    public function fonnte()
    {
        $payload = $this->get_payload();

        if (empty($payload)) {
            $payload = [
                'sender' => $this->input->get('sender'),
                'message' => $this->input->get('message')
            ];
        }

        return $this->handle_incoming_message($payload);
    }

    public function fonnte_simulate()
    {
        $payload = [
            'sender' => $this->input->get('sender') ?: '083871821218',
            'message' => $this->input->get('message') ?: 'tambah akun'
        ];

        return $this->handle_incoming_message($payload);
    }

    private function handle_incoming_message($payload)
    {
        $this->log_webhook_payload($payload);

        $sender = $this->normalize_phone($payload['sender'] ?? $payload['pengirim'] ?? '');
        $message = trim((string) ($payload['message'] ?? $payload['pesan'] ?? ''));
        $inboxid = $payload['inboxid'] ?? null;

        if ($sender === '' || $message === '') {
            return $this->json_response(['status' => true, 'message' => 'Payload kosong']);
        }

        if (!$this->is_allowed_sender($sender)) {
            return $this->json_response(['status' => true, 'message' => 'Sender tidak diizinkan']);
        }

        $this->ensure_session_table();

        $lower_message = strtolower($message);
        $command_message = preg_replace('/[^a-z0-9]/', '', $lower_message);

        if (in_array($command_message, ['batal', 'cancel'], true)) {
            $this->clear_session($sender);
            $this->send_fonnte_message($sender, "Baik, proses tambah akun dibatalkan.", $inboxid);
            return $this->json_response(['status' => true]);
        }

        if (in_array($command_message, ['tambahakun', 'tambahakungrok'], true)) {
            $this->start_bulk_add_session($sender);
            $this->send_fonnte_message($sender, $this->bulk_instruction_message(), $inboxid);
            return $this->json_response(['status' => true]);
        }

        $session = $this->get_active_session($sender);

        if ($session && $session->state === 'awaiting_bulk_add') {
            $result = $this->create_accounts_from_bulk_text($message, $sender);
            $this->clear_session($sender);
            $this->send_fonnte_message($sender, $this->bulk_result_message($result), $inboxid);

            return $this->json_response([
                'status' => true,
                'created' => $result['created'],
                'skipped' => $result['skipped']
            ]);
        }

        return $this->json_response(['status' => true, 'message' => 'Tidak ada perintah aktif']);
    }

    public function fonnte_test()
    {
        return $this->json_response([
            'status' => true,
            'message' => 'Webhook Fonnte aktif',
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    private function get_payload()
    {
        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);

        if (is_array($payload)) {
            return $payload;
        }

        return $this->input->post(NULL, false) ?: [];
    }

    private function log_webhook_payload($payload)
    {
        $this->ensure_webhook_log_table();

        $sender = $this->normalize_phone($payload['sender'] ?? $payload['pengirim'] ?? '');
        $message = trim((string) ($payload['message'] ?? $payload['pesan'] ?? ''));

        $this->db->insert('whatsapp_webhook_log', [
            'sender' => $sender,
            'message' => $message,
            'payload' => json_encode($payload),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function bulk_instruction_message()
    {
        return "Baik, fitur tambah akun dimulai.\n\n"
            . "Kirim daftar akun dengan format bulk:\n"
            . "username|password|catatan\n\n"
            . "Bisa banyak baris sekaligus, contoh:\n"
            . "user1@gmail.com|password123|akun utama\n"
            . "user2@gmail.com|pass456\n\n"
            . "Ketik BATAL untuk membatalkan.";
    }

    private function create_accounts_from_bulk_text($bulk_text, $sender)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $bulk_text);
        $created = 0;
        $skipped = 0;
        $seen_usernames = [];
        $changed_by = $this->get_sender_name($sender);

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $parts = explode('|', $line, 3);
            $username = trim($parts[0] ?? '');
            $password = trim($parts[1] ?? '');
            $note = trim($parts[2] ?? '');

            if ($username === '' || $password === '') {
                $skipped++;
                continue;
            }

            $username_key = strtolower($username);

            if (isset($seen_usernames[$username_key]) || $this->username_exists($username)) {
                $skipped++;
                continue;
            }

            $seen_usernames[$username_key] = true;

            $this->db->insert('akun', [
                'nama_akun' => 'Grok',
                'kategori' => 'belum_terjual',
                'status' => 'aktif',
                'username' => $username,
                'password' => $password,
                'website' => '',
                'max_user' => 0,
                'expired_password' => null,
                'note' => $note,
                'created_by' => $changed_by,
                'last_edited_by' => $changed_by,
                'last_edited_at' => date('Y-m-d H:i:s'),
            ]);

            $id = $this->db->insert_id();

            $this->db->insert('activity_log', [
                'akun_id' => $id,
                'action' => 'Bulk tambah akun via WhatsApp',
                'changed_by' => $changed_by,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $created++;
        }

        return [
            'created' => $created,
            'skipped' => $skipped
        ];
    }

    private function bulk_result_message($result)
    {
        if ((int) $result['created'] < 1) {
            return "Tidak ada akun yang berhasil ditambahkan.\n"
                . "Pastikan formatnya: username|password|catatan";
        }

        $message = $result['created'] . " akun berhasil ditambahkan lewat WhatsApp.";

        if ((int) $result['skipped'] > 0) {
            $message .= "\n" . $result['skipped'] . " baris dilewati karena format salah atau username sudah ada.";
        }

        return $message;
    }

    private function username_exists($username)
    {
        return $this->db
            ->where('username', $username)
            ->count_all_results('akun') > 0;
    }

    private function get_sender_name($sender)
    {
        $sender_variants = $this->phone_variants($sender);

        if (!empty($sender_variants)) {
            $user = $this->db
                ->group_start()
                ->where_in('no_wa', $sender_variants)
                ->group_end()
                ->get('users')
                ->row();

            if ($user) {
                return $user->username ?: ('WhatsApp ' . $sender);
            }
        }

        return 'WhatsApp ' . $sender;
    }

    private function start_bulk_add_session($sender)
    {
        $this->clear_session($sender);

        $this->db->insert('whatsapp_command_sessions', [
            'sender' => $sender,
            'state' => 'awaiting_bulk_add',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function get_active_session($sender)
    {
        return $this->db
            ->where('sender', $sender)
            ->where('updated_at >=', date('Y-m-d H:i:s', strtotime('-30 minutes')))
            ->order_by('id', 'DESC')
            ->get('whatsapp_command_sessions')
            ->row();
    }

    private function clear_session($sender)
    {
        if (!$this->db->table_exists('whatsapp_command_sessions')) {
            return;
        }

        $this->db
            ->where('sender', $sender)
            ->delete('whatsapp_command_sessions');
    }

    private function is_allowed_sender($sender)
    {
        $allowed = $this->config->item('fonnte_allowed_senders');
        $allowed = is_array($allowed) ? $allowed : [];

        if (empty($allowed)) {
            return true;
        }

        $allowed = $this->normalize_phones($allowed);

        return in_array($sender, $allowed, true);
    }

    private function normalize_phones($phones)
    {
        $normalized = [];

        foreach ($phones as $phone) {
            $phone = $this->normalize_phone($phone);

            if ($phone !== '') {
                $normalized[] = $phone;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalize_phone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (strpos($phone, '0') === 0) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    private function phone_variants($phone)
    {
        $phone = $this->normalize_phone($phone);
        $variants = [$phone];

        if (strpos($phone, '62') === 0) {
            $variants[] = '0' . substr($phone, 2);
        }

        return array_values(array_unique($variants));
    }

    private function send_fonnte_message($target, $message, $inboxid = null)
    {
        $token = (string) $this->config->item('fonnte_token');

        if ($token === '' || $this->is_blocked_recipient($target)) {
            return false;
        }

        $curl = curl_init();

        $post_fields = [
            'target' => $target,
            'message' => $message,
            'countryCode' => (string) $this->config->item('fonnte_country_code')
        ];

        if (!empty($inboxid)) {
            $post_fields['inboxid'] = $inboxid;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $token
            ],
        ]);

        curl_exec($curl);
        curl_close($curl);

        return true;
    }

    private function is_blocked_recipient($target)
    {
        $blocked = $this->config->item('fonnte_blocked_recipients');
        $blocked = is_array($blocked) ? $this->normalize_phones($blocked) : [];

        return in_array($this->normalize_phone($target), $blocked, true);
    }

    private function ensure_session_table()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `whatsapp_command_sessions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `sender` VARCHAR(30) NOT NULL,
                `state` VARCHAR(50) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_whatsapp_command_sender` (`sender`, `updated_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
    }

    private function ensure_webhook_log_table()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `whatsapp_webhook_log` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `sender` VARCHAR(30) NULL,
                `message` TEXT NULL,
                `payload` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_whatsapp_webhook_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
    }

    private function json_response($data, $status_code = 200)
    {
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
