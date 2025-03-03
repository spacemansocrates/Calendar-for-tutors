-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2025 at 10:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bloom`
--

-- --------------------------------------------------------

--
-- Table structure for table `homework`
--

CREATE TABLE `homework` (
  `homework_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Assigned','Submitted','Graded','Late') DEFAULT 'Assigned',
  `file_path` varchar(255) DEFAULT NULL,
  `submission_path` varchar(255) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `lesson_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `lesson_type` enum('Regular','Demo','Catchup') NOT NULL,
  `session_status` enum('Scheduled','Delivered','No Show','Cancelled','Rescheduled') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `tutor_id`, `student_name`, `lesson_date`, `start_time`, `end_time`, `lesson_type`, `session_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Usman', '2025-03-01', '09:00:00', '10:00:00', 'Regular', 'Delivered', 'he did percentages\r\n', '2025-03-03 17:44:27', '2025-03-03 17:44:27'),
(2, 1, 'abigail', '2025-03-01', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'she did a school worksheet', '2025-03-03 17:44:55', '2025-03-03 17:44:55'),
(3, 1, 'Arash ', '2025-03-02', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'he did surds', '2025-03-03 17:45:40', '2025-03-03 17:45:40'),
(4, 1, 'Mofiyin', '2025-03-02', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'he did equations ', '2025-03-03 17:46:28', '2025-03-03 17:46:28'),
(5, 1, 'konrad ', '2025-03-03', '17:00:00', '18:00:00', 'Regular', 'Delivered', 'he did algebraic fractions ', '2025-03-03 17:50:36', '2025-03-03 17:50:36'),
(8, 1, 'Mofiyin', '2025-03-03', '18:00:00', '19:00:00', 'Regular', 'Delivered', 'two step equations ', '2025-03-03 18:31:40', '2025-03-03 18:31:40'),
(9, 1, 'muhammad', '2025-03-03', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'he did a past paper\r\n', '2025-03-03 21:03:49', '2025-03-03 21:03:49'),
(10, 1, 'Alex', '2025-03-03', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'did a past paper', '2025-03-03 21:05:34', '2025-03-03 21:05:34');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `grade` varchar(20) DEFAULT NULL,
  `subjects` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `email`, `password`, `parent_name`, `parent_email`, `phone`, `grade`, `subjects`, `notes`, `created_at`, `last_login`) VALUES
(1, 'Ayaan', 'ayaan@bloom.com', '$2y$10$d2311/aJHLf19hh4ap0z6et9MxHcoQWbk2ol8p9l0pQs4FzAv0GHW', NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 17:22:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

CREATE TABLE `tutors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`, `link`) VALUES
(1, 'Paul Naho', 'paul@bloom.com', '$2y$10$maFKYyb3pgQ0aawfdk6csORvXDbXdnFtVCzb8rUdWkgf3zW3wnSLO', '2025-03-03 17:29:05', '2025-03-03 18:44:58', 'https://meet.google.com/qbn-zfsj-zxa');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `homework`
--
ALTER TABLE `homework`
  ADD PRIMARY KEY (`homework_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lessons_date` (`lesson_date`),
  ADD KEY `idx_lessons_tutor_date` (`tutor_id`,`lesson_date`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `homework`
--
ALTER TABLE `homework`
  MODIFY `homework_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `homework`
--
ALTER TABLE `homework`
  ADD CONSTRAINT `homework_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
