<?php

/* Admin class for script administrator*/

class Admin
{
    // This function is responsible for saving settings all data 
    function updateSettings($data){
        $status = true;
        $message = array();
        foreach ($data as $key => $value) {
            $validate_data = clean_and_validate($key, $value);
            $value = $validate_data[0];
            if($validate_data[1][0]){
                app('db')->where('name', $key);
                if(app('db')->getOne('settings')){
                    app('db')->where ('name', $key);
                    app('db')->update('settings', array('value' => $value));
                }else{
                    app('db')->insert ('settings', array('name' => $key, 'value' => $value));
                }
            }else{
                $status = false;
                array_push($message, $validate_data[1][1]);
            }
        }
        return array($status, $message);
    }

}
