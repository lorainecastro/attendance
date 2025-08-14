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
(1, 'Lennox', 1, 1, 'Grade 11', '', 0.00, 'active', '2025-08-11 10:33:57'),
(2, 'Galileo', 1, 1, 'Grade 11', '', 0.00, 'active', '2025-08-11 10:34:17'),
(3, 'Lennox', 3, 1, 'Grade 10', '', 0.00, 'active', '2025-08-11 10:35:14');

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
(1, 127463980521, 1, '2025-08-11 10:38:10'),
(1, 219847365098, 1, '2025-08-11 10:38:10'),
(1, 378209145376, 1, '2025-08-11 10:38:10'),
(1, 386517204963, 1, '2025-08-11 10:38:10'),
(1, 592841076213, 1, '2025-08-11 10:38:10'),
(1, 593201847265, 1, '2025-08-11 10:38:10'),
(1, 641902738459, 1, '2025-08-11 10:38:10'),
(1, 704193826574, 1, '2025-08-11 10:38:10'),
(1, 850174932610, 1, '2025-08-11 10:38:10'),
(1, 904728163059, 1, '2025-08-11 10:38:10');

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
(127463980521, 'Lee', 'Michael', 'Hernandez', 'michael.lee@email.com', 'Male', '2004-11-10', 'Grade 11', '789 Pine Rd', 'Susan Lee', '09123456789', 'profilePhoto4.png', NULL, '2025-08-11'),
(219847365098, 'Taylor', 'William', 'Parker', 'william.taylor@email.com', 'Male', '2005-12-18', 'Grade 11', '147 Maple Ave', 'Emily Taylor', '09123456789', 'profilePhoto16.png', NULL, '2025-08-11'),
(378209145376, 'Chen', 'Sophia', 'Lopez', 'sophia.chen@email.com', 'Female', '2005-09-05', 'Grade 11', '321 Elm St', 'David Chen', '09123456789', 'profilePhoto21.png', NULL, '2025-08-11'),
(386517204963, 'Martinez', 'Ethan', 'Ramirez', 'ethan.martinez@email.com', 'Male', '2004-08-09', 'Grade 11', '369 Willow Rd', 'Maria Martinez', '09123456789', 'profilePhoto12.png', NULL, '2025-08-11'),
(592841076213, 'Garcia', 'Isabella', 'Reyes', 'isabella.garcia@email.com', 'Female', '2005-06-17', 'Grade 11', '741 Ash St', 'Carlos Garcia', '09123456789', 'profilePhoto18.png', NULL, '2025-08-11'),
(593201847265, 'Doe', 'John', 'Anderson', 'john.doe@email.com', 'Male', '2005-03-15', 'Grade 11', '123 Main St', 'Jane Doe', '09123456789', 'profilePhoto2.png', NULL, '2025-08-11'),
(641902738459, 'Brown', 'James', 'Clark', 'james.brown@email.com', 'Male', '2006-01-30', 'Grade 11', '654 Birch Ln', 'Lisa Brown', '09123456789', 'profilePhoto20.png', NULL, '2025-08-11'),
(704193826574, 'Johnson', 'Ava', 'Nguyen', 'ava.johnson@email.com', 'Female', '2006-04-25', 'Grade 11', '258 Spruce Ct', 'Robert Johnson', '09123456789', 'profilePhoto3.png', NULL, '2025-08-11'),
(850174932610, 'Wilson', 'Olivia', 'Gomez	', 'olivia.wilson@email.com', 'Female', '2004-05-12', 'Grade 11', '987 Cedar Dr', 'Mike Wilson', '09123456789', 'profilePhoto19.png', NULL, '2025-08-11'),
(904728163059, 'Smith', 'Emma', 'Robinson', 'emma.smith@email.com', 'Female', '2006-07-22', 'Grade 11', '456 Oak Ave', 'Tom Smith', '09123456789', 'profilePhoto1.png', NULL, '2025-08-11');

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
(1, 'ICT-101', 'ICT', '2025-08-11 10:33:57'),
(3, 'SCI-101', 'SCI', '2025-08-11 10:35:14');

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
(1, 'Loraine', 'Castro', 'SRNTS', 'castro.loraine.26@gmail.com', '_lorainecastro', '$2y$10$AK3ZL2g3VEEPpsP8k0ZK3elYl6BzAh3sYAUxeiFvaHW6yXOKucgiO', 'profile_1_1754458788.jpg', 1, 1, NULL, 'EMAIL_VERIFICATION', '2025-08-06 13:38:16', NULL, 1, '2025-08-11 10:33:30');
(2, 'Loraine', 'Castro', 'SRNTS', 'elci.bank@gmail.com', 'lorainecastro', '$2y$10$AK3ZL2g3VEEPpsP8k0ZK3elYl6BzAh3sYAUxeiFvaHW6yXOKucgiO', 'profile_1_1754458788.jpg', 1, 1, NULL, 'EMAIL_VERIFICATION', '2025-08-06 13:38:16', NULL, 1, '2025-08-11 10:33:30');

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
(2, 1, 'deee8667b6c21f697cc6ade9c01b6531fe9a0bf76aa4355828192e34a3e4d15a', '2025-09-10 10:33:30', '2025-08-11 18:33:30');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

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
