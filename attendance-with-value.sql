-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Aug 11, 2025 at 10:56 AM
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
  `logged_by` enum('Teacher','QR') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 'Dewey', 1, 1, 'Grade 11', '', 0.00, 'active', '2025-08-11 02:34:17');

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
(1, 127463980521, 1, '2025-08-26 15:31:53'),
(1, 182563283391, 1, '2025-08-26 15:31:53'),
(1, 196853737107, 1, '2025-08-26 15:31:53'),
(1, 219847365098, 1, '2025-08-26 15:31:53'),
(1, 304958671230, 1, '2025-08-26 15:31:53'),
(1, 309878613646, 1, '2025-08-26 15:31:53'),
(1, 378209145376, 1, '2025-08-26 15:31:53'),
(1, 386517204963, 1, '2025-08-26 15:31:53'),
(1, 418515383035, 1, '2025-08-26 15:31:53'),
(1, 548790321684, 1, '2025-08-26 15:31:53'),
(1, 592841076213, 1, '2025-08-26 15:31:53'),
(1, 593201847265, 1, '2025-08-26 15:31:53'),
(1, 615972384720, 1, '2025-08-26 15:31:53'),
(1, 641902738459, 1, '2025-08-26 15:31:53'),
(1, 704193826574, 1, '2025-08-26 15:31:53'),
(1, 745976379835, 1, '2025-08-26 15:31:53'),
(1, 850174932610, 1, '2025-08-26 15:31:53'),
(1, 872639105387, 1, '2025-08-26 15:31:53'),
(1, 901753824519, 1, '2025-08-26 15:31:53'),
(1, 904728163059, 1, '2025-08-26 15:31:53');

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
  `emergency_contact` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT 'no-icon.png',
  `qr_code` varchar(255) DEFAULT NULL,
  `date_added` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`lrn`, `last_name`, `first_name`, `middle_name`, `email`, `gender`, `dob`, `grade_level`, `address`, `parent_name`, `emergency_contact`, `photo`, `qr_code`, `date_added`) VALUES
