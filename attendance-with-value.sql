-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Sep 06, 2025 at 02:24 PM
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
-- Database: `attendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_tracking`
--

CREATE TABLE `attendance_tracking` (
  `attendance_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `lrn` bigint(20) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_status` enum('Present','Absent','Late') DEFAULT NULL,
  `reason` enum('Health Issue','Household Income','Transportation','Family Structure','No Reason','Other') DEFAULT NULL,
  `time_checked` datetime DEFAULT NULL,
  `is_qr_scanned` tinyint(1) DEFAULT 0,
  `logged_by` enum('Teacher','QR') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_tracking`
--

INSERT INTO `attendance_tracking` (`attendance_id`, `class_id`, `lrn`, `attendance_date`, `attendance_status`, `reason`, `time_checked`, `is_qr_scanned`, `logged_by`) VALUES
(241, 1, 127463980521, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(242, 1, 182563283391, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(243, 1, 196853737107, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(244, 1, 219847365098, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(245, 1, 304958671230, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(246, 1, 309878613646, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(247, 1, 378209145376, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(248, 1, 386517204963, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(249, 1, 418515383035, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(250, 1, 548790321684, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(251, 1, 592841076213, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(252, 1, 593201847265, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(253, 1, 615972384720, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(254, 1, 641902738459, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(255, 1, 704193826574, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(256, 1, 745976379835, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(257, 1, 850174932610, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(258, 1, 872639105387, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(259, 1, 901753824519, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(260, 1, 904728163059, '2025-08-26', 'Present', NULL, '2025-08-26 23:56:05', 0, 'Teacher'),
(261, 1, 615972384720, '2025-08-31', 'Present', NULL, '2025-08-31 02:53:44', 0, 'Teacher'),
(262, 1, 641902738459, '2025-08-31', 'Present', NULL, '2025-08-31 05:17:48', 1, 'QR'),
(263, 1, 704193826574, '2025-08-31', 'Present', NULL, '2025-08-31 05:19:43', 1, 'QR'),
(264, 1, 615972384720, '2025-09-02', 'Present', NULL, '2025-09-02 14:50:29', 1, 'QR'),
(271, 1, 641902738459, '2025-09-03', 'Present', NULL, '2025-09-03 18:39:49', 1, 'QR'),
(272, 1, 704193826574, '2025-09-03', 'Present', NULL, '2025-09-03 18:40:18', 1, 'QR'),
(273, 1, 378209145376, '2025-09-03', 'Late', 'Transportation', '2025-09-03 18:53:03', 0, 'Teacher'),
(276, 1, 704193826574, '2025-09-05', 'Present', NULL, '2025-09-05 11:58:51', 1, 'QR'),
(277, 1, 548790321684, '2025-09-05', 'Present', NULL, '2025-09-05 13:38:58', 1, 'QR');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `attendance_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `section_name`, `subject_id`, `teacher_id`, `grade_level`, `room`, `attendance_percentage`, `status`, `created_at`) VALUES
(1, 'Lennox', 1, 1, 'Grade 11', '', 0.00, 'active', '2025-08-11 02:33:57'),
(2, 'Dewey', 1, 1, 'Grade 11', '', 0.00, 'active', '2025-08-11 02:34:17'),
(13, 'Galileo', 1, 1, 'Grade 11', '', 0.00, 'active', '2025-08-26 15:52:51');

-- --------------------------------------------------------

--
-- Table structure for table `class_students`
--

