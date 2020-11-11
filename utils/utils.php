<?php
/* This files includes all helper and utilities function used by various other major functions in the system */
// this function will be used to output jason responses with correct headers
function json_response($data=null, $status=200){
    header_remove();
    header("Content-Type: application/json");
    header('Status: ' . $status);
    $json = json_encode($data);
    if ($json === false) {
        $json = json_encode(["jsonError" => json_last_error_msg()]);
        if ($json === false) {
            $json = '{"jsonError":"unknown"}';
        }
        http_response_code(500);
    }
    echo $json;
}
// Image upload function used in profile and settings sections
function image($files, $name=false, $path="", $height=false, $width=false){
    $image = new Image($files);

    // Config
    if ($name) {
        $image->setName($name);
    }
    $image->setSize(0, 4000000); //4MB
    $image->setMime(array('jpeg', 'gif', 'png', 'jpg'));
    $image->setLocation('media/' . $path);
    //Upload
    if($image){
      $upload = $image->upload();
      if($upload){
            // Crop
            if ($height || $width) {
                if ($height == false) {
                    $height = $image->getHeight();
                }
                if ($width == false) {
                    $width = $image->getWidth();
                }

                $image = new ImageResize($upload->getFullPath());
                $image->crop($width, $height);
                $image->save($upload->getFullPath());

            }
        return array(true,$upload->getName().'.'.$upload->getMime());
      }else{
        app('msg')->error($image->getError());
        return array(false, $image->getError());
      }
  }else{
      return array(false, "No Image Found!");
  }
}
// Image upload function used in chat dropzone
function chat_image_upload($file){
    $image = new Image($file);
    $image->setSize(0, 5000000); //5MB
    $image->setMime(array('jpeg', 'gif', 'png', 'jpg'));
    $image->setLocation('media/chats/images/large');

    //Upload
    if($image){
        $upload = $image->upload();
        if($upload){
            // Crop
            $image = new ImageResize($upload->getFullPath());
            $image->resizeToWidth(600);
            $upload->setName(uniqid()."_".$image->getDestWidth()."x".$image->getDestHeight());
            $image->save($upload->getFullPath());

            // save medium image
            $medium_image = "media/chats/images/medium/".$upload->getName() .".". $upload->getMime();
            if(copy($upload->getFullPath(), $medium_image)){
                $medium_image_crop = new ImageResize($medium_image);
                $medium_image_crop->crop(300, 300);
                $medium_image_crop->save($medium_image);
            }

            // save thumb image
            $thumb_image = "media/chats/images/thumb/".$upload->getName() .".". $upload->getMime();
            if(copy($upload->getFullPath(), $thumb_image)){
                $thumb_image_crop = new ImageResize($thumb_image);
                $thumb_image_crop->crop(150, 150);
                $thumb_image_crop->save($thumb_image);
            }
            return $upload->getName().'.'.$upload->getMime();
        }else{
            app('msg')->error($image->getError());
            return app('msg')->error($image->getError());
        }
    }
}
// Send mail function to send reset password links and other emails
function send_mail($to, $subject, $body){
    try {
        //Recipients
        app('mail')->addAddress($to);
        // Content
        app('mail')->isHTML(true);
        app('mail')->Subject = $subject;
        app('mail')->Body = $body;
        app('mail')->send();
        return true;
    } catch (Exception $e) {
        app('msg')->error(app('mail')->ErrorInfo);
    }
}
// Crean input $_POST data and validate according to given rules
function clean_and_validate($key, $value){

    $value_and_rules = clean_get_validation_rules($key, $value);
    $value = $value_and_rules[0];
    $rules = $value_and_rules[1];

    $validator = new Valitron\Validator([$key => $value]);
    if($rules){
        foreach ($rules as $rule) {
            if(is_array($rule)){
                foreach ($rule as $key_rule => $rule_params) {
                    $validator->rule($key_rule, $key, $rule_params);
                }
            }else{
                $validator->rule($rule, $key);
            }
        }
    }


    if($validator->validate()){
        return array($value, array(true, ""));
    }else{
        return array($value, array(false, $validator->errors()));
    }
}
// get defined validation rules for given feilds
function clean_get_validation_rules($field, $value){
    if(in_array($field, array('footer_js', 'header_js'))){
        $value = clean($value);
    }elseif (in_array($field, array('password'))) {
        $value = trim($value);
    }else{
        $value = clean($value);
        $value = app('purify')->xss_clean($value);
    }

    switch ($field) {
        case "site_name":
            return array($value, array('required', ['lengthMax' => '200']));
            break;
        case "email_host":
            return array($value, array('required'));
            break;
        case "email_username":
            return array($value, array('required'));
            break;
        case "email_password":
            return array($value, array('required'));
            break;
        case "email":
        case "email_from_address":
            return array($value, array('required', 'email'));
            break;
        case "email_from_name":
            return array($value, array('required'));
            break;
        case "chat_receive_seconds":
        case "user_list_check_seconds":
        case "chat_status_check_seconds":
        case "online_status_check_seconds":
        case "typing_status_check_seconds":
            return array($value, array('required', 'integer', ['min' => '1']));
            break;
        case "home_bg_gradient_1":
        case "home_bg_gradient_2":
        case "home_text_color":
        case "home_header_bg_color":
        case "home_header_text_color":
        case "chat_userlist_bg_gradient_1":
        case "chat_userlist_bg_gradient_2":
        case "chat_userlist_text_color":
        case "chat_container_bg_gradient_1":
        case "chat_container_bg_gradient_2":
        case "chat_container_text_color":
        case "chat_container_received_bubble_color":
        case "chat_container_received_text_color":
        case "chat_container_username_text_color":
        case "chat_container_sent_bubble_color":
        case "chat_container_sent_text_color":
        case "chat_info_bg_gradient_1":
        case "chat_info_bg_gradient_2":
        case "chat_info_section_header_color":
        case "chat_info_text_color":
            return array($value, array(['regex' => '/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/']));
            break;
        case "tenor_gif_limit":
            return array($value, array(['min' => '1']));
            break;
        case "name":
            return array($value, array('required'));
            break;
        case "slug":
            return array($value, array('required', 'slug'));
            break;
        case "last_name":
        case "first_name":
            return array($value, array('required', 'alphaNum', ['lengthMax' => '20']));
            break;
        case "user_name":
            return array($value, array(['lengthMin' => '3'], ['lengthMax' => '10']));
            break;
        case "password":
            return array($value, array(['lengthMin' => '4'], ['lengthMax' => '20']));
            break;
        case "pin":
            return array($value, array(['lengthMin' => '3'], ['lengthMax' => '10']));
            break;

        default:
            return array($value, false);
    }
}
// basic clean function to clean input data
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
