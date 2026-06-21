<?php
defined('BASEPATH') OR exit('No direct script access allowed');
    date_default_timezone_set('Asia/Jakarta');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['admin/kelola_akun'] = 'admin/kelola_akun';
$route['admin/tambah_akun'] = 'admin/tambah_akun';
$route['admin/bulk_tambah_akun'] = 'admin/bulk_tambah_akun';
$route['admin/bulk_edit_akun'] = 'admin/bulk_edit_akun';
$route['admin/edit_akun/(:num)'] = 'admin/edit_akun/$1';
$route['admin/hapus_akun/(:num)'] = 'admin/hapus_akun/$1';
$route['user/kelola_akun'] = 'user/kelola_akun';
$route['user/tambah_akun'] = 'user/tambah_akun';
$route['user/bulk_tambah_akun'] = 'user/bulk_tambah_akun';
$route['user/bulk_edit_akun'] = 'user/bulk_edit_akun';
$route['user/edit_akun/(:num)'] = 'user/edit_akun/$1';
$route['user/hapus_akun/(:num)'] = 'user/hapus_akun/$1';
$route['forgot-password'] = 'auth/forgot_password';
$route['send-otp'] = 'auth/send_otp';
$route['verify-otp'] = 'auth/verify_otp';
$route['check-otp'] = 'auth/check_otp';
$route['reset-password'] = 'auth/reset_password';
$route['update-password'] = 'auth/update_password';
$route['cron/send-expired-whatsapp/(:any)'] = 'cron/send_expired_whatsapp/$1';
$route['cron/test-fonnte-targets/(:any)'] = 'cron/test_fonnte_targets/$1';
$route['webhook/fonnte']['post'] = 'webhook/fonnte';
$route['webhook/fonnte-test']['get'] = 'webhook/fonnte_test';
$route['webhook/fonnte-simulate']['get'] = 'webhook/fonnte_simulate';

$route['api/login']['post'] = 'api/login';
$route['api/logout']['post'] = 'api/logout';
$route['api/me']['get'] = 'api/me';
$route['api/akun']['get'] = 'api/akun';
$route['api/akun']['post'] = 'api/akun';
$route['api/akun/(:num)']['get'] = 'api/akun/$1';
$route['api/akun/(:num)']['put'] = 'api/akun/$1';
$route['api/akun/(:num)']['patch'] = 'api/akun/$1';
$route['api/akun/(:num)']['delete'] = 'api/akun/$1';
$route['api/akun/(:num)/tambah-max-user']['post'] = 'api/tambah_max_user/$1';
$route['api/notifications']['get'] = 'api/notifications';
$route['api/activity']['get'] = 'api/activity';
$route['api/kepegawaian']['get'] = 'api/kepegawaian';
$route['api/kepegawaian']['post'] = 'api/kepegawaian';
$route['api/chat/messages']['get'] = 'api/chat_messages';
$route['api/chat/messages']['post'] = 'api/chat_messages';
$route['api/notes']['get'] = 'api/notes';
$route['api/notes']['post'] = 'api/notes';
$route['api/notes/(:num)']['get'] = 'api/notes/$1';
$route['api/notes/(:num)']['put'] = 'api/notes/$1';
$route['api/notes/(:num)']['patch'] = 'api/notes/$1';
$route['api/notes/(:num)']['delete'] = 'api/notes/$1';
$route['api/users']['get'] = 'api/users';
