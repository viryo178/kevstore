<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->config->load('fonnte');
	}

	// ================= LOGIN =================

	public function index()
	{
		$this->load->view('auth/login');
	}

	public function proses_login()
	{
		$username = $this->input->post('username');
		$password = $this->input->post('password');

		$user = $this->db->get_where('users', [
			'username' => $username
		])->row_array();

		if (!$user) {

			$this->session->set_flashdata(
				'error',
				'Username atau password salah'
			);

			redirect('/');
		}

		if (!password_verify($password, $user['password'])) {

			$this->session->set_flashdata(
				'error',
				'Username atau password salah'
			);

			redirect('/');
		}

		$this->session->set_userdata([
			'id_user' => $user['id_user'],
			'username' => $user['username'],
			'nama_user' => $user['nama_user'] ?? $user['username'],
			'tipe_user' => $user['tipe_user'],
			'status' => $user['login'],
			'last_login_at' => date('Y-m-d H:i:s'),
		]);

		if ($user['tipe_user'] == 'admin') {
			redirect('admin');
		} else {
			redirect('user');
		}
	}

	// ================= HALAMAN LUPA PASSWORD =================

	public function forgot_password()
	{
		$this->load->view('auth/forgot_password');
	}

	// ================= KIRIM OTP =================

	public function send_otp()
	{
		$username = $this->input->post('username');

		$user = $this->db->get_where('users', [
			'username' => $username
		])->row_array();

		// user tidak ada
		if (!$user) {

			$this->session->set_flashdata(
				'error',
				'User tidak ditemukan'
			);

			redirect('forgot-password');
		}

		// nomor WA kosong
		if (empty($user['no_wa'])) {

			$this->session->set_flashdata(
				'error',
				'Nomor WhatsApp belum tersedia'
			);

			redirect('forgot-password');
		}

		// generate OTP
		$otp = rand(100000, 999999);

		$expired = date(
			'Y-m-d H:i:s',
			strtotime('+2 minutes')
		);

		// simpan OTP
		$this->db->where('id_user', $user['id_user']);

		$this->db->update('users', [
			'otp_code' => $otp,
			'otp_expired' => $expired
		]);

		// pesan WA
		$message = "Kode OTP Reset Password Anda: " . $otp . "\n\n";
		$message .= "OTP berlaku 2 menit.\n";
		$message .= "Jangan berikan kode ini kepada siapapun.";

		// ================= FONNTE =================

		if (!$this->is_blocked_recipient($user['no_wa'])) {
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.fonnte.com/send',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => array(
					'target' => $user['no_wa'],
					'message' => $message,
					'countryCode' => '62'
				),
				CURLOPT_HTTPHEADER => array(
					'Authorization: J6WgtFwxavJ312gRdiVp'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);
		}

		// simpan session reset
		$this->session->set_userdata(
			'reset_user',
			$user['id_user']
		);

		redirect('verify-otp');
	}

	// ================= HALAMAN OTP =================

	public function verify_otp()
	{
		if (!$this->session->userdata('reset_user')) {
			redirect('forgot-password');
		}

		$this->load->view('auth/verify_otp');
	}

	// ================= CEK OTP =================

	public function check_otp()
	{
		$id_user = $this->session->userdata('reset_user');

		$otp = $this->input->post('otp');

		$user = $this->db->get_where('users', [
			'id_user' => $id_user
		])->row_array();

		if (!$user) {
			show_error('User tidak ditemukan');
		}

		// cek OTP
		if (
			$user['otp_code'] == $otp &&
			strtotime($user['otp_expired']) > time()
		) {

			$this->session->set_userdata(
				'allow_reset',
				true
			);

			redirect('reset-password');
		}

		$this->session->set_flashdata(
			'error',
			'OTP salah atau expired'
		);

		redirect('verify-otp');
	}

	// ================= HALAMAN RESET PASSWORD =================

	public function reset_password()
	{
		if (!$this->session->userdata('allow_reset')) {
			redirect('/');
		}

		$this->load->view('auth/reset_password');
	}

	// ================= UPDATE PASSWORD =================

	public function update_password()
	{
		if (!$this->session->userdata('allow_reset')) {
			redirect('/');
		}

		$id_user = $this->session->userdata('reset_user');

		$password = $this->input->post('password');
		$confirm_password = $this->input->post('confirm_password');

		// password kosong
		if (empty($password)) {

			$this->session->set_flashdata(
				'error',
				'Password wajib diisi'
			);

			redirect('reset-password');
		}

		// password tidak sama
		if ($password != $confirm_password) {

			$this->session->set_flashdata(
				'error',
				'Konfirmasi password tidak sama'
			);

			redirect('reset-password');
		}

		// hash password
		$password_hash = password_hash(
			$password,
			PASSWORD_DEFAULT
		);

		// update password
		$this->db->where('id_user', $id_user);

		$this->db->update('users', [
			'password' => $password_hash,
			'otp_code' => null,
			'otp_expired' => null
		]);

		// hapus session
		$this->session->unset_userdata('reset_user');
		$this->session->unset_userdata('allow_reset');

		$this->session->set_flashdata(
			'success',
			'Password berhasil diubah'
		);

		redirect('/');
	}

	// ================= LOGOUT =================

	public function logout()
	{
		$this->session->sess_destroy();

		redirect('/');
	}

	private function is_blocked_recipient($target)
	{
		$blocked = $this->config->item('fonnte_blocked_recipients');
		$blocked = is_array($blocked) ? $this->normalize_phones($blocked) : [];

		return in_array($this->normalize_phone($target), $blocked, true);
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
}
