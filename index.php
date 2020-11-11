<?php
session_start();
define('DS', DIRECTORY_SEPARATOR);
define('BASE_PATH', __DIR__ . DS);

require BASE_PATH.'vendor/autoload.php';
require BASE_PATH.'config/init.php';

if (file_exists(BASE_PATH.'config/settings.php')) {

    // Include shortcut functions used in template htmls.
    require BASE_PATH.'config/template_functions.php';

    // URLs
    $route->get('/', 'App\homeController@index')->as('index');
    $route->get('/chat', 'App\homeController@chat')->as('chat');
    $route->any('/login', 'App\homeController@login')->as('login');
    $route->any('/logout', 'App\homeController@logout')->as('logout');
    $route->any('/register', 'App\homeController@register')->as('register');
    $route->any('/forgot-password', 'App\homeController@forgot_password')->as('forgot-password');
    $route->any('/reset-password', 'App\homeController@reset_password')->as('reset-password');
    $route->get('/css/color.css', 'App\homeController@color_css')->as('color_css');
    $route->any('/install', 'App\installController@installed')->as('installed');
    $route->any('/{chatroomslug}', 'App\homeController@chat_room')->as('chat-room');

    // Ajax
    $route->any('ajax/heartbeat', 'App\ajaxController@heartbeat')->as('ajax-heartbeat');
    $route->any('ajax/save-message', 'App\ajaxController@save_message')->as('ajax-save-message');
    $route->any('ajax/get-active-info', 'App\ajaxController@get_active_info')->as('ajax-get-active-info');
    $route->any('ajax/load-chats', 'App\ajaxController@load_chats')->as('ajax-load-chats');
    $route->any('ajax/save-profile', 'App\ajaxController@save_profile')->as('ajax-save-profile');
    $route->any('ajax/online-list', 'App\ajaxController@online_list')->as('ajax-online-list');
    $route->any('ajax/updated-chats', 'App\ajaxController@updated_chats')->as('ajax-updated-chats');
    $route->any('ajax/send-files', 'App\ajaxController@send_files')->as('ajax-send-files');
    $route->any('ajax/get-strickers', 'App\ajaxController@get_stickers')->as('ajax-get-stickers');
    $route->any('ajax/active-user-restriction', 'App\ajaxController@active_user_restriction')->as('ajax-active-user-restriction');
    $route->any('ajax/active-group-restriction', 'App\ajaxController@active_group_restriction')->as('ajax-active-group-restriction');
    $route->any('ajax/change-user-status', 'App\ajaxController@change_user_status')->as('ajax-change-user-status');
    $route->any('ajax/update-settings', 'App\ajaxController@update_settings')->as('ajax-update-settings');
    $route->any('ajax/update-chatroom', 'App\ajaxController@update_chatroom')->as('ajax-update-chatroom');
    $route->any('ajax/get-chatroom', 'App\ajaxController@get_chatroom')->as('ajax-get-chatroom');
    $route->any('ajax/chatroom-user-restriction', 'App\ajaxController@chatroom_user_restriction')->as('ajax-chatroom-user-restriction');
    $route->any('ajax/load-more-chats', 'App\ajaxController@load_more_chats')->as('ajax-load-more-chats');
    $route->any('/install/ajax', 'App\installController@ajax')->as('ajax');
}else{
    $route->any('/', 'App\installController@index')->as('index');
    $route->any('/install', 'App\installController@install')->as('install');
    $route->any('/install/ajax', 'App\installController@ajax')->as('ajax');
}

$route->end();

?>
