<?php
class Ujicoba extends CI_Controller {
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $pw = 'admin';
		$hash = password_hash($pw, PASSWORD_DEFAULT);
		echo $hash;
    }
}
