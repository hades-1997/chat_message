<?php
// get Server Software fallback function
if(!function_exists('apache_get_version')){
    function apache_get_version(){
        if(!isset($_SERVER['SERVER_SOFTWARE']) || strlen($_SERVER['SERVER_SOFTWARE']) == 0){
            return false;
        }
        return $_SERVER["SERVER_SOFTWARE"];
    }
}

$required_php_version = 7.1;
$currrent_php_version = phpversion();
$currrent_apache_version = apache_get_version();
$is_mysqli_installed = extension_loaded('mysqli');
$is_gd_installed = extension_loaded('gd');
$is_media_writable = is_writable(BASE_PATH.'media');
$media_perm = substr(sprintf('%o', fileperms(BASE_PATH.'media')), -4);
$is_config_writable = is_writable(BASE_PATH.'config');
$config_perm = substr(sprintf('%o', fileperms(BASE_PATH.'config')), -4);

$can_proceed = 1;

if($currrent_php_version < $required_php_version){
    $can_proceed = 0;
}

if (!$is_mysqli_installed) {
    $can_proceed = 0;
}

if (!$is_gd_installed) {
    $can_proceed = 0;
}

if (!$is_config_writable) {
    $can_proceed = 0;
}

if (!$is_media_writable) {
    $can_proceed = 0;
}

?>
