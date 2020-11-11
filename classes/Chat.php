<?php

class Chat {

    // Save new chat message with it's relavent type to database
    function saveNewMessage($sender, $message_content, $to_user=Null, $to_group=Null, $to_room=Null, $message_type, $chat_meta_id=Null){
        $message_content  = trim($message_content);
        $message_id = null;
        $time = "";
        // check provided data validity
        if(empty($sender)){
            $status = false;
            $message = "Please Login!";
        }elseif (empty($message_content)) {
            $status = false;
            $message = "Message Content Empty!";
        }elseif (empty($to_room)) {
            $status = false;
            $message = "Empty Room!";
        }elseif (empty($to_user) and empty($to_group)) {
            $status = false;
            $message = "Please select sending user or group!";
        }else{
            if(empty($to_user)){
                $to_user = null;
            }else{
                $to_user = (int)$to_user;
            }

            if(empty($to_group)){
                $to_group = null;
            }else{
                $to_group = (int)$to_group;
            }

            if($to_user){
                if($to_user > $sender) {
                    $user_1 = $sender;
                    $user_2 = $to_user;
                }else{
                    $user_1 = $to_user;
                    $user_2 = $sender;
                }

                $data = Array ("user_1" => $user_1,
                               "user_2" => $user_2,
                               "sender_id" => $sender,
                               "room_id" => $to_room,
                               "type" => $message_type,
                               "message" => $message_content,
                               "status" => 1,
                               "time" => app('db')->now(),
                               "updated_at" => app('db')->now(),
                            );
                $id = app('db')->insert ('private_chats', $data);
                app('db')->where('id', $id);
                $chat_data = app('db')->getOne('private_chats');

                if($chat_meta_id){
                    app('db')->where('id', $chat_meta_id);
                    $chat_meta_data = app('db')->getOne('private_chat_meta');

                    $update_meta = array();
                    $update_meta['chat_meta_id'] = $chat_meta_data['id'];
                    $update_meta['unread_count'] = $chat_meta_data['unread_count'] + 1;
                    $update_meta['last_chat_id'] = $id;
                    $update_meta['is_typing'] = 0;
                    $this->updateChatMetaData($update_meta);

                    //active user chat meta
                    $to_user_chat_meta_data = $this->getChatMetaData($to_user, $sender, $to_room);
                    $to_user_update_meta = array();
                    $to_user_update_meta['chat_meta_id'] = $to_user_chat_meta_data['id'];
                    $to_user_update_meta['last_chat_id'] = $id;
                    $this->updateChatMetaData($to_user_update_meta);
                }
            }else{
                $data = Array ("sender_id" => $sender,
                               "group_id" => $to_group,
                               "room_id" => $to_room,
                               "type" => $message_type,
                               "message" => $message_content,
                               "status" => 1,
                               "time" => app('db')->now(),
                               "updated_at" => app('db')->now(),
                            );
                $id = app('db')->insert ('group_chats', $data);
                app('db')->where('id', $id);
                $chat_data = app('db')->getOne('group_chats');

                if($chat_meta_id){
                    $update_meta = array();
                    $update_meta['updated_at'] = app('db')->now();
                    $update_meta['unread_count'] = app('db')->inc(1);

                    app('db')->where('chat_group', $to_group);
                    app('db')->where('id != ' . $chat_meta_id);
                    app('db')->update('group_users', $update_meta);
                }
            }


            if($id){
                $status = true;
                $message = "Message Sent";
                $message_id = $id;
                $time = $chat_data['time'];

            }else{
                $status = false;
                $message = "Please select sending user or group!";
                $time = "";
            }
        }
        $return_data = array();
        $return_data['status'] = $status;
        $return_data['message'] = $message;
        $return_data['id'] = $message_id;
        $return_data['time'] = $time;
        return $return_data;
    }

