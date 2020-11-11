<?php
namespace App;

/* This class is handling all the requests in the fornt end*/

class homeController{

    function __construct() {
        // Verify CSFR
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(! app('csfr')->verifyToken(SECRET_KEY) ){
                header('HTTP/1.0 403 Forbidden');
                exit();
            }
        }
    }

    // main index function to  load homepage
    public function index(){
        $data = array();
        if (!(app('auth')->user() && app('auth')->user()['user_type'] == 1)) {
            app('db')->where ('status', '1');
            app('db')->where ('is_visible', '1');
        }
        echo app('twig')->render('index.html', $data);
    }
    public function chat(){
        $data = array();
        if (!(app('auth')->user() && app('auth')->user()['user_type'] == 1)) {
            app('db')->where ('status', '1');
            app('db')->where ('is_visible', '1');
        }
        $chat_rooms_list = app('db')->get('chat_rooms');
        $chat_rooms = array();
        foreach ($chat_rooms_list as $chat_room) {
            app('db')->join("chat_groups cg", "cg.id=gu.chat_group", "LEFT");
            app('db')->where ('cg.chat_room', $chat_room['id']);
            app('db')->where ('cg.slug', 'general');
            app('db')->get('group_users gu', null, 'gu.*');
            $chat_room['users_count'] = app('db')->count;
            array_push($chat_rooms, $chat_room);
        }
        $data['chat_rooms'] = $chat_rooms;
        $data['timezone_list'] = $this->get_timezone_list(SETTINGS['timezone']);
        echo app('twig')->render('chat.html', $data);
    }

    // load chat room pages for a given slug
    public function chat_room($chatroomslug){
        if (app('auth')->isAuthenticated() == true) {
            app('db')->where ('slug', $chatroomslug);
            if ($chat_room = app('db')->getOne('chat_rooms')) {
                // Get room's default group
                app('db')->where ('slug', 'general');
                app('db')->where ('chat_room', $chat_room['id']);
                $chat_group = app('db')->getOne('chat_groups');

                // Check if user already in this group else add
                app('db')->where ('user', app('auth')->user()['id']);
                app('db')->where ('chat_group', $chat_group['id']);
                $exist_user = app('db')->getOne('group_users');
                if ($chat_group) {
                    $data = array();
                    $join_chat = true;
                    if(!$exist_user){
                        if (app('request')->method=='POST') {
                            $post_data = app('request')->body;
                            if ($chat_room['is_protected']){
                                if (array_key_exists("pin", $post_data)){
                                    if ($chat_room['password'] != $post_data['pin']){
                                        app('msg')->error("Wrong PIN");
                                        $join_chat = false;
                                    }
                                }else{
                                    app('msg')->error("PIN missing");
                                    $join_chat = false;
                                }
                            }
                            if ($join_chat){
                                $insert_data = Array (
                                    "user" => app('auth')->user()['id'],
                                    "chat_group" => $chat_group['id'],
                                    "user_type" => 2,
                                    "status" => 1,
                                    "created_at" => app('db')->now(),
                                    "updated_at" => app('db')->now()
                                );
                                app('db')->insert ('group_users', $insert_data);
                            }

                        }
                    }

                    $data['chat_room'] = $chat_room;
                    $active_room = true;
                    if($chat_room['status'] == 2){
                        if(app('auth')->user()['user_type'] != 1){
                            $active_room = false;
                        }
                    }

                    $data['active_room'] = $active_room;
                    if ($active_room){
                        if ($join_chat) {
                            // get group user data
                            app('db')->where ('user', app('auth')->user()['id']);
                            app('db')->where ('chat_group', $chat_group['id']);
                            $group_user = app('db')->getOne('group_users');
                            if ($group_user) {
                                if($group_user['status'] == 3){
                                    $data['kicked_user'] = true;
                                    echo app('twig')->render('join_chatroom.html', $data);
                                }else{
                                    $data['chat_group'] = $chat_group;
                                    $data['timezone_list'] = $this->get_timezone_list();
                                    echo app('twig')->render('chat/chat_room.html', $data);
                                }
                            }else{
                                echo app('twig')->render('chat/join_chatroom.html', $data);
                            }
                        }else{
                            echo app('twig')->render('chat/join_chatroom.html', $data);
                        }
                    }else{
                        echo app('twig')->render('chat/join_chatroom.html', $data);
                    }

                }else{
                    header("HTTP/1.0 404 Not Found");
                }

            }else{
                header("HTTP/1.0 404 Not Found");
            }
        } else {
            header("Location: " . route('login')."?next=".app('request')->fullurl);
        }

    }

    // load login page
    public function login(){
        if (app('request')->method=='POST') {
            $post_data = app('request')->body;
            if ($post_data && array_key_exists("email", $post_data) && array_key_exists("password", $post_data)) {
                $login = app('auth')->authenticate($post_data['email'], $post_data['password']);
                if($login){
                    if (isset($_GET['next'])) {
                        if (filter_var($_GET['next'], FILTER_VALIDATE_URL) !== false) {
                            header("Location: " . $_GET['next']);
                        }else {
                            header("Location: " . route('index'));
                        }
                    }else{
                        header("Location: " . route('index'));
                    }
                }else{
                    $data = array();
                    echo app('twig')->render('login.html', $data);
                }
            }
        }else{
            $data = array();
            echo app('twig')->render('login.html', $data);
        }
    }

    // log out and destroy sessions
    public function logout(){
        session_destroy();
        header("Location: " . route('index'));
    }

    // load register page
    public function register(){
        if (app('request')->method=='POST') {
            $data = array();
            $data = $post_data = app('request')->body;

            if ($post_data && array_key_exists("email", $post_data) && array_key_exists("user_name", $post_data)
                && array_key_exists("first_name", $post_data) && array_key_exists("last_name", $post_data)
                && array_key_exists("password", $post_data)) {
                $registration = app('auth')->registerNewUser($post_data['user_name'], $post_data['first_name'], $post_data['last_name'],
                                                             $post_data['email'], $post_data['password'], $post_data['password_repeat']);
                if($registration){
                    $login = app('auth')->authenticate($post_data['email'], $post_data['password']);
                    if($login){
                        header("Location: " . route('chat'));
                    }
                }else{
                    echo app('twig')->render('register.html', $data);
                }
            }else{
                echo app('twig')->render('register.html', $data);
            }
        }else{
            $data = array();
            echo app('twig')->render('register.html', $data);
        }
    }

    // load forget password page
    public function forgot_password(){
        if (app('request')->method=='POST') {
            $data = array();
            $post_data = app('request')->body;
            if ($post_data && array_key_exists("email", $post_data)) {
                app('auth')->sendResetPasswordLink($post_data['email']);
            }
            echo app('twig')->render('forgot_password.html', $data);
        }else{
            $data = array();
            echo app('twig')->render('forgot_password.html', $data);
        }
    }

    // load reset password page
    public function reset_password(){
        if (app('request')->method=='POST') {
            $data = array();
            $post_data = app('request')->body;
            if ($post_data && array_key_exists("password", $post_data) && array_key_exists("reset_key", $post_data)){
                $reset_key = app('purify')->xss_clean($_GET['reset_key']);
                $validate_data = clean_and_validate("password", $post_data['password']);
                $password = $validate_data[0];
                $valid = true;
                $message = '<ul>';
                if(!$validate_data[1][0]){
                    $valid = false;
                    foreach ($validate_data[1][1]['password'] as $each_error) {
                        $message .= "<li>".$each_error."</li>";
                    }
                }
                $message .= "</ul>";

                if($valid){
                    $reset = app('auth')->resetPassword($reset_key,$password);
                    if ($reset[0]) {
                        app('msg')->success($reset[1]);
                        header("Location: " . route('login'));
                    }else{
                        app('msg')->error($reset[1]);
                        if (isset($_GET['reset_key'])) {
                            $value = app('purify')->xss_clean($_GET['reset_key']);
                            $data['reset_key'] = $value;
                        }
                        echo app('twig')->render('reset_password.html', $data);
                    }
                }else {
                    app('msg')->error($message);
                    echo app('twig')->render('reset_password.html', $data);
                }
            }
        }else{
            $data = array();
            if (isset($_GET['reset_key'])) {
                $value = app('purify')->xss_clean($_GET['reset_key']);
                $data['reset_key'] = $value;
            }
            echo app('twig')->render('reset_password.html', $data);
        }
    }

    public function get_timezone_list($selected_timezone=False){
        $opt = '';
        $regions = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');
        $tzs = timezone_identifiers_list();
        $optgroup = '';
        sort($tzs);
        $timestamp = time();
        if (!$selected_timezone) {
            $selected_timezone = SETTINGS['timezone'];
            if(app('auth')->user()['timezone']){
                $selected_timezone = app('auth')->user()['timezone'];
            }
        }

        foreach ($tzs as $tz) {
            $z = explode('/', $tz, 2);
            date_default_timezone_set($tz); //for each timezone offset
            $diff_from_GMT = 'GMT ' . date('P', $timestamp);
            if (count($z) != 2 || !in_array($z[0], $regions)){
                continue;
            }
            if ($optgroup != $z[0]) {
                if ($optgroup !== ''){
                    $opt .= '</optgroup>';
                }
                $optgroup = $z[0];
                $opt .= '<optgroup label="' . htmlentities($z[0]) . '">';
            }

            $selected = "";
            if($selected_timezone == htmlentities($tz)){
                $selected = "selected";
            }
            $opt .= '<option value="' . htmlentities($tz) . '" '. $selected .' >'  . htmlentities(str_replace('_', ' ', $tz)). " - " .$diff_from_GMT . '</option>';
        }
        if ($optgroup !== ''){
            $opt .= '</optgroup>';
        }
        // change back system timezone
        date_default_timezone_set(SETTINGS['timezone']);
        return $opt;

    }

    // load customized color css file
    public function color_css(){
        header("Content-Type: text/css");
        echo app('twig')->render('css/color.css');
    }



}