CREATE TABLE `class_students` (
  `class_id` int(11) NOT NULL,
  `lrn` bigint(20) NOT NULL,
  `is_enrolled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_students`
--

INSERT INTO `class_students` (`class_id`, `lrn`, `is_enrolled`, `created_at`) VALUES
(1, 127463980521, 1, '2025-08-30 20:35:41'),
(1, 182563283391, 1, '2025-08-30 20:35:41'),
(1, 196853737107, 1, '2025-08-30 20:35:41'),
(1, 219847365098, 1, '2025-08-30 20:35:41'),
(1, 304958671230, 1, '2025-08-30 20:35:41'),
(1, 309878613646, 1, '2025-08-30 20:35:41'),
(1, 378209145376, 1, '2025-08-30 20:35:41'),
(1, 386517204963, 1, '2025-08-30 20:35:41'),
(1, 418515383035, 1, '2025-08-30 20:35:41'),
(1, 548790321684, 1, '2025-08-30 20:35:41'),
(1, 592841076213, 1, '2025-08-30 20:35:41'),
(1, 593201847265, 1, '2025-08-30 20:35:41'),
(1, 615972384720, 1, '2025-08-30 20:35:41'),
(1, 641902738459, 1, '2025-08-30 20:35:41'),
(1, 704193826574, 1, '2025-08-30 20:35:41'),
(1, 745976379835, 1, '2025-08-30 20:35:41'),
(1, 850174932610, 1, '2025-08-30 20:35:41'),
(1, 872639105387, 1, '2025-08-30 20:35:41'),
(1, 901753824519, 1, '2025-08-30 20:35:41'),
(1, 904728163059, 1, '2025-08-30 20:35:41');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `day` enum('monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `lrn` bigint(20) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_email` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT 'no-icon.png',
  `qr_code` varchar(255) DEFAULT NULL,
  `date_added` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`lrn`, `last_name`, `first_name`, `middle_name`, `email`, `gender`, `dob`, `grade_level`, `address`, `parent_name`, `parent_email`, `emergency_contact`, `photo`, `qr_code`, `date_added`) VALUES
(111732090008, 'Castro', 'Loraine', 'Otora', 'castro.loraine.26@gmail.com', 'Female', '2025-09-02', 'Grade 11', 'Gumamela St Block 17 Lot 11 Green Estate Tiaong Guiguinto Bulacan', 'Manuel Casto', '', '09123456789', 'no-icon.png', '111732090008.png', '2025-09-02'),
(123456789043, 'Castro', 'Loraine', 'Otora', 'castro.loraine.26@gmail.com', 'Female', '2025-09-04', 'Grade 11', 'Gumamela St Block 17 Lot 11 Green Estate Tiaong Guiguinto Bulacan', 'Manuel Casto', 'castro.loraine.26@gmail.com', '09123456789', 'no-icon.png', '123456789043.png', '2025-09-04'),
(127463980521, 'Lee', 'Michael', 'Hernandez', 'michael.lee@email.com', 'Male', '2004-11-10', 'Grade 11', '789 Pine Rd', 'Susan Lee', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto11.png', '127463980521.png', '2025-08-21'),
(182563283391, 'Martinez', 'William', 'Lee', 'william.martinez@email.com', 'Male', '2006-03-22', 'Grade 11', '890 Example St', 'John Martinez', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto13.png', '182563283391.png', '2025-08-21'),
(196853737107, 'Miller', 'Liam', 'Marie', 'liam.miller@email.com', 'Male', '2005-04-22', 'Grade 11', '567 Example St', 'Jane Miller', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto14.png', '196853737107.png', '2025-08-21'),
(219847365098, 'Taylor', 'William', 'Parker', 'william.taylor@email.com', 'Male', '2005-12-18', 'Grade 11', '147 Maple Ave', 'Emily Taylor', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto18.png', '219847365098.png', '2025-08-21'),
(304958671230, 'Smith', 'Emma', 'Grace', 'emma.smith@email.com', 'Female', '2005-10-19', 'Grade 11', '823 Example St', 'Lisa Smith', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto15.png', '304958671230.png', '2025-08-21'),
(309878613646, 'Johnson', 'Olivia', 'Ann', 'olivia.johnson@email.com', 'Male', '2005-06-16', 'Grade 11', '187 Example St', 'John Johnson', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto10.png', '309878613646.png', '2025-08-21'),
(378209145376, 'Chen', 'Sophia', 'Lopez', 'sophia.chen@email.com', 'Female', '2005-09-05', 'Grade 11', '321 Elm St', 'David Chen', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto3.png', '378209145376.png', '2025-08-21'),
(386517204963, 'Martinez', 'Ethan', 'Ramirez', 'ethan.martinez@email.com', 'Male', '2004-08-09', 'Grade 11', '369 Willow Rd', 'Maria Martinez', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto12.png', '386517204963.png', '2025-08-21'),
(418515383035, 'Garcia', 'Benjamin', 'John', 'benjamin.garcia@email.com', 'Female', '2005-12-02', 'Grade 11', '130 Example St', 'David Garcia', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto6.png', '418515383035.png', '2025-08-21'),
(548790321684, 'Davis', 'Liam', 'James', 'liam.davis@email.com', 'Male', '2006-01-12', 'Grade 11', '412 Example St', 'Lisa Davis', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto4.png', '548790321684.png', '2025-08-21'),
(592841076213, 'Garcia', 'Isabella', 'Reyes', 'isabella.garcia@email.com', 'Female', '2005-06-17', 'Grade 11', '741 Ash St', 'Carlos Garcia', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto7.png', '592841076213.png', '2025-08-21'),
(593201847265, 'Doe', 'John', 'Anderson', 'john.doe@email.com', 'Male', '2005-03-15', 'Grade 11', '123 Main St', 'Jane Doe', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto5.png', '593201847265.png', '2025-08-21'),
(615972384720, 'Brown', 'Ava', 'Lou', 'ava.brown@email.com', 'Female', '2006-02-27', 'Grade 11', '278 Example St', 'David Brown', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto1.png', '615972384720.png', '2025-08-21'),
(641902738459, 'Brown', 'James', 'Clark', 'james.brown@email.com', 'Male', '2006-01-30', 'Grade 11', '654 Birch Ln', 'Lisa Brown', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto2.png', '641902738459.png', '2025-08-21'),
(704193826574, 'Johnson', 'Ava', 'Nguyen', 'ava.johnson@email.com', 'Female', '2006-04-25', 'Grade 11', '258 Spruce Ct', 'Robert Johnson', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto8.png', '704193826574.png', '2025-08-21'),
(745976379835, 'Johnson', 'Noah', 'John', 'noah.johnson@email.com', 'Female', '2005-11-24', 'Grade 11', '634 Example St', 'John Johnson', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto9.png', '745976379835.png', '2025-08-21'),
(850174932610, 'Wilson', 'Olivia', 'Gomez	', 'olivia.wilson@email.com', 'Female', '2004-05-12', 'Grade 11', '987 Cedar Dr', 'Mike Wilson', 'castro.loraine.26@gmail.com', '09123456789', NULL, '850174932610.png', '2025-08-21'),
(872639105387, 'Williams', 'Benjamin', 'Lee', 'benjamin.williams@email.com', 'Male', '2005-09-03', 'Grade 11', '764 Example St', 'John Williams', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto19.png', '872639105387.png', '2025-08-21'),
(901753824519, 'Smith', 'Olivia', 'Ray', 'olivia.smith@email.com', 'Female', '2005-08-25', 'Grade 11', '345 Example St', 'Jane Smith', 'castro.loraine.26@gmail.com', '09123456789', NULL, '901753824519.png', '2025-08-21'),
(904728163059, 'Smith', 'Emma', 'Robinson', 'emma.smith@email.com', 'Female', '2006-07-22', 'Grade 11', '456 Oak Ave', 'Tom Smith', 'castro.loraine.26@gmail.com', '09123456789', 'profilePhoto16.png', '904728163059.png', '2025-08-21');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_code`, `subject_name`, `created_at`) VALUES
(1, 'ICT-102', 'ICT', '2025-08-11 02:33:57'),
(2, 'SCI-101', 'SCI', '2025-08-11 02:35:14');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `picture` varchar(255) DEFAULT 'no-icon.png',
  `isActive` tinyint(1) DEFAULT 0,
  `isVerified` tinyint(1) DEFAULT 0,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_purpose` varchar(50) DEFAULT NULL,
  `otp_created_at` datetime DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `otp_is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `firstname`, `lastname`, `institution`, `email`, `username`, `password`, `picture`, `isActive`, `isVerified`, `otp_code`, `otp_purpose`, `otp_created_at`, `otp_expires_at`, `otp_is_used`, `created_at`) VALUES
(1, 'Loraine', 'Castro', 'San Rafael National Trade School', 'castro.loraine.26@gmail.com', '_lorainecastro', '$2y$10$AK3ZL2g3VEEPpsP8k0ZK3elYl6BzAh3sYAUxeiFvaHW6yXOKucgiO', 'lorainecastro.png', 1, 1, '119039', 'EMAIL_VERIFICATION', '2025-09-05 11:21:31', '2025-09-05 03:36:31', 0, '2025-09-06 14:21:58'),
(2, 'Loraine', 'Castro', 'SRNTS', 'elci.bank@gmail.com', 'lorainecastro', '$2y$10$AK3ZL2g3VEEPpsP8k0ZK3elYl6BzAh3sYAUxeiFvaHW6yXOKucgiO', 'lorainecastro.png', 1, 1, NULL, 'EMAIL_VERIFICATION', '2025-09-05 14:47:10', NULL, 1, '2025-09-04 13:14:02');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_sessions`
--

CREATE TABLE `teacher_sessions` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_sessions`
--

INSERT INTO `teacher_sessions` (`id`, `teacher_id`, `session_token`, `expires_at`, `created_at`) VALUES
(133, 1, '1c12bd5b0c007f63f2f4273e0332001b80af1479fa0d1040de848196d47d9283', '2025-10-06 14:21:58', '2025-09-06 22:21:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_tracking`
--
ALTER TABLE `attendance_tracking`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `lrn` (`lrn`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_students`
--
ALTER TABLE `class_students`
  ADD PRIMARY KEY (`class_id`,`lrn`),
  ADD KEY `lrn` (`lrn`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`lrn`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD UNIQUE KEY `subject_name` (`subject_name`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_tracking`
--
ALTER TABLE `attendance_tracking`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_tracking`
--
ALTER TABLE `attendance_tracking`
  ADD CONSTRAINT `attendance_tracking_ibfk_1` FOREIGN KEY (`lrn`) REFERENCES `students` (`lrn`),
  ADD CONSTRAINT `attendance_tracking_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `class_students`
--
ALTER TABLE `class_students`
  ADD CONSTRAINT `class_students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `class_students_ibfk_2` FOREIGN KEY (`lrn`) REFERENCES `students` (`lrn`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  ADD CONSTRAINT `teacher_sessions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