(127463980521, 'Lee', 'Michael', 'Hernandez', 'michael.lee@email.com', 'Male', '2004-11-10', 'Grade 11', '789 Pine Rd', 'Susan Lee', '09123456789', 'profilePhoto4.png', '127463980521.png', '2025-08-21'),
(182563283391, 'Martinez', 'William', 'Lee', 'william.martinez@email.com', 'Male', '2006-03-22', 'Grade 11', '890 Example St', 'John Martinez', '09123456789', 'no-icon.png', '182563283391.png', '2025-08-21'),
(196853737107, 'Miller', 'Liam', 'Marie', 'liam.miller@email.com', 'Male', '2005-04-22', 'Grade 11', '567 Example St', 'Jane Miller', '09123456789', 'no-icon.png', '196853737107.png', '2025-08-21'),
(219847365098, 'Taylor', 'William', 'Parker', 'william.taylor@email.com', 'Male', '2005-12-18', 'Grade 11', '147 Maple Ave', 'Emily Taylor', '09123456789', 'profilePhoto16.png', '219847365098.png', '2025-08-21'),
(304958671230, 'Smith', 'Emma', 'Grace', 'emma.smith@email.com', 'Female', '2005-10-19', 'Grade 11', '823 Example St', 'Lisa Smith', '09123456789', 'no-icon.png', '304958671230.png', '2025-08-21'),
(309878613646, 'Johnson', 'Olivia', 'Ann', 'olivia.johnson@email.com', 'Male', '2005-06-16', 'Grade 11', '187 Example St', 'John Johnson', '09123456789', 'no-icon.png', '309878613646.png', '2025-08-21'),
(378209145376, 'Chen', 'Sophia', 'Lopez', 'sophia.chen@email.com', 'Female', '2005-09-05', 'Grade 11', '321 Elm St', 'David Chen', '09123456789', 'profilePhoto21.png', '378209145376.png', '2025-08-21'),
(386517204963, 'Martinez', 'Ethan', 'Ramirez', 'ethan.martinez@email.com', 'Male', '2004-08-09', 'Grade 11', '369 Willow Rd', 'Maria Martinez', '09123456789', 'profilePhoto12.png', '386517204963.png', '2025-08-21'),
(418515383035, 'Garcia', 'Benjamin', 'John', 'benjamin.garcia@email.com', 'Female', '2005-12-02', 'Grade 11', '130 Example St', 'David Garcia', '09123456789', 'no-icon.png', '418515383035.png', '2025-08-21'),
(548790321684, 'Davis', 'Liam', 'James', 'liam.davis@email.com', 'Male', '2006-01-12', 'Grade 11', '412 Example St', 'Lisa Davis', '09123456789', 'no-icon.png', '548790321684.png', '2025-08-21'),
(592841076213, 'Garcia', 'Isabella', 'Reyes', 'isabella.garcia@email.com', 'Female', '2005-06-17', 'Grade 11', '741 Ash St', 'Carlos Garcia', '09123456789', 'profilePhoto18.png', '592841076213.png', '2025-08-21'),
(593201847265, 'Doe', 'John', 'Anderson', 'john.doe@email.com', 'Male', '2005-03-15', 'Grade 11', '123 Main St', 'Jane Doe', '09123456789', 'profilePhoto2.png', '593201847265.png', '2025-08-21'),
(615972384720, 'Brown', 'Ava', 'Lou', 'ava.brown@email.com', 'Female', '2006-02-27', 'Grade 11', '278 Example St', 'David Brown', '09123456789', 'no-icon.png', '615972384720.png', '2025-08-21'),
(641902738459, 'Brown', 'James', 'Clark', 'james.brown@email.com', 'Male', '2006-01-30', 'Grade 11', '654 Birch Ln', 'Lisa Brown', '09123456789', 'profilePhoto20.png', '641902738459.png', '2025-08-21'),
(704193826574, 'Johnson', 'Ava', 'Nguyen', 'ava.johnson@email.com', 'Female', '2006-04-25', 'Grade 11', '258 Spruce Ct', 'Robert Johnson', '09123456789', 'profilePhoto3.png', '704193826574.png', '2025-08-21'),
(745976379835, 'Johnson', 'Noah', 'John', 'noah.johnson@email.com', 'Female', '2005-11-24', 'Grade 11', '634 Example St', 'John Johnson', '09123456789', 'no-icon.png', '745976379835.png', '2025-08-21'),
(850174932610, 'Wilson', 'Olivia', 'Gomez	', 'olivia.wilson@email.com', 'Female', '2004-05-12', 'Grade 11', '987 Cedar Dr', 'Mike Wilson', '09123456789', 'profilePhoto19.png', '850174932610.png', '2025-08-21'),
(872639105387, 'Williams', 'Benjamin', 'Lee', 'benjamin.williams@email.com', 'Male', '2005-09-03', 'Grade 11', '764 Example St', 'John Williams', '09123456789', 'no-icon.png', '872639105387.png', '2025-08-21'),
(901753824519, 'Smith', 'Olivia', 'Ray', 'olivia.smith@email.com', 'Female', '2005-08-25', 'Grade 11', '345 Example St', 'Jane Smith', '09123456789', 'no-icon.png', '901753824519.png', '2025-08-21'),
(904728163059, 'Smith', 'Emma', 'Robinson', 'emma.smith@email.com', 'Female', '2006-07-22', 'Grade 11', '456 Oak Ave', 'Tom Smith', '09123456789', 'profilePhoto1.png', '904728163059.png', '2025-08-21');

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
(1, 'ICT-101', 'ICT', '2025-08-11 02:33:57'),
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
(1, 'Loraine', 'Castro', 'San Rafael National Trade School', 'castro.loraine.26@gmail.com', '_lorainecastro', '$2y$10$AK3ZL2g3VEEPpsP8k0ZK3elYl6BzAh3sYAUxeiFvaHW6yXOKucgiO', 'profile_1_1755526394.png', 1, 1, NULL, 'EMAIL_VERIFICATION', '2025-08-06 13:38:16', NULL, 1, '2025-08-26 15:18:35'),
(2, 'Loraine', 'Castro', 'SRNTS', 'elci.bank@gmail.com', 'lorainecastro', '$2y$10$AK3ZL2g3VEEPpsP8k0ZK3elYl6BzAh3sYAUxeiFvaHW6yXOKucgiO', 'lorainecastro.png', 1, 1, NULL, 'EMAIL_VERIFICATION', '2025-08-06 13:38:16', NULL, 1, '2025-08-21 15:22:32'),
(3, 'Leiumar', 'Sayco', 'San Rafael National Trade School', 'leiumarsayco95@gmail.com', 'leiumarsayco', '$2y$10$K8fScHTN5mIEn0hh5Zf0o.6kSE.LK28yRCnuqJ0WUfDkSV8uo2rl.', 'no-icon.png', 1, 1, NULL, 'EMAIL_VERIFICATION', '2025-08-22 08:56:40', NULL, 1, '2025-08-23 15:26:51');

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
(76, 1, 'f9ea63d305f4c4748ad88b08947abf9633668c32f36e3745d212564e48212d20', '2025-09-25 15:18:35', '2025-08-26 23:18:35');

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
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

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
