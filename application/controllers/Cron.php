<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Jakarta');
        $this->load->database();
        $this->config->load('fonnte');
    }

    public function send_expired_whatsapp($key = null)
    {
        $cron_key = (string) $this->config->item('fonnte_cron_key');

        if ($cron_key !== '' && !hash_equals($cron_key, (string) $key)) {
            return $this->json_response([
                'status' => 'error',
                'message' => 'Cron key tidak valid'
            ], 403);
        }

        $this->ensure_whatsapp_log_table();

        $today = date('Y-m-d');
        $targets = $this->get_notification_targets();
        $dry_run = $this->input->get('dry_run') === '1';
        $force_send = $this->input->get('force') === '1';

        if (empty($targets)) {
            return $this->json_response([
                'status' => 'error',
                'message' => 'Nomor tujuan WhatsApp belum tersedia'
            ], 422);
        }

        $accounts = $this->get_accounts_expired_on($today);
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $details = [];

        foreach ($accounts as $account) {
            foreach ($targets as $target) {
                $log_id = null;

                if (!$force_send) {
                    $log_id = $this->reserve_whatsapp_notification($account->id_akun, $target, $today);
                }

                if (!$force_send && !$log_id) {
                    $skipped++;
                    $details[] = [
                        'akun_id' => $account->id_akun,
                        'nama_akun' => $account->nama_akun,
                        'target' => $target,
                        'sent' => false,
                        'skipped_reason' => 'Sudah pernah diproses hari ini'
                    ];
                    continue;
                }

                $message = $this->build_expired_message($account);

                if ($dry_run) {
                    $skipped++;
                    $details[] = [
                        'akun_id' => $account->id_akun,
                        'nama_akun' => $account->nama_akun,
                        'target' => $target,
                        'sent' => false,
                        'dry_run' => true
                    ];
                    continue;
                }

                $result = $this->send_fonnte_message($target, $message);

                $this->save_whatsapp_notification_result(
                    $log_id,
                    $account->id_akun,
                    $target,
                    $today,
                    $result
                );

                if ($result['success']) {
                    $sent++;
                } else {
                    $failed++;
                }

                $details[] = [
                    'akun_id' => $account->id_akun,
                    'nama_akun' => $account->nama_akun,
                    'target' => $target,
                    'sent' => $result['success']
                ];
            }
        }

        return $this->json_response([
            'status' => 'success',
            'date' => $today,
            'dry_run' => $dry_run,
            'force' => $force_send,
            'accounts' => count($accounts),
            'targets' => count($targets),
            'target_numbers' => $targets,
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'details' => $details
        ]);
    }

    public function test_fonnte_targets($key = null)
    {
        $cron_key = (string) $this->config->item('fonnte_cron_key');

        if ($cron_key !== '' && !hash_equals($cron_key, (string) $key)) {
            return $this->json_response([
                'status' => 'error',
                'message' => 'Cron key tidak valid'
            ], 403);
        }

        $targets = $this->get_notification_targets();
        $details = [];
        $sent = 0;
        $failed = 0;

        foreach ($targets as $target) {
            $result = $this->send_fonnte_message(
                $target,
                "Test Notifikasi Kevstore\n\nPesan test Fonnte berhasil dipanggil pada " . date('d-m-Y H:i:s') . "."
            );

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }

            $details[] = [
                'target' => $target,
                'sent' => $result['success'],
                'response' => $result['response']
            ];
        }

        return $this->json_response([
            'status' => 'success',
            'targets' => count($targets),
            'target_numbers' => $targets,
            'sent' => $sent,
            'failed' => $failed,
            'details' => $details
        ]);
    }

    private function get_accounts_expired_on($date)
    {
        $expired_date = "CASE WHEN expired_password REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN expired_password ELSE NULL END";

        return $this->db
            ->where($expired_date . ' = ' . $this->db->escape($date), null, false)
            ->order_by('nama_akun', 'ASC')
            ->get('akun')
            ->result();
    }

    private function get_notification_targets()
    {
        $targets = $this->config->item('fonnte_expired_targets');
        $numbers = is_array($targets) ? $targets : [];

        $admins = $this->db
            ->select('no_wa')
            ->where('tipe_user', 'admin')
            ->where('no_wa IS NOT NULL', null, false)
            ->where('no_wa !=', '')
            ->get('users')
            ->result();

        foreach ($admins as $admin) {
            $numbers[] = $admin->no_wa;
        }

        return $this->normalize_targets($numbers);
    }

    private function normalize_targets($targets)
    {
        $normalized = [];
        $blocked = $this->config->item('fonnte_blocked_recipients');
        $blocked = is_array($blocked) ? $this->normalize_phone_list($blocked) : [];

        foreach ($targets as $target) {
            $target = $this->normalize_phone($target);

            if ($target !== '' && !in_array($target, $blocked, true)) {
                $normalized[] = $target;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalize_phone_list($phones)
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

    private function build_expired_message($account)
    {
        $web_url = (string) $this->config->item('fonnte_web_url');
        $web_url = $web_url !== '' ? $web_url : base_url();

        return "Notifikasi Kevstore\n\n"
            . "Akun expired hari ini.\n"
            . "Nama akun: " . $account->nama_akun . "\n"
            . "Username: " . $account->username . "\n"
            . "Password: " . $account->password . "\n"
            . "Tanggal expired: " . date('d-m-Y', strtotime($account->expired_password)) . "\n\n"
            . "Link web: " . $web_url . "\n\n"
            . "Silakan cek dan update akun.";
    }

    private function send_fonnte_message($target, $message)
    {
        $token = (string) $this->config->item('fonnte_token');

        if ($token === '') {
            return [
                'success' => false,
                'response' => 'Token Fonnte belum diisi'
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'target' => $target,
                'message' => $message,
                'countryCode' => (string) $this->config->item('fonnte_country_code')
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $token
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $error !== '') {
            return [
                'success' => false,
                'response' => $error ?: 'Tidak ada response dari Fonnte'
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'success' => is_array($decoded) && !empty($decoded['status']),
            'response' => $response
        ];
    }

    private function already_sent($akun_id, $target, $date)
    {
        return $this->db
            ->where('akun_id', (int) $akun_id)
            ->where('target', $target)
            ->where('notification_date', $date)
            ->where('status', 'sent')
            ->count_all_results('whatsapp_notification_log') > 0;
    }

    private function reserve_whatsapp_notification($akun_id, $target, $date)
    {
        if ($this->already_sent($akun_id, $target, $date)) {
            return false;
        }

        $this->db->query(
            "INSERT IGNORE INTO `whatsapp_notification_log`
                (`akun_id`, `target`, `notification_date`, `status`, `response`, `created_at`)
             VALUES (?, ?, ?, 'processing', 'Reserved before sending', ?)",
            [
                (int) $akun_id,
                $target,
                $date,
                date('Y-m-d H:i:s')
            ]
        );

        return $this->db->affected_rows() > 0 ? $this->db->insert_id() : false;
    }

    private function save_whatsapp_notification_result($log_id, $akun_id, $target, $date, $result)
    {
        $data = [
            'status' => $result['success'] ? 'sent' : 'failed',
            'response' => $result['response']
        ];

        if ($log_id) {
            $this->db
                ->where('id', (int) $log_id)
                ->update('whatsapp_notification_log', $data);
            return;
        }

        $this->db->query(
            "INSERT INTO `whatsapp_notification_log`
                (`akun_id`, `target`, `notification_date`, `status`, `response`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `status` = VALUES(`status`),
                `response` = VALUES(`response`),
                `created_at` = VALUES(`created_at`)",
            [
                (int) $akun_id,
                $target,
                $date,
                $data['status'],
                $data['response'],
                date('Y-m-d H:i:s')
            ]
        );
    }

    private function ensure_whatsapp_log_table()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `whatsapp_notification_log` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `akun_id` INT NULL,
                `target` VARCHAR(30) NOT NULL,
                `notification_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL,
                `response` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_whatsapp_notification` (`akun_id`, `target`, `notification_date`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");

        $this->db->query("
            DELETE newer
            FROM `whatsapp_notification_log` newer
            INNER JOIN `whatsapp_notification_log` older
                ON older.`akun_id` = newer.`akun_id`
                AND older.`target` = newer.`target`
                AND older.`notification_date` = newer.`notification_date`
                AND older.`id` < newer.`id`
        ");

        $has_unique_index = $this->db
            ->where('TABLE_SCHEMA', $this->db->database)
            ->where('TABLE_NAME', 'whatsapp_notification_log')
            ->where('INDEX_NAME', 'uniq_whatsapp_notification_once')
            ->count_all_results('INFORMATION_SCHEMA.STATISTICS') > 0;

        if (!$has_unique_index) {
            $this->db->query("
                ALTER TABLE `whatsapp_notification_log`
                ADD UNIQUE KEY `uniq_whatsapp_notification_once` (`akun_id`, `target`, `notification_date`)
            ");
        }
    }

    private function json_response($data, $status_code = 200)
    {
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