    // Get active users list
    function get_active_list($room_id){
        $data = array();
        app('db')->where ('id', $room_id);
        if ($chat_room = app('db')->getOne('chat_rooms')) {
            $loged_in_user_id = app('auth')->user()['id'];
            // Get room's default group
            app('db')->where ('slug', 'general');
            app('db')->where ('chat_room', $chat_room['id']);

            app('db')->join("group_users gu", "gu.chat_group=cg.id", "LEFT");
            app('db')->where("gu.user", $loged_in_user_id);
            app('db')->where ('cg.slug', 'general');
            app('db')->where ('cg.chat_room', $chat_room['id']);
            $chat_group = app('db')->getOne("chat_groups cg", null, "cg.*, gu.unread_count, gu.is_muted");

            // create chat room cover image url
            if ($chat_room['cover_image']) {
                $chat_room['cover_url'] = URL."media/chatrooms/".$chat_room['cover_image'];
            }else {
                $chat_room['cover_url'] = URL."static/img/group.png";
            }

            // create chat group cover image url
            if ($chat_group['cover_image']) {
                $chat_group['cover_url'] = URL."media/chatgroups/".$chat_group['cover_image'];
            } else {
                $chat_group['cover_url'] = $chat_room['cover_url'];
            }

            $chat_group['room_data'] = $chat_room;
            if ($chat_group) {

                $sql = "SELECT
                    g.id as group_id,
                    u.id as user_id, u.first_name, u.last_name, u.last_seen, u.avatar, u.user_status, u.timezone,
                    cm.unread_count, cm.is_blocked as blocked_by_him, cmr.is_favourite, cmr.is_muted, cmr.is_blocked as blocked_by_you,
                    c.message as last_message, c.type as last_message_type, c.time as last_message_time
                FROM
                    cn_group_users g
                LEFT JOIN cn_users u ON
                    g.user = u.id
                LEFT JOIN cn_private_chat_meta cm ON
                    g.user = cm.from_user AND cm.to_user = ? AND cm.room_id = $room_id
                LEFT JOIN cn_private_chat_meta cmr ON
                    g.user = cmr.to_user AND cmr.from_user = ? AND cmr.room_id = $room_id
                LEFT JOIN (
                   SELECT id, message, type, user_1, user_2, time
                   FROM cn_private_chats
                ) c ON c.id = cm.last_chat_id
                WHERE
                    g.chat_group = ?
                AND
                    u.id != ?
                ORDER BY
                    cm.is_blocked ASC,
                    cmr.is_blocked ASC,
                    cm.last_chat_id DESC";

                $group_users = app('db')->rawQuery(
                    $sql, array(
                        $loged_in_user_id,
                        $loged_in_user_id,
                        $chat_group['chat_group'],
                        $loged_in_user_id,
                    )
                );

                $data['default_group'] = $chat_group;
                $data['list'] = $group_users;
            }
        }
        return $data;
    }

    // Update database once an user read a message
    function updateChatReadStatus($sender, $to_user=Null, $to_group=Null, $to_room=Null, $last_chat_id=Null){
        if($to_user){
            if($to_user > $sender) {
                $user_1 = $sender;
                $user_2 = $to_user;
            }else{
                $user_1 = $to_user;
                $user_2 = $sender;
            }

            if ($last_chat_id) {
                app('db')->where ('id', $last_chat_id, ">");
            }

            app('db')->where ('user_1', $user_1);
            app('db')->where ('user_2', $user_2);
            app('db')->where ('room_id', $to_room);
            app('db')->where ("sender_id != " . app('auth')->user()['id']);
            app('db')->where ('status', 1);
            app('db')->update('private_chats', array('status' => 2, "updated_at" => app('db')->now()));

            //active user chat meta
            $to_user_chat_meta_data = $this->getChatMetaData($to_user, $sender, $to_room);
            $to_user_update_meta = array();
            $to_user_update_meta['chat_meta_id'] = $to_user_chat_meta_data['id'];
            $to_user_update_meta['unread_count'] = 0;
            $this->updateChatMetaData($to_user_update_meta);

            return true;
        }else{
            if ($last_chat_id) {
                app('db')->where ('id', $last_chat_id, ">");
            }

            app('db')->where ('group_id', $to_group);
            app('db')->where ("sender_id != " . app('auth')->user()['id']);
            app('db')->where ('status', 1);
            app('db')->update('group_chats', array('status' => 2, "updated_at" => app('db')->now()));

            $group_chat_meta = $this->getGroupChatMetaData(app('auth')->user()['id'], $to_group);
            $update_meta = array();
            $update_meta['chat_meta_id'] = $group_chat_meta['id'];
            $update_meta['unread_count'] = 0;
            $this->updateGroupChatMetaData($update_meta);
            return true;
        }
    }

