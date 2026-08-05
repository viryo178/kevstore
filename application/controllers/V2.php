<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class V2 extends CI_Controller {
    public function index($path = NULL) {
        $index = FCPATH . 'v2/index.html';

        if (!is_file($index)) {
            show_404();
            return;
        }

        $this->output
            ->set_content_type('text/html', 'utf-8')
            ->set_output(file_get_contents($index));
    }
}
