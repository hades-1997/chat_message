<?php

// Route Init o handle all URLs
$app = System\App::instance();
$app->request = System\Request::instance();
$app->route	= System\Route::instance($app->request);
$route = $app->route;

$settings = array();

// SECRET_KEY will be used to create csrf tokens, You can change this wi your own random hash
define('SECRET_KEY', '4vm4t0fers5s1ulojfp78f9s9c');

// Check the script is installed, then init the database
if (file_exists(BASE_PATH.'config/settings.php')) {

    // Include main settings file
    require BASE_PATH.'config/settings.php';

    // Init database with settings
    $mysqli = new mysqli (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    app()->db = new MysqliDb ($mysqli);
    app()->db->setPrefix (DB_PREFIX);

    // Site Settings init
    $site_settings = app()->db->get('settings');

    foreach ($site_settings as $each_settings) {
        $settings[$each_settings['name']] = $each_settings['value'];
    }
    define('SETTINGS', $settings);

    // Timezone
    date_default_timezone_set(SETTINGS['timezone']);
    app()->db->rawQuery('SET time_zone=?', Array (date('P')));
}

// Template Init
$loader = new \Twig\Loader\FilesystemLoader(['templates', 'static']);
app()->twig= new \Twig\Environment($loader);

// Auth
require_once('classes/Auth.php');
app()->auth = new Auth();

// Chat
require_once('classes/Chat.php');
app()->chat = new Chat();

// Admin
require_once('classes/Admin.php');
app()->admin = new Admin();

// Messages
app()->msg = new \Plasticbrain\FlashMessages\FlashMessages();

// Upload
require_once('classes/Upload.php');
require_once('classes/Resize.php');

require_once('utils/utils.php');

// upload image size
$image_size = array();
$image_size['logo']['width'] = "130";
$image_size['logo']['height'] = "30";
$image_size['small_logo']['width'] = "40";
$image_size['small_logo']['height'] = "40";
$image_size['favicon']['width'] = "32";
$image_size['favicon']['height'] = "32";
define('IMAGE_SIZE', $image_size);


// PHP Mailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->SMTPAuth   = true;
$mail->Host       = array_key_exists("email_host", $settings) ? $settings['email_host'] : "";
$mail->Username   = array_key_exists("email_username", $settings) ? $settings['email_username'] : "";
$mail->Password   = array_key_exists("email_password", $settings) ? $settings['email_password'] : "";
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = array_key_exists("email_port", $settings) ? $settings['email_port'] : 587;
$mail->From = array_key_exists("email_from_address", $settings) ? $settings['email_from_address'] : "chatnet@".$_SERVER['HTTP_HOST'];
$mail->FromName = array_key_exists("email_from_name", $settings) ? $settings['email_from_name'] : "ChatNet";
// email bug fix
$mail->SMTPOptions = array(
    'ssl' => array(
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true
    )
);
app()->mail = $mail;


use voku\helper\AntiXSS;
app()->purify = new AntiXSS();

require_once('classes/Csrf.php');
app()->csfr = new Csrf();

?>
