-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2020 at 12:43 PM
-- Server version: 10.1.28-MariaDB
-- PHP Version: 7.1.11

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chatnet`
--

-- --------------------------------------------------------

--
-- Table structure for table `cn_chat_groups`
--

CREATE TABLE `cn_chat_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `chat_room` int(11) NOT NULL,
  `cover_image` varchar(200) DEFAULT NULL,
  `is_protected` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True = 1, False = 0',
  `password` varchar(300) DEFAULT NULL,
  `slug` varchar(20) NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT 'ACTIVE = 1, INACTIVE = 2',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cn_chat_rooms`
--

CREATE TABLE `cn_chat_rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `cover_image` varchar(200) DEFAULT NULL,
  `is_protected` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True = 1, False = 0',
  `password` varchar(300) DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'True = 1, False = 0',
  `chat_validity` int(11) DEFAULT NULL COMMENT 'hours',
  `slug` varchar(20) NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT 'ACTIVE = 1, INACTIVE = 2'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cn_group_chats`
--

CREATE TABLE `cn_group_chats` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT '1' COMMENT 'text= 1, image= 2, gif= 3',
  `message` text DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT 'send= 1, seen = 2, deleted = 3',
  `time` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cn_group_users`
--

CREATE TABLE `cn_group_users` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `chat_group` int(11) NOT NULL,
  `user_type` smallint(6) NOT NULL DEFAULT '2' COMMENT 'Group admin = 1, Group user = 2',
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT 'Active = 1, Inactive = 2',
  `is_typing` tinyint(1) NOT NULL DEFAULT '0',
  `is_muted` tinyint(4) NOT NULL DEFAULT '0',
  `unread_count` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cn_private_chats`
--

CREATE TABLE `cn_private_chats` (
  `id` int(11) NOT NULL,
  `user_1` int(11) NOT NULL,
  `user_2` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT '1' COMMENT 'text=1, image=2, gif=3',
  `message` text DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT 'send= 1, seen = 2, deleted = 3',
  `time` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cn_private_chat_meta`
--

CREATE TABLE `cn_private_chat_meta` (
  `id` int(11) NOT NULL,
  `from_user` int(11) NOT NULL,
  `to_user` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `is_typing` tinyint(1) DEFAULT '0',
  `is_blocked` tinyint(1) DEFAULT '0',
  `is_favourite` tinyint(1) DEFAULT '0',
  `is_muted` tinyint(4) DEFAULT '0',
  `unread_count` int(11) DEFAULT '0',
  `last_chat_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cn_settings`
--

CREATE TABLE `cn_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `value` text CHARACTER SET latin1 DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `cn_settings`
--

INSERT INTO `cn_settings` (`id`, `name`, `value`) VALUES
(1, 'timezone', 'Asia/Colombo'),
(2, 'chat_receive_seconds', '3'),
(3, 'user_list_check_seconds', '5'),
(4, 'chat_status_check_seconds', '3'),
(5, 'online_status_check_seconds', '10'),
(6, 'typing_status_check_seconds', '3');

-- --------------------------------------------------------

--
-- Table structure for table `cn_users`
--

CREATE TABLE `cn_users` (
  `id` int(11) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `sex` smallint(6) DEFAULT NULL COMMENT 'MALE = 1, FEMALE = 2, OTHER = 3',
  `avatar` varchar(200) DEFAULT NULL,
  `password` varchar(300) NOT NULL,
  `about` varchar(500) DEFAULT NULL,
  `user_status` smallint(6) DEFAULT '1' COMMENT 'ONLINE = 1, OFFLINE = 2, BUSY = 3',
  `available_status` smallint(6) DEFAULT '1' COMMENT 'ACTIVE = 1, INACTIVE = 2',
  `last_seen` timestamp NULL DEFAULT NULL,
  `user_type` smallint(6) NOT NULL DEFAULT '2' COMMENT 'Admin = 1, Chat User = 2',
  `reset_key` varchar(32) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `timezone` varchar(100) DEFAULT 'Asia/Colombo'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cn_chat_groups`
--
ALTER TABLE `cn_chat_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_chat_rooms`
--
ALTER TABLE `cn_chat_rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_group_chats`
--
ALTER TABLE `cn_group_chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_group_users`
--
ALTER TABLE `cn_group_users`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `cn_private_chats`
--
ALTER TABLE `cn_private_chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_private_chat_meta`
--
ALTER TABLE `cn_private_chat_meta`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_settings`
--
ALTER TABLE `cn_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_users`
--
ALTER TABLE `cn_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`,`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cn_chat_groups`
--
ALTER TABLE `cn_chat_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_chat_rooms`
--
ALTER TABLE `cn_chat_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_group_chats`
--
ALTER TABLE `cn_group_chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_group_users`
--
ALTER TABLE `cn_group_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_private_chats`
--
ALTER TABLE `cn_private_chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_private_chat_meta`
--
ALTER TABLE `cn_private_chat_meta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_settings`
--
ALTER TABLE `cn_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_users`
--
ALTER TABLE `cn_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
