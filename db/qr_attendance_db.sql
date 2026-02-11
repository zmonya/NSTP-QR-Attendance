-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 11, 2026 at 02:28 AM
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
-- Table structure for table `tbl_attendance`
--

CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(1, 2, 1, '2024-03-13 00:45:37', '2026-02-10 00:23:44');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student`
--

CREATE TABLE `tbl_student` (
  `tbl_student_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course_section` varchar(255) NOT NULL,
  `generated_code` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_student`
--

INSERT INTO `tbl_student` (`tbl_student_id`, `student_name`, `course_section`, `generated_code`, `created_by`) VALUES
(1, 'Samantha', 'BSIS 4B', 'KIYkAk6ZRV', 1),
(2, 'caleb', 'bsit 4E', 'GofyhgePOc', 1),
(3, 'jamo', 'bsit 4A', 'lg7Q8Xp49j', 1),
(4, 'andre', 'bsit 4B', 'XNHwxXbA9w', 1),
(5, 'steven', 'bsit 4C', 'j4LyGT0mYV', 3);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', '2026-02-09 08:30:37', '2026-02-10 02:29:22', NULL),
(2, 'CWTS', 'client2@gmail.com', '$2y$10$Wt.4NYHkw3yoU0qRjBZXEO6FfKEmSeOBf6q7k5kqcwBRSXrz4mbnW', 'CWTS', 'admin', '2026-02-10 02:21:10', '2026-02-11 01:20:30', NULL),
(4, 'LTS', 'client1@gmail.com', '$2y$10$XI.L.FvumL9iS2iyAwhdTOeEfPG1UuJOmDoA3R8yFnk1uqEYJA9qi', 'LTS', 'admin', '2026-02-11 01:15:04', '2026-02-11 01:15:40', 1),
(5, 'ROTC', 'client3@gmail.com', '$2y$10$Bie4mf9QUFmo1p5cTxOmq.LIo7E2Y1c7FXyXlatFMaBy46JaXhrSO', 'ROTC', 'admin', '2026-02-11 01:21:07', '2026-02-11 01:21:07', 1);

--
-- Indexes for dumped tables
--

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
  ADD KEY `idx_created_by` (`created_by`);

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
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_attendance_archive`
--
ALTER TABLE `tbl_attendance_archive`
  MODIFY `tbl_attendance_archive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
