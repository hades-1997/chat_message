<?php

/* user authentication class */

class Auth
{
    // Check whether user is logged in
    public function isAuthenticated()
    {
        if (isset($_SESSION['user'])) {
            return true;
        } else {
            return false;
        }
    }

    // Log in user by email and password
    public function authenticate($email, $password)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            app('db')->where('email', $email);
            if ($user = app('db')->getOne('users')) {
                $passwprd_verify = password_verify($password, $user['password']);
                if ($passwprd_verify) {
                    $_SESSION['user'] = $this->user($user['id']);
                    return true;
                } else {
                    // Wrong Password
                    app('msg')->error('Wrong Password!');
                    return false;
                }
            } else {
                // Wrong Email
                app('msg')->error('Wrong Email!');
                return false;
            }
        }else{
            app('msg')->error('Email is invalid!');
            return false;
        }
    }

    // Add new user to the system
    public function registerNewUser($user_name, $first_name, $last_name, $user_email, $password, $password_repeat)
    {
        // check provided data validity
        if (empty($user_name)) {
            app('msg')->error('Username is required!');
            return false;
        } elseif (preg_match('/[^a-z_\-0-9]/i', $user_name)) {
            app('msg')->error('Username is invalid!');
            return false;
        } elseif (empty($user_email)) {
            app('msg')->error('Email is required!');
            return false;
        } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            app('msg')->error('Email is invalid!');
            return false;
        } elseif (empty($password) || empty($password_repeat)) {
            app('msg')->error('Password is required!');
            return false;
        } elseif ($password !== $password_repeat) {
            app('msg')->error('Password mismatch!');
            return false;
        } else {
            app('db')->where('email', $user_email);
            $user_email_exist = app('db')->getOne('users');

            app('db')->where('user_name', $user_name);
            $user_name_exist = app('db')->getOne('users');

            if ($user_email_exist) {
                app('msg')->error('Email already exists!');
                return false;
            } elseif ($user_name_exist) {
                app('msg')->error('Username is already taken!');
                return false;
            } else {
                $data = array("user_name" => $user_name,
                               "first_name" => $first_name,
                               "last_name" => $last_name,
                               "email" => $user_email,
                               "password" => $password,
                            );

                $valid = true;
                $message = '<ul>';
                foreach ($data as $key => $value) {
                    $validate_data = clean_and_validate($key, $value);
                    $value = $validate_data[0];
                    $data[$key] = $value;
                    if(!$validate_data[1][0]){
                        $valid = false;
                        foreach ($validate_data[1][1][$key] as $each_error) {
                            $message .= "<li>".$each_error."</li>";
                        }
                    }
                }
                $message .= "</ul>";

                if($valid){
                    $data['password'] = password_hash(trim($password), PASSWORD_DEFAULT);
                    $data['user_status'] = 1;
                    $data['available_status'] = 1;
                    $data['created_at'] = app('db')->now();
                    $id = app('db')->insert ('users', $data);
                    if($id){
                        return true;
                    }else {
                        app('msg')->error('Something went wrong!');
                        return false;
                    }
                }else {
                    app('msg')->error($message);
                    return false;
                }
            }
        }
    }

    // Get user data
    public function user($id = false)
    {
        if ($id) {
            app('db')->where('id', $id);
            $user_data = app('db')->getOne('users');
            if ($user_data['avatar']) {
                $user_data['avatar_url'] = URL."media/avatars/".$user_data['avatar'];
            } else {
                $user_data['avatar_url'] = URL."static/img/user.jpg";
            }

            $user_data['user_status_class'] = "";
            if ($user_data['user_status'] == 1) {
                $user_data['user_status_class'] = "online";
            } elseif ($user_data['user_status'] == 2) {
                $user_data['user_status_class'] = "offline";
            } elseif ($user_data['user_status'] == 3) {
                $user_data['user_status_class'] = "busy";
            } elseif ($user_data['user_status'] == 4) {
                $user_data['user_status_class'] = "away";
            }
            return $user_data;
        } else {
            if (isset($_SESSION['user'])) {
                return $_SESSION['user'];
            } else {
                return false;
            }
        }
    }

    // Save user provile with provided data
    public function saveProfile($data)
    {
        app('db')->where('id', $data['id']);
        if (app('db')->update('users', $data)) {
            $_SESSION['user'] = $this->user($data['id']);
            return [true, 'Successfully saved!'];
        } else {
            app('msg')->error('Something went wrong!');
            return [false, 'Something went wrong!'];
        }

    }

    // Send password reset link with a reset key
    function sendResetPasswordLink($email){
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            app('db')->where('email', $email);
            $user_email_exist = app('db')->getOne('users');
            if ($user_email_exist) {
                $reset_key = uniqid('cn_',true);
                $data = array();
                $data['reset_key'] = $reset_key;
                app('db')->where('email', $email);
                app('db')->update('users', $data);
                $email_data['reset_link'] = route('reset-password').'?reset_key='.$reset_key;
                $body = app('twig')->render('emails/password_reset.html', $email_data);
                send_mail($email, 'ChatNet Password Reset', $body);
            }
            app('msg')->success('If the provided email is on our database, We have sent a password reset link.');
            return [true, 'Email sent!'];
        }else{
            app('msg')->error('Email is invalid!');
            return [false, ''];
        }
    }

    // Reset password if the reset key is valid
    function resetPassword($reset_key, $password){
        app('db')->where('reset_key', $reset_key);
        $user_exist = app('db')->getOne('users');
        if (empty($password)) {
            return [false, 'Empty Password'];
        } elseif (empty($reset_key)) {
            return [false, 'Empty Reset Key'];
        }elseif (!$user_exist) {
            return [false, 'Wrong Reset Key'];
        }else{
            $data = array();
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            $data['reset_key'] = '';
            app('db')->where('reset_key', $reset_key);
            app('db')->update('users', $data);
            return [true, 'Password Reseted Successfully'];
        }
    }

}
