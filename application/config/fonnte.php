<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['fonnte_token'] = '';
$config['fonnte_country_code'] = '62';

// Isi nomor tambahan di sini. Bisa lebih dari satu nomor.
// Sistem juga tetap mengirim ke semua user bertipe admin yang punya no_wa.
// Contoh: ['083871821218', '081234567890']
$config['fonnte_expired_targets'] = [
    '',
    ''
];
$config['fonnte_web_url'] = 'https://kevs.my.id/';
$config['fonnte_allowed_senders'] = ['6283871821218'];
$config['fonnte_blocked_recipients'] = [
    '0813122445798',
    '083871821218',
];

// Ganti key ini sebelum dipakai di production agar endpoint cron tidak mudah dipanggil orang lain.
$config['fonnte_cron_key'] = 'kevstore-expired-wa-00';
