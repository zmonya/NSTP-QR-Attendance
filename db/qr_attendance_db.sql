-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 07:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qr_attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin_sections`
--

CREATE TABLE `tbl_admin_sections` (
  `admin_section_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_section` varchar(255) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_admin_sections`
--

INSERT INTO `tbl_admin_sections` (`admin_section_id`, `user_id`, `course_section`, `assigned_by`, `assigned_at`) VALUES
(5, 2, 'CWTS 1A', 1, '2026-02-11 08:01:05'),
(6, 4, 'LTS 1A', 1, '2026-02-11 08:01:36'),
(7, 5, 'Alpha 1st', 1, '2026-02-11 08:02:02');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_attendance`
--

CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'On Time'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_attendance_archive`
--

CREATE TABLE `tbl_attendance_archive` (
  `tbl_attendance_archive_id` int(11) NOT NULL,
  `tbl_attendance_id` int(11) NOT NULL,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_attendance_archive`
--

INSERT INTO `tbl_attendance_archive` (`tbl_attendance_archive_id`, `tbl_attendance_id`, `tbl_student_id`, `time_in`, `archived_date`) VALUES
(1, 2, 1, '2024-03-13 00:45:37', '2026-02-10 00:23:44'),
(2, 4, 1, '2026-02-11 03:11:00', '2026-02-11 03:16:25'),
(3, 5, 4, '2026-02-11 03:11:00', '2026-02-11 03:16:25'),
(4, 6, 2, '2026-02-11 03:12:00', '2026-02-11 03:16:25'),
(5, 8, 5, '2026-02-10 23:44:00', '2026-02-12 01:08:13'),
(6, 7, 1, '2026-02-11 05:36:00', '2026-02-12 01:08:13');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student`
--

CREATE TABLE `tbl_student` (
  `tbl_student_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course_section` varchar(255) NOT NULL,
  `generated_code` varchar(255) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_student`
--

INSERT INTO `tbl_student` (`tbl_student_id`, `student_name`, `course_section`, `generated_code`, `qr_code`, `created_by`) VALUES
(1, 'Samantha', 'BSIS 4B', 'KIYkAk6ZRV', 'KIYkAk6ZRV', 1),
(9, 'Wencel', 'LTS 1A', 'L7QUD6BAP0', NULL, 4),
(10, 'Jonelle', 'LTS 1A', '7K6QYOPVFZ', NULL, 4),
(12, 'Janel', 'LTS 1A', '03OBwb7Xij', NULL, 4),
(15, 'caleb', 'CWTS 1A', 'o0yfErWuQ0', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `assigned_section` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_password_change` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `assigned_section`, `profile_picture`, `last_password_change`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', NULL, NULL, NULL, '2026-02-09 08:30:37', '2026-02-10 02:29:22', NULL),
(2, 'CWTS', 'client2@gmail.com', '$2y$10$Wt.4NYHkw3yoU0qRjBZXEO6FfKEmSeOBf6q7k5kqcwBRSXrz4mbnW', 'CWTS', 'admin', 'CWTS 1A', 'uploads/profile_pictures/profile_2_1770874919.png', NULL, '2026-02-10 02:21:10', '2026-02-12 05:41:59', NULL),
(4, 'LTS', 'client1@gmail.com', '$2y$10$XI.L.FvumL9iS2iyAwhdTOeEfPG1UuJOmDoA3R8yFnk1uqEYJA9qi', 'LTS', 'admin', 'LTS 1A', NULL, NULL, '2026-02-11 01:15:04', '2026-02-11 08:01:43', 1),
(5, 'ROTC', 'client3@gmail.com', '$2y$10$Bie4mf9QUFmo1p5cTxOmq.LIo7E2Y1c7FXyXlatFMaBy46JaXhrSO', 'ROTC', 'admin', 'Alpha 1st', NULL, NULL, '2026-02-11 01:21:07', '2026-02-11 08:02:07', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_admin_sections`
--
ALTER TABLE `tbl_admin_sections`
  ADD PRIMARY KEY (`admin_section_id`),
  ADD UNIQUE KEY `unique_admin_section` (`user_id`,`course_section`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_course_section` (`course_section`);

--
-- Indexes for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD PRIMARY KEY (`tbl_attendance_id`);

--
-- Indexes for table `tbl_attendance_archive`
--
ALTER TABLE `tbl_attendance_archive`
  ADD PRIMARY KEY (`tbl_attendance_archive_id`),
  ADD KEY `idx_student_id` (`tbl_student_id`),
  ADD KEY `idx_time_in` (`time_in`),
  ADD KEY `idx_archived_date` (`archived_date`);

--
-- Indexes for table `tbl_student`
--
ALTER TABLE `tbl_student`
  ADD PRIMARY KEY (`tbl_student_id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_student_created_by` (`created_by`),
  ADD KEY `idx_student_course_section` (`course_section`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_admin_sections`
--
ALTER TABLE `tbl_admin_sections`
  MODIFY `admin_section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_attendance_archive`
--
ALTER TABLE `tbl_attendance_archive`
  MODIFY `tbl_attendance_archive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_student`
--
ALTER TABLE `tbl_student`
  ADD CONSTRAINT `fk_student_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