    // get last updated time for a conversation
    function getLastUpdatedTime($sender, $to_user=Null, $to_group=Null, $to_room=Null){
        if($to_user){
            if($to_user > $sender) {
                $user_1 = $sender;
                $user_2 = $to_user;
            }else{
                $user_1 = $to_user;
                $user_2 = $sender;
            }

            app('db')->where ('user_1', $user_1);
            app('db')->where ('user_2', $user_2);
            app('db')->where ('room_id', $to_room);
            app('db')->orderBy("updated_at","desc");
            $updated_chats = app('db')->getValue('private_chats', "updated_at", 1);
            if ($updated_chats) {
                return $updated_chats;
            }else{
                return 0;
            }

        }else{
            app('db')->where ('group_id', $to_group);
            app('db')->orderBy("updated_at","desc");
            $updated_chats = app('db')->getValue('group_chats', "updated_at", 1);
            if ($updated_chats) {
                return $updated_chats;
            }else{
                return 0;
            }
        }
    }

    // Get recenly shared photos with a particular user or group
    function getSharedPhotos($sender, $to_user=Null, $to_group=Null, $to_room=Null){
        if($to_user){
            if($to_user > $sender) {
                $user_1 = $sender;
                $user_2 = $to_user;
            }else{
                $user_1 = $to_user;
                $user_2 = $sender;
            }

            app('db')->where ('user_1', $user_1);
            app('db')->where ('user_2', $user_2);
            app('db')->where ('room_id', $to_room);
            app('db')->where ('type', 2);
            app('db')->orderBy("id","desc");
            $shared_image = app('db')->getValue('private_chats', "message", 12);
            if ($shared_image) {
                return $shared_image;
            }else{
                return 0;
            }

        }else{
            app('db')->where ('group_id', $to_group);
            app('db')->where ('type', 2);
            app('db')->orderBy("id","desc");
            $shared_image = app('db')->getValue('group_chats', "message", 10);
            if ($shared_image) {
                return $shared_image;
            }else{
                return 0;
            }
        }
    }

    // Get extra information for private chats
    function getChatMetaData($from_user, $to_user, $to_room){
        app('db')->where ('from_user', $from_user);
        app('db')->where ('to_user', $to_user);
        app('db')->where ('room_id', $to_room);
        $chat_meta_data = app('db')->getOne('private_chat_meta');
        if(!$chat_meta_data){
            $data = Array ("from_user" => $from_user,
                           "to_user" => $to_user,
                           "room_id" => $to_room,
                           "created_at" => app('db')->now(),
                           "updated_at" => app('db')->now(),
                        );
            $chat_meta_id = app('db')->insert('private_chat_meta', $data);
            app('db')->where('id', $chat_meta_id);
            $chat_meta_data = app('db')->getOne('private_chat_meta');
        }
        return $chat_meta_data;
    }

    // Get extra information for group chats
    function getGroupChatMetaData($from_user, $to_group){
        app('db')->where ('user', $from_user);
        app('db')->where ('chat_group', $to_group);
        $chat_meta_data = app('db')->getOne('group_users');
        if(!$chat_meta_data){
            $chat_meta_data = False;
        }
        return $chat_meta_data;
    }

