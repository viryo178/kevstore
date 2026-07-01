<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
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

		$this->session->set_flashdata(
			'error',
			'Reset password via WhatsApp sudah dinonaktifkan. Hubungi admin untuk reset password.'
		);

		redirect('forgot-password');
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

}