    // Update extra information for private chats
    function updateChatMetaData($meta_data){
        if (array_key_exists('chat_meta_id', $meta_data)) {
            $update_data = array();
            $update_data['updated_at'] = app('db')->now();
            if (array_key_exists('last_chat_id', $meta_data)) {
                $update_data['last_chat_id'] = $meta_data['last_chat_id'];
            }

            if (array_key_exists('unread_count', $meta_data)) {
                $update_data['unread_count'] = $meta_data['unread_count'];
            }

            if (array_key_exists('is_typing', $meta_data)) {
                $update_data['is_typing'] = $meta_data['is_typing'];
            }

            if (array_key_exists('is_blocked', $meta_data)) {
                $update_data['is_blocked'] = $meta_data['is_blocked'];
            }

            if (array_key_exists('is_favourite', $meta_data)) {
                $update_data['is_favourite'] = $meta_data['is_favourite'];
            }

            if (array_key_exists('is_muted', $meta_data)) {
                $update_data['is_muted'] = $meta_data['is_muted'];
            }

            app('db')->where ('id', $meta_data['chat_meta_id']);
            app('db')->update('private_chat_meta', $update_data);
        }
    }

    // Update extra information for group chats
    function updateGroupChatMetaData($meta_data){
        if (array_key_exists('chat_meta_id', $meta_data)) {
            $update_data = array();
            $update_data['updated_at'] = app('db')->now();

            if (array_key_exists('unread_count', $meta_data)) {
                $update_data['unread_count'] = $meta_data['unread_count'];
            }

            if (array_key_exists('is_typing', $meta_data)) {
                $update_data['is_typing'] = $meta_data['is_typing'];
            }

            if (array_key_exists('is_muted', $meta_data)) {
                $update_data['is_muted'] = $meta_data['is_muted'];
            }

            app('db')->where ('id', $meta_data['chat_meta_id']);
            app('db')->update('group_users', $update_data);
        }
    }

    // Get typing stateses
    function getGroupChatTypingUsers($sender, $active_group, $active_room){
        app('db')->where('gu.chat_group', $active_group);
        app('db')->where('gu.user != ' . $sender);
        app('db')->where('gu.is_typing', 1);

        app('db')->join("users u", "u.id=gu.user", "LEFT");

        $private_usersQ = app('db')->subQuery("pc");
        $private_usersQ->where('from_user', $sender);
        $private_usersQ->where('room_id', $active_room);
        $private_usersQ->get("private_chat_meta");

        app('db')->join($private_usersQ, "pc.to_user=u.id", "LEFT");
        app('db')->orderBy("pc.is_favourite","desc");
        $group_users = app('db')->get("group_users gu", null, "gu.*, u.first_name, u.last_name, pc.is_favourite");

        return $group_users;

    }

    // Get group chat info
    function getGroupChats($group, $chat_room){
        app('db')->join("users u", "c.sender_id=u.id", "LEFT");
        app('db')->where ('c.group_id', $group);
        app('db')->where ('c.room_id', $chat_room);
        app('db')->orderBy('c.time','desc');
        $chats = app('db')->get('group_chats c', array($_SESSION['last_loaded_count'],20), 'c.*, u.first_name, u.last_name, u.avatar, "group" as chat_type');
        $chats = array_reverse($chats);
        return $chats;
    }

    // Get a conversation between two users
    function getPrivateChats($user_1, $user_2, $chat_room){
        app('db')->join("users u", "c.sender_id=u.id", "LEFT");
        app('db')->where ('c.user_1', $user_1);
        app('db')->where ('c.user_2', $user_2);
        app('db')->where ('c.room_id', $chat_room);
        app('db')->orderBy('c.time','desc');
        $chats = app('db')->get('private_chats c', array($_SESSION['last_loaded_count'],20), 'c.*, u.first_name, u.last_name, u.avatar, "private" as chat_type');
        $chats = array_reverse($chats);
        return $chats;
    }



}


?>
