-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 11, 2025 at 01:29 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_super_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password_hash`, `email`, `created_at`, `updated_at`, `is_super_admin`) VALUES
(2, 'superadmin', '$2y$10$TOAnqppyyuT8dT.VrK3QpO3.DnDu4eEya7T8sA79qhvXOU68WVW/2', 'superadmin@example.com', '2025-03-08 18:43:10', '2025-03-10 19:16:12', 1);

-- --------------------------------------------------------

--
-- Table structure for table `homework`
--

DROP TABLE IF EXISTS `homework`;
CREATE TABLE IF NOT EXISTS `homework` (
  `homework_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `due_date` date DEFAULT NULL,
  `status` enum('Assigned','Submitted','Graded','Late') COLLATE utf8mb4_general_ci DEFAULT 'Assigned',
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submission_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_general_ci,
  `grade` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `student_id` int DEFAULT NULL,
  `tutor_id` int DEFAULT NULL,
  `topic` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submission_date` datetime DEFAULT NULL,
  PRIMARY KEY (`homework_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homework`
--

INSERT INTO `homework` (`homework_id`, `title`, `description`, `due_date`, `status`, `file_path`, `submission_path`, `feedback`, `grade`, `created_at`, `student_id`, `tutor_id`, `topic`, `submitted_file_path`, `submission_date`) VALUES
(1, 'Math Algebra Homework', 'Solve 20 algebraic equations', '2025-03-10', '', '/homework/math1.pdf', NULL, NULL, NULL, '2025-03-04 22:00:00', 101, 201, 'Algebra', NULL, NULL),
(2, 'History Essay', 'Write 500 words about WW2', '2025-03-12', 'Submitted', '/homework/history1.pdf', '/submissions/history1_submitted.pdf', 'Well written but needs more sources', 'B+', '2025-03-04 22:00:00', 102, 202, 'History', '/submissions/history1_submitted.pdf', '2025-03-06 00:00:00'),
(3, 'Science Lab Report', 'Document the reaction between vinegar and baking soda', '2025-03-11', 'Submitted', '/homework/science1.pdf', '/submissions/science1_submitted.pdf', 'Good structure, minor formatting issues', 'A-', '2025-03-04 22:00:00', 103, 203, 'Chemistry', '/submissions/science1_submitted.pdf', '2025-03-07 00:00:00'),
(4, 'English Literature Analysis', 'Analyze themes in \"To Kill a Mockingbird\"', '2025-03-15', '', '/homework/english1.pdf', NULL, NULL, NULL, '2025-03-04 22:00:00', 104, 204, 'Literature', NULL, NULL),
(5, 'Computer Science Project', 'Create a simple calculator using Python', '2025-03-14', 'Submitted', '/homework/cs1.pdf', '/submissions/cs1_submitted.py', 'Code works well, add comments', 'A', '2025-03-04 22:00:00', 105, 205, 'Programming', '/submissions/cs1_submitted.py', '2025-03-08 00:00:00'),
(11, 'The Roman Empire', 'research about the roman empire ', '2025-03-08', 'Graded', 'uploads/homework/67c89d5447252_67c8601a6dc4e_this is the homework.txt', NULL, 'this was good, nice work!', 'A', '2025-03-05 18:52:04', 1, 1, 'Research', 'uploads/student_homework/1_11_1741200744_1_10_1741199925_tutor_system.sql', '2025-03-05 20:52:24'),
(12, 'Website ', 'sgv website second page', '2025-03-11', 'Graded', 'uploads/homework/67cb104ea0c8b_Screenshot 2023-12-03 232032.png', NULL, 'do more research bud', '69', '2025-03-07 15:27:10', 4, 1, 'Yo mama ', 'uploads/student_homework/4_12_1741361266_Screenshot 2023-12-01 175444.png', '2025-03-07 17:27:46'),
(13, 'Fractional divisions', 'answer all the question ', '2025-03-12', 'Graded', 'uploads/homework/67cc4d35cbf36_1CP1_02_que_20201107.pdf', NULL, 'you got 80%', 'B', '2025-03-08 13:59:17', 1, 1, 'Fractions and operations', 'uploads/student_homework/1_13_1741442386_Colorful Cool Lonely Man Watching the Sunset Photo Album Cover.png', '2025-03-08 15:59:46');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

DROP TABLE IF EXISTS `lessons`;
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tutor_id` int NOT NULL,
  `student_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `lesson_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `lesson_type` enum('Regular','Demo','Catchup') COLLATE utf8mb4_general_ci NOT NULL,
  `session_status` enum('Scheduled','Delivered','No Show','Cancelled','Rescheduled') COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lessons_date` (`lesson_date`),
  KEY `idx_lessons_tutor_date` (`tutor_id`,`lesson_date`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `tutor_id`, `student_name`, `lesson_date`, `start_time`, `end_time`, `lesson_type`, `session_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Usman', '2025-03-01', '09:00:00', '10:00:00', 'Regular', 'Delivered', 'he did percentages\r\n', '2025-03-03 15:44:27', '2025-03-03 15:44:27'),
(2, 1, 'abigail', '2025-03-01', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'she did a school worksheet', '2025-03-03 15:44:55', '2025-03-03 15:44:55'),
(3, 1, 'Arash ', '2025-03-02', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'he did surds', '2025-03-03 15:45:40', '2025-03-03 15:45:40'),
(4, 1, 'Mofiyin', '2025-03-02', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'he did equations ', '2025-03-03 15:46:28', '2025-03-03 15:46:28'),
(5, 1, 'konrad ', '2025-03-03', '17:00:00', '18:00:00', 'Regular', 'Delivered', 'he did algebraic fractions ', '2025-03-03 15:50:36', '2025-03-03 15:50:36'),
(8, 1, 'Mofiyin', '2025-03-03', '18:00:00', '19:00:00', 'Regular', 'Delivered', 'two step equations ', '2025-03-03 16:31:40', '2025-03-03 16:31:40'),
(9, 1, 'muhammad', '2025-03-03', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'he did a past paper\r\n', '2025-03-03 19:03:49', '2025-03-03 19:03:49'),
(10, 1, 'Alex', '2025-03-03', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'did a past paper', '2025-03-03 19:05:34', '2025-03-03 19:05:34'),
(12, 1, 'Neina', '2025-03-04', '16:00:00', '17:00:00', 'Regular', 'Delivered', 'she did enlargement ', '2025-03-04 15:25:37', '2025-03-04 15:25:37'),
(13, 1, 'Adalyn', '2025-03-04', '17:00:00', '18:00:00', 'Regular', 'Delivered', 'she did adding fractions', '2025-03-04 15:26:06', '2025-03-04 15:26:06'),
(14, 1, 'Gazaldeep', '2025-03-04', '18:00:00', '19:00:00', 'Regular', 'Delivered', 'she did surds past paper questions ', '2025-03-04 16:42:13', '2025-03-04 16:42:13'),
(15, 1, 'Indie ', '2025-03-04', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'she did a past paper', '2025-03-04 19:13:15', '2025-03-04 19:13:15'),
(16, 1, 'Daithi ', '2025-03-04', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'he did a past paper', '2025-03-04 19:13:33', '2025-03-04 19:13:33'),
(17, 1, 'Jake', '2025-03-05', '16:00:00', '17:00:00', 'Regular', 'Delivered', 'he did cube roots and cubes', '2025-03-05 15:27:09', '2025-03-05 15:27:09'),
(18, 1, 'Henry', '2025-03-05', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'he did the cosine rule ', '2025-03-05 20:08:50', '2025-03-05 20:08:50'),
(19, 1, 'Sviatlana ', '2025-03-05', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'she did quadratic graphs ', '2025-03-05 20:10:12', '2025-03-05 20:10:12'),
(20, 1, 'Sviatlana ', '2025-03-06', '16:00:00', '17:00:00', 'Regular', 'Delivered', 'She did equations \r\n', '2025-03-06 17:34:02', '2025-03-06 17:34:02'),
(21, 1, 'Coby ', '2025-03-06', '17:00:00', '18:00:00', 'Regular', 'Delivered', 'did mean, mode, median and range ', '2025-03-06 17:34:32', '2025-03-06 17:36:39'),
(22, 1, 'Nadia', '2025-03-06', '18:00:00', '19:00:00', 'Regular', 'Delivered', 'she did rearranging formulae', '2025-03-06 17:35:16', '2025-03-06 17:35:16'),
(23, 1, 'Mofiyin ', '2025-03-06', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'Rearranging formulae\r\n', '2025-03-06 18:35:52', '2025-03-06 18:35:52'),
(25, 1, 'Usman', '2025-03-07', '16:00:00', '17:00:00', 'Regular', 'Delivered', 'he did a sats past paper', '2025-03-07 16:06:19', '2025-03-07 16:06:19'),
(26, 1, 'Abigail ', '2025-03-07', '18:00:00', '19:00:00', 'Regular', 'Delivered', 'surface area of prisms', '2025-03-07 16:20:38', '2025-03-07 16:20:38'),
(28, 1, 'Muhammad ', '2025-03-07', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'he did a past paper\r\n', '2025-03-07 18:51:42', '2025-03-07 18:51:42'),
(29, 1, 'Mofiyin', '2025-03-07', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'he did complicated area of a circle questions ', '2025-03-07 18:55:11', '2025-03-07 18:55:11'),
(30, 1, 'Usman ', '2025-03-08', '09:00:00', '10:00:00', 'Regular', 'Delivered', 'he did a sats past paper', '2025-03-08 07:47:41', '2025-03-08 07:47:41'),
(47, 1, 'Arash ', '2025-03-09', '19:00:00', '20:00:00', 'Regular', 'Delivered', 'Forming and simplifying ratios', '2025-03-10 19:44:32', '2025-03-10 19:44:32'),
(48, 1, 'Mofiyin', '2025-03-09', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'volume of compound shapes', '2025-03-10 19:46:23', '2025-03-10 19:46:23'),
(49, 1, 'Jake', '2025-03-10', '16:00:00', '17:00:00', 'Regular', 'Delivered', 'indices ', '2025-03-10 19:57:54', '2025-03-10 19:57:54'),
(50, 1, 'Konrad', '2025-03-10', '17:00:00', '18:00:00', 'Regular', 'No Show', 'no show', '2025-03-10 19:58:19', '2025-03-10 19:58:19'),
(51, 1, 'Abigail', '2025-03-10', '18:00:00', '19:00:00', 'Regular', 'Delivered', 'cosine rule', '2025-03-10 19:59:43', '2025-03-10 19:59:43'),
(52, 1, 'Muhammad ', '2025-03-10', '20:00:00', '21:00:00', 'Regular', 'Delivered', 'we did a past paper', '2025-03-10 20:16:49', '2025-03-10 20:16:49');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `parent_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grade` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subjects` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `email`, `password`, `parent_name`, `parent_email`, `phone`, `grade`, `subjects`, `notes`, `created_at`, `last_login`) VALUES
(1, 'Ayaan', 'ayaan@bloom.com', '$2y$10$d2311/aJHLf19hh4ap0z6et9MxHcoQWbk2ol8p9l0pQs4FzAv0GHW', NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 17:22:26', NULL),
(2, 'nadiie', 'nadia@bloom.com', '$2y$10$uS126I2iVWv03IpRYk3QtepsGah7yyEJOexqvpBY0/9BJyMAo7zJm', '', '', '', '', '', '', '2025-03-06 10:03:42', NULL),
(4, 'Eugene Onions', 'eugene@bloom.com', '$2y$10$8jPDNcOTnOlnCW5p6ws6EOXxpN7wY7.aPQDuM4iu6clS51VrRyIkK', NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-07 15:24:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

DROP TABLE IF EXISTS `student_subjects`;
CREATE TABLE IF NOT EXISTS `student_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `year_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_subject_year` (`student_id`,`subject_id`,`year_level`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_id`, `year_level`, `created_at`) VALUES
(1, 1, 1, 'Year 11', '2025-03-07 19:51:37');

-- --------------------------------------------------------

--
-- Table structure for table `student_topic_progress`
--

DROP TABLE IF EXISTS `student_topic_progress`;
CREATE TABLE IF NOT EXISTS `student_topic_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `topic_id` int NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_date` datetime DEFAULT NULL,
  `tutor_id` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_topic` (`student_id`,`topic_id`),
  KEY `topic_id` (`topic_id`),
  KEY `tutor_id` (`tutor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_topic_progress`
--

INSERT INTO `student_topic_progress` (`id`, `student_id`, `topic_id`, `is_completed`, `completed_date`, `tutor_id`, `notes`, `created_at`, `updated_at`) VALUES
(4, 1, 1, 1, '2025-03-08 14:02:37', NULL, NULL, '2025-03-08 14:02:37', '2025-03-08 14:02:37');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `subject_id` int NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `subject_name` (`subject_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `description`, `created_at`) VALUES
(1, 'Mathematics ', 'higher mathematics curriculum', '2025-03-07 18:43:22'),
(2, 'English', 'English language and literature', '2025-03-07 18:43:22'),
(3, 'Science', 'General science including physics, chemistry and biology', '2025-03-07 18:43:22'),
(4, 'History', 'World and national history', '2025-03-07 18:43:22'),
(5, 'Geography', 'Physical and human geography', '2025-03-07 18:43:22'),
(6, 'Computing', 'Programming, IT, and digital literacy', '2025-03-07 22:25:14'),
(7, 'English Literature', 'Study of prose, poetry, and drama', '2025-03-07 22:25:14'),
(8, 'Biology', 'Study of living organisms and life processes', '2025-03-07 22:25:14'),
(9, 'Chemistry', 'Study of substances, reactions, and matter', '2025-03-07 22:25:14'),
(10, 'Physics', 'Study of forces, energy, and the physical world', '2025-03-07 22:25:14');

-- --------------------------------------------------------

--
-- Table structure for table `subtopics`
--

DROP TABLE IF EXISTS `subtopics`;
CREATE TABLE IF NOT EXISTS `subtopics` (
  `subtopic_id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int NOT NULL,
  `subtopic_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `difficulty_level` enum('Easy','Medium','Hard') COLLATE utf8mb4_general_ci DEFAULT 'Medium',
  `estimated_time_minutes` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`subtopic_id`),
  KEY `topic_id` (`topic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=245 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subtopics`
--

INSERT INTO `subtopics` (`subtopic_id`, `topic_id`, `subtopic_name`, `description`, `difficulty_level`, `estimated_time_minutes`, `created_at`) VALUES
(1, 1, 'Graphing an inequality', 'Learning to graph inequalities on a coordinate plane', '', 30, '2025-03-07 23:04:55'),
(2, 1, 'Graphing more than one inequality', 'Graphing systems of inequalities and identifying feasible regions', '', 45, '2025-03-07 23:04:55'),
(3, 1, 'Linear Programming word Problem', 'Solving optimization problems using linear programming techniques', '', 60, '2025-03-07 23:04:55'),
(4, 1, 'Past Exam Paper Practice', 'Practice with past exam questions on linear programming', '', 90, '2025-03-07 23:04:55'),
(5, 2, 'Quadratic Graphs', 'Understanding and graphing quadratic functions', '', 45, '2025-03-07 23:04:55'),
(6, 2, 'Cubic Graphs', 'Understanding and graphing cubic functions', '', 45, '2025-03-07 23:04:55'),
(7, 2, 'Reciprocal and Exponential Graphs', 'Understanding and graphing reciprocal and exponential functions', '', 60, '2025-03-07 23:04:55'),
(8, 2, 'Circle Graphs', 'Understanding and graphing circle equations', '', 45, '2025-03-07 23:04:55'),
(9, 2, 'Trigonometric Graphs', 'Understanding and graphing sine, cosine, and tangent functions', '', 60, '2025-03-07 23:04:55'),
(10, 2, 'Transforming Graphs', 'Applying transformations to various graph types', '', 60, '2025-03-07 23:04:55'),
(11, 2, 'Review Exercise', 'Consolidated practice on different graph types', '', 60, '2025-03-07 23:04:55'),
(12, 2, 'Exam-Style Questions', 'Practice with exam-style questions on graphs', '', 45, '2025-03-07 23:04:55'),
(13, 2, 'Past Exam Paper Practice', 'Practice with past exam questions on different graph types', '', 90, '2025-03-07 23:04:55'),
(14, 3, 'Evaluating Functions', 'Computing function values for given inputs', '', 30, '2025-03-07 23:04:55'),
(15, 3, 'Composite Functions', 'Understanding and working with function composition', '', 45, '2025-03-07 23:04:55'),
(16, 3, 'Inverse Functions', 'Finding and using inverse functions', '', 45, '2025-03-07 23:04:55'),
(17, 3, 'Review Exercise', 'Consolidated practice on functions', '', 60, '2025-03-07 23:04:55'),
(18, 3, 'Exam-Style Questions', 'Practice with exam-style questions on functions', '', 45, '2025-03-07 23:04:55'),
(19, 3, 'Past Exam Paper Practice', 'Practice with past exam questions on functions', '', 90, '2025-03-07 23:04:55'),
(20, 4, 'Pythagoras\' Theorem', 'Understanding and applying the Pythagorean theorem', '', 45, '2025-03-07 23:04:55'),
(21, 4, 'Pythagoras\' Theorem in 3D', 'Extending Pythagorean theorem to three dimensions', '', 60, '2025-03-07 23:04:55'),
(22, 4, 'Trigonometry — Sin, Cos and Tan', 'Basic trigonometric ratios and calculations', '', 60, '2025-03-07 23:04:55'),
(23, 4, 'The Sine and Cosine Rules', 'Advanced trigonometric applications for non-right triangles', '', 60, '2025-03-07 23:04:55'),
(24, 4, 'Trigonometry in 3D', 'Applying trigonometry in three-dimensional contexts', '', 75, '2025-03-07 23:04:55'),
(25, 4, 'Review Exercise', 'Consolidated practice on Pythagoras and trigonometry', '', 60, '2025-03-07 23:04:55'),
(26, 4, 'Exam-Style Questions', 'Practice with exam-style questions on Pythagoras and trigonometry', '', 45, '2025-03-07 23:04:55'),
(27, 4, 'Past Exam Paper Practice', 'Practice with past exam questions on Pythagoras and trigonometry', '', 90, '2025-03-07 23:04:55'),
(28, 5, 'Calculations', 'Basic arithmetic operations and principles', '', 30, '2025-03-07 23:04:55'),
(29, 5, 'Multiples and Factors', 'Understanding multiples and factors of numbers', '', 30, '2025-03-07 23:04:55'),
(30, 5, 'Prime Numbers and Prime Factors', 'Identifying prime numbers and finding prime factorizations', '', 45, '2025-03-07 23:04:55'),
(31, 5, 'LCM and HCF', 'Finding lowest common multiples and highest common factors', '', 45, '2025-03-07 23:04:55'),
(32, 5, 'Review Exercise', 'Consolidated practice on arithmetic fundamentals', '', 45, '2025-03-07 23:04:55'),
(33, 5, 'Exam-Style Questions', 'Practice with exam-style questions on arithmetic', '', 30, '2025-03-07 23:04:55'),
(34, 5, 'Past Exam Paper Practice', 'Practice with past exam questions on arithmetic', '', 60, '2025-03-07 23:04:55'),
(35, 6, 'Rounding', 'Techniques for rounding numbers to specified accuracy', '', 30, '2025-03-07 23:04:55'),
(36, 6, 'Upper and Lower Bounds', 'Understanding measurement errors and bounds', '', 45, '2025-03-07 23:04:55'),
(37, 6, 'Review Exercise', 'Consolidated practice on approximations', '', 45, '2025-03-07 23:04:55'),
(38, 6, 'Exam-Style Questions', 'Practice with exam-style questions on approximations', '', 30, '2025-03-07 23:04:55'),
(39, 6, 'Past Exam Paper Practice', 'Practice with past exam questions on approximations', '', 60, '2025-03-07 23:04:55'),
(40, 7, 'Equivalent Fractions', 'Understanding and finding equivalent fractions', '', 30, '2025-03-07 23:04:55'),
(41, 7, 'Mixed Numbers', 'Converting between improper fractions and mixed numbers', '', 30, '2025-03-07 23:04:55'),
(42, 7, 'Ordering Fractions', 'Comparing and ordering fractions of different denominators', '', 30, '2025-03-07 23:04:55'),
(43, 7, 'Adding and Subtracting Fractions', 'Techniques for addition and subtraction with fractions', '', 45, '2025-03-07 23:04:55'),
(44, 7, 'Multiplying and Dividing by Fractions', 'Methods for multiplication and division with fractions', '', 45, '2025-03-07 23:04:55'),
(45, 7, 'Fractions and Decimals', 'Converting between fractions and decimal representations', '', 30, '2025-03-07 23:04:55'),
(46, 7, 'Review Exercise', 'Consolidated practice on fractions', '', 45, '2025-03-07 23:04:55'),
(47, 7, 'Exam-Style Questions', 'Practice with exam-style questions on fractions', '', 30, '2025-03-07 23:04:55'),
(48, 7, 'Past Exam Paper Practice', 'Practice with past exam questions on fractions', '', 60, '2025-03-07 23:04:55'),
(49, 8, 'Ratios', 'Understanding and working with ratios', '', 30, '2025-03-07 23:04:55'),
(50, 8, 'Using Ratios', 'Applying ratios to solve problems', '', 45, '2025-03-07 23:04:55'),
(51, 8, 'Dividing in a Given Ratio', 'Splitting quantities according to specified ratios', '', 45, '2025-03-07 23:04:55'),
(52, 8, 'Proportion', 'Understanding and applying direct and inverse proportion', '', 45, '2025-03-07 23:04:55'),
(53, 8, 'Review Exercise', 'Consolidated practice on ratio and proportion', '', 45, '2025-03-07 23:04:55'),
(54, 8, 'Exam-Style Questions', 'Practice with exam-style questions on ratio and proportion', '', 30, '2025-03-07 23:04:55'),
(55, 8, 'Past Exam Paper Practice', 'Practice with past exam questions on ratio and proportion', '', 60, '2025-03-07 23:04:55'),
(56, 9, 'Percentages', 'Understanding percentages as parts of a whole', '', 30, '2025-03-07 23:04:55'),
(57, 9, 'Percentages, Fractions and Decimals', 'Converting between percentages, fractions, and decimals', '', 30, '2025-03-07 23:04:55'),
(58, 9, 'Percentage Increase and Decrease', 'Calculating changes as percentages', '', 45, '2025-03-07 23:04:55'),
(59, 9, 'Compound Percentage Change', 'Working with successive percentage changes', '', 60, '2025-03-07 23:04:55'),
(60, 9, 'Review Exercise', 'Consolidated practice on percentages', '', 45, '2025-03-07 23:04:55'),
(61, 9, 'Exam-Style Questions', 'Practice with exam-style questions on percentages', '', 30, '2025-03-07 23:04:55'),
(62, 9, 'Past Exam Paper Practice', 'Practice with past exam questions on percentages', '', 60, '2025-03-07 23:04:55'),
(63, 10, 'Simplifying Expressions', 'Techniques for simplifying algebraic expressions', '', 30, '2025-03-07 23:04:55'),
(64, 10, 'Expanding Brackets', 'Multiplying terms and removing brackets', '', 45, '2025-03-07 23:04:55'),
(65, 10, 'Factorising — Common Factors', 'Finding and factoring out common terms', '', 45, '2025-03-07 23:04:55'),
(66, 10, 'Factorising — Quadratics', 'Factoring quadratic expressions', '', 60, '2025-03-07 23:04:55'),
(67, 10, 'Algebraic Fractions', 'Simplifying and operating with algebraic fractions', '', 60, '2025-03-07 23:04:55'),
(68, 10, 'Review Exercise', 'Consolidated practice on algebraic expressions', '', 45, '2025-03-07 23:04:55'),
(69, 10, 'Exam-Style Questions', 'Practice with exam-style questions on expressions', '', 45, '2025-03-07 23:04:55'),
(70, 10, 'Past Exam Paper Practice', 'Practice with past exam questions on expressions', '', 90, '2025-03-07 23:04:55'),
(71, 11, 'Squares, Cubes and Roots', 'Working with square and cube numbers and their roots', '', 30, '2025-03-07 23:04:55'),
(72, 11, 'Indices and Index Laws', 'Understanding and applying the laws of exponents', '', 45, '2025-03-07 23:04:55'),
(73, 11, 'Standard Form', 'Writing and calculating with numbers in scientific notation', '', 45, '2025-03-07 23:04:55'),
(74, 11, 'Surds', 'Manipulating and simplifying irrational square roots', '', 60, '2025-03-07 23:04:55'),
(75, 11, 'Review Exercise', 'Consolidated practice on powers and roots', '', 45, '2025-03-07 23:04:55'),
(76, 11, 'Exam-Style Questions', 'Practice with exam-style questions on powers and roots', '', 45, '2025-03-07 23:04:55'),
(77, 11, 'Past Exam Paper Practice', 'Practice with past exam questions on powers and roots', '', 90, '2025-03-07 23:04:55'),
(78, 12, 'Writing Formulas', 'Creating algebraic formulas from word problems', '', 45, '2025-03-07 23:04:55'),
(79, 12, 'Substituting into a Formula', 'Evaluating formulas with given values', '', 30, '2025-03-07 23:04:55'),
(80, 12, 'Rearranging Formulas', 'Manipulating formulas to isolate different variables', '', 45, '2025-03-07 23:04:55'),
(81, 12, 'Review Exercise', 'Consolidated practice on formulas', '', 45, '2025-03-07 23:04:55'),
(82, 12, 'Exam-Style Questions', 'Practice with exam-style questions on formulas', '', 30, '2025-03-07 23:04:55'),
(83, 12, 'Past Exam Paper Practice', 'Practice with past exam questions on formulas', '', 60, '2025-03-07 23:04:55'),
(84, 13, 'Solving Equations', 'Methods for solving linear and simple equations', '', 45, '2025-03-07 23:04:55'),
(85, 13, 'Forming Equations from Word Problems', 'Translating word problems into algebraic equations', '', 45, '2025-03-07 23:04:55'),
(86, 13, 'Identities', 'Understanding and working with algebraic identities', '', 60, '2025-03-07 23:04:55'),
(87, 13, 'Proof', 'Techniques for mathematical proofs', '', 60, '2025-03-07 23:04:55'),
(88, 13, 'Iterative Methods', 'Using iterative approaches to solve equations', '', 60, '2025-03-07 23:04:55'),
(89, 13, 'Review Exercise', 'Consolidated practice on equations', '', 45, '2025-03-07 23:04:55'),
(90, 13, 'Exam-Style Questions', 'Practice with exam-style questions on equations', '', 45, '2025-03-07 23:04:55'),
(91, 13, 'Past Exam Paper Practice', 'Practice with past exam questions on equations', '', 90, '2025-03-07 23:04:55'),
(92, 14, 'Direct Proportion', 'Understanding and applying direct proportion relationships', '', 45, '2025-03-07 23:04:55'),
(93, 14, 'Inverse Proportion', 'Understanding and applying inverse proportion relationships', '', 45, '2025-03-07 23:04:55'),
(94, 14, 'Review Exercise', 'Consolidated practice on proportion', '', 45, '2025-03-07 23:04:55'),
(95, 14, 'Exam-Style Questions', 'Practice with exam-style questions on proportion', '', 30, '2025-03-07 23:04:55'),
(96, 14, 'Past Exam Paper Practice', 'Practice with past exam questions on proportion', '', 60, '2025-03-07 23:04:55'),
(97, 15, 'Solving Quadratic Equations by Factorising', 'Using factorization to solve quadratic equations', '', 45, '2025-03-07 23:04:55'),
(98, 15, 'Completing the Square', 'Solving quadratics by completing the square method', '', 60, '2025-03-07 23:04:55'),
(99, 15, 'The Quadratic Formula', 'Using the formula to solve any quadratic equation', '', 45, '2025-03-07 23:04:55'),
(100, 15, 'Review Exercise', 'Consolidated practice on quadratic equations', '', 45, '2025-03-07 23:04:55'),
(101, 15, 'Exam-Style Questions', 'Practice with exam-style questions on quadratic equations', '', 45, '2025-03-07 23:04:55'),
(102, 15, 'Past Exam Paper Practice', 'Practice with past exam questions on quadratic equations', '', 90, '2025-03-07 23:04:55'),
(103, 16, 'Simultaneous Linear Equations', 'Solving pairs of linear equations algebraically', '', 45, '2025-03-07 23:04:55'),
(104, 16, 'Simultaneous Linear and Quadratic Equations', 'Solving mixed systems of equations', '', 60, '2025-03-07 23:04:55'),
(105, 16, 'Review Exercise', 'Consolidated practice on simultaneous equations', '', 45, '2025-03-07 23:04:55'),
(106, 16, 'Exam-Style Questions', 'Practice with exam-style questions on simultaneous equations', '', 45, '2025-03-07 23:04:55'),
(107, 16, 'Past Exam Paper Practice', 'Practice with past exam questions on simultaneous equations', '', 90, '2025-03-07 23:04:55'),
(108, 17, 'Solving Inequalities', 'Methods for solving linear inequalities', '', 45, '2025-03-07 23:04:55'),
(109, 17, 'Quadratic Inequalities', 'Techniques for solving quadratic inequalities', '', 60, '2025-03-07 23:04:55'),
(110, 17, 'Graphing Inequalities', 'Representing inequalities on coordinate planes', '', 45, '2025-03-07 23:04:55'),
(111, 17, 'Review Exercise', 'Consolidated practice on inequalities', '', 45, '2025-03-07 23:04:55'),
(112, 17, 'Exam-Style Questions', 'Practice with exam-style questions on inequalities', '', 45, '2025-03-07 23:04:55'),
(113, 17, 'Past Exam Paper Practice', 'Practice with past exam questions on inequalities', '', 90, '2025-03-07 23:04:55'),
(114, 18, 'Term to Term Rules', 'Finding patterns and rules in sequences', '', 30, '2025-03-07 23:04:55'),
(115, 18, 'Using the nth Term', 'Finding specific terms using general formulas', '', 45, '2025-03-07 23:04:55'),
(116, 18, 'Finding the nth Term', 'Deriving formulas for arithmetic and geometric sequences', '', 45, '2025-03-07 23:04:55'),
(117, 18, 'Review Exercise', 'Consolidated practice on sequences', '', 45, '2025-03-07 23:04:55'),
(118, 18, 'Exam-Style Questions', 'Practice with exam-style questions on sequences', '', 30, '2025-03-07 23:04:55'),
(119, 18, 'Past Exam Paper Practice', 'Practice with past exam questions on sequences', '', 60, '2025-03-07 23:04:55'),
(120, 19, 'Straight-Line Graphs', 'Drawing and interpreting linear functions', '', 30, '2025-03-07 23:04:55'),
(121, 19, 'Gradients', 'Understanding and calculating slopes of lines', '', 45, '2025-03-07 23:04:55'),
(122, 19, 'Equations of Straight-Line Graphs', 'Finding and using different forms of line equations', '', 45, '2025-03-07 23:04:55'),
(123, 19, 'Parallel and Perpendicular Lines', 'Relationships between gradients of related lines', '', 45, '2025-03-07 23:04:55'),
(124, 19, 'Line Segments', 'Working with portions of lines on the coordinate plane', '', 45, '2025-03-07 23:04:55'),
(125, 19, 'Review Exercise', 'Consolidated practice on straight-line graphs', '', 45, '2025-03-07 23:04:55'),
(126, 19, 'Exam-Style Questions', 'Practice with exam-style questions on straight-line graphs', '', 30, '2025-03-07 23:04:55'),
(127, 19, 'Past Exam Paper Practice', 'Practice with past exam questions on straight-line graphs', '', 60, '2025-03-07 23:04:55'),
(128, 20, 'Interpreting Real-Life Graphs', 'Understanding graphs in context', '', 45, '2025-03-07 23:04:55'),
(129, 20, 'Drawing Real-Life Graphs', 'Creating graphs to represent real situations', '', 45, '2025-03-07 23:04:55'),
(130, 20, 'Solving Simultaneous Equations Graphically', 'Using graphs to find intersection points', '', 45, '2025-03-07 23:04:55'),
(131, 20, 'Solving Quadratics Graphically', 'Finding roots and turning points on quadratic graphs', '', 45, '2025-03-07 23:04:55'),
(132, 20, 'Gradients of Curves', 'Finding rates of change for non-linear functions', '', 60, '2025-03-07 23:04:55'),
(133, 20, 'Review Exercise', 'Consolidated practice on using graphs', '', 45, '2025-03-07 23:04:55'),
(134, 20, 'Exam-Style Questions', 'Practice with exam-style questions on using graphs', '', 45, '2025-03-07 23:04:55'),
(135, 20, 'Past Exam Paper Practice', 'Practice with past exam questions on using graphs', '', 90, '2025-03-07 23:04:55'),
(136, 21, 'Sets', 'Understanding set notation and operations', '', 45, '2025-03-07 23:04:55'),
(137, 21, 'Venn Diagrams', 'Representing sets using Venn diagrams', '', 45, '2025-03-07 23:04:55'),
(138, 21, 'Unions and Intersections', 'Operations combining sets', '', 45, '2025-03-07 23:04:55'),
(139, 21, 'Complement of a Set', 'Understanding and finding complementary sets', '', 45, '2025-03-07 23:04:55'),
(140, 21, 'Review Exercise', 'Consolidated practice on sets', '', 45, '2025-03-07 23:04:55'),
(141, 21, 'Exam-Style Questions', 'Practice with exam-style questions on sets', '', 30, '2025-03-07 23:04:55'),
(142, 21, 'Past Exam Paper Practice', 'Practice with past exam questions on sets', '', 60, '2025-03-07 23:04:55'),
(143, 22, 'Angles on Lines and Around Points', 'Understanding basic angle relationships', '', 30, '2025-03-07 23:04:55'),
(144, 22, 'Parallel Lines', 'Angle properties with parallel lines', '', 30, '2025-03-07 23:04:55'),
(145, 22, 'Triangles', 'Properties and angle rules in triangles', '', 30, '2025-03-07 23:04:55'),
(146, 22, 'Quadrilaterals', 'Properties of various four-sided shapes', '', 30, '2025-03-07 23:04:55'),
(147, 22, 'Polygons', 'Properties of regular and irregular polygons', '', 45, '2025-03-07 23:04:55'),
(148, 22, 'Symmetry', 'Identifying and working with symmetrical properties', '', 30, '2025-03-07 23:04:55'),
(149, 22, 'Review Exercise', 'Consolidated practice on angles and 2D shapes', '', 45, '2025-03-07 23:04:55'),
(150, 22, 'Exam-Style Questions', 'Practice with exam-style questions on angles and 2D shapes', '', 30, '2025-03-07 23:04:55'),
(151, 22, 'Past Exam Paper Practice', 'Practice with past exam questions on angles and 2D shapes', '', 60, '2025-03-07 23:04:55'),
(152, 23, 'Circle Theorems 1', 'Basic angle properties in circles', '', 45, '2025-03-07 23:04:55'),
(153, 23, 'Circle Theorems 2', 'Intermediate circle properties and theorems', '', 60, '2025-03-07 23:04:55'),
(154, 23, 'Circle Theorems 3', 'Advanced circle properties and proofs', '', 60, '2025-03-07 23:04:55'),
(155, 23, 'Review Exercise', 'Consolidated practice on circle geometry', '', 45, '2025-03-07 23:04:55'),
(156, 23, 'Exam-Style Questions', 'Practice with exam-style questions on circle geometry', '', 45, '2025-03-07 23:04:55'),
(157, 23, 'Past Exam Paper Practice', 'Practice with past exam questions on circle geometry', '', 90, '2025-03-07 23:04:55'),
(158, 24, 'Metric Units — Length, Mass and Volume', 'Understanding and converting basic metric units', '', 30, '2025-03-07 23:04:55'),
(159, 24, 'Metric Units — Area and Volume', 'Working with units for area and volume measurements', '', 30, '2025-03-07 23:04:55'),
(160, 24, 'Metric and Imperial Units', 'Converting between different measurement systems', '', 45, '2025-03-07 23:04:55'),
(161, 24, 'Estimating in Real Life', 'Practical estimation techniques', '', 30, '2025-03-07 23:04:55'),
(162, 24, 'Review Exercise', 'Consolidated practice on units and measurement', '', 45, '2025-03-07 23:04:55'),
(163, 24, 'Exam-Style Questions', 'Practice with exam-style questions on units and measurement', '', 30, '2025-03-07 23:04:55'),
(164, 24, 'Past Exam Paper Practice', 'Practice with past exam questions on units and measurement', '', 60, '2025-03-07 23:04:55'),
(165, 25, 'Compound Measures', 'Understanding combined units like speed and density', '', 45, '2025-03-07 23:04:55'),
(166, 25, 'Distance-Time Graphs', 'Interpreting and drawing distance-time graphs', '', 45, '2025-03-07 23:04:55'),
(167, 25, 'Velocity-Time Graphs', 'Understanding and using velocity-time graphs', '', 45, '2025-03-07 23:04:55'),
(168, 25, 'Review Exercise', 'Consolidated practice on compound measures', '', 45, '2025-03-07 23:04:55'),
(169, 25, 'Exam-Style Questions', 'Practice with exam-style questions on compound measures', '', 30, '2025-03-07 23:04:55'),
(170, 25, 'Past Exam Paper Practice', 'Practice with past exam questions on compound measures', '', 60, '2025-03-07 23:04:55'),
(171, 26, 'Scale Drawings', 'Creating drawings with precise scales', '', 45, '2025-03-07 23:04:55'),
(172, 26, 'Bearings', 'Working with directional measurements', '', 45, '2025-03-07 23:04:55'),
(173, 26, 'Constructions', 'Geometric constructions using compass and ruler', '', 45, '2025-03-07 23:04:55'),
(174, 26, 'Loci', 'Determining paths based on distance conditions', '', 60, '2025-03-07 23:04:55'),
(175, 26, 'Review Exercise', 'Consolidated practice on constructions', '', 45, '2025-03-07 23:04:55'),
(176, 26, 'Exam-Style Questions', 'Practice with exam-style questions on constructions', '', 45, '2025-03-07 23:04:55'),
(177, 26, 'Past Exam Paper Practice', 'Practice with past exam questions on constructions', '', 90, '2025-03-07 23:04:55'),
(178, 27, 'Vectors and Scalars', 'Understanding the difference between vectors and scalars', '', 45, '2025-03-07 23:04:55'),
(179, 27, 'Vector Geometry', 'Using vectors for geometric proofs and problems', '', 60, '2025-03-07 23:04:55'),
(180, 27, 'Review Exercise', 'Consolidated practice on vectors', '', 45, '2025-03-07 23:04:55'),
(181, 27, 'Exam-Style Questions', 'Practice with exam-style questions on vectors', '', 45, '2025-03-07 23:04:55'),
(182, 27, 'Past Exam Paper Practice', 'Practice with past exam questions on vectors', '', 90, '2025-03-07 23:04:55'),
(183, 28, 'Triangles and Quadrilaterals', 'Calculating perimeter and area of basic shapes', '', 30, '2025-03-07 23:04:55'),
(184, 28, 'Circles and Sectors', 'Finding the perimeter and area of circles and portions of circles', '', 45, '2025-03-07 23:04:55'),
(185, 28, 'Review Exercise', 'Consolidated practice on perimeter and area', '', 45, '2025-03-07 23:04:55'),
(186, 28, 'Exam-Style Questions', 'Practice with exam-style questions on perimeter and area', '', 30, '2025-03-07 23:04:55'),
(187, 28, 'Past Exam Paper Practice', 'Practice with past exam questions on perimeter and area', '', 60, '2025-03-07 23:04:55'),
(188, 29, 'Plans, Elevations and Isometric Drawings', 'Representing 3D objects in different views', '', 45, '2025-03-07 23:04:55'),
(189, 29, 'Volume', 'Calculating volumes of various 3D shapes', '', 45, '2025-03-07 23:04:55'),
(190, 29, 'Nets and Surface Area', 'Understanding nets and finding surface areas', '', 45, '2025-03-07 23:04:55'),
(191, 29, 'Spheres, Cones and Pyramids', 'Working with more complex 3D shapes', '', 60, '2025-03-07 23:04:55'),
(192, 29, 'Rates of Flow', 'Calculating volume changes over time', '', 60, '2025-03-07 23:04:55'),
(193, 29, 'Symmetry of 3D Shapes', 'Identifying symmetry in three dimensions', '', 45, '2025-03-07 23:04:55'),
(194, 29, 'Review Exercise', 'Consolidated practice on 3D shapes', '', 45, '2025-03-07 23:04:55'),
(195, 29, 'Exam-Style Questions', 'Practice with exam-style questions on 3D shapes', '', 45, '2025-03-07 23:04:55'),
(196, 29, 'Past Exam Paper Practice', 'Practice with past exam questions on 3D shapes', '', 90, '2025-03-07 23:04:55'),
(197, 30, 'Reflections', 'Understanding reflection transformations', '', 30, '2025-03-07 23:04:55'),
(198, 30, 'Rotations', 'Understanding rotation transformations', '', 45, '2025-03-07 23:04:55'),
(199, 30, 'Translations', 'Understanding translation transformations', '', 30, '2025-03-07 23:04:55'),
(200, 30, 'Enlargements', 'Understanding scaling transformations', '', 45, '2025-03-07 23:04:55'),
(201, 30, 'Congruence and Similarity', 'Identifying shapes with same form or proportions', '', 45, '2025-03-07 23:04:55'),
(202, 30, 'Review Exercise', 'Consolidated practice on transformations', '', 45, '2025-03-07 23:04:55'),
(203, 30, 'Exam-Style Questions', 'Practice with exam-style questions on transformations', '', 30, '2025-03-07 23:04:55'),
(204, 30, 'Past Exam Paper Practice', 'Practice with past exam questions on transformations', '', 60, '2025-03-07 23:04:55'),
(205, 31, 'Congruence and Similarity', 'Identifying and proving congruent and similar shapes', '', 45, '2025-03-07 23:04:55'),
(206, 31, 'Areas and Volumes of Similar Shapes', 'Scale factor relationships for area and volume', '', 60, '2025-03-07 23:04:55'),
(207, 31, 'Review Exercise', 'Consolidated practice on congruence and similarity', '', 45, '2025-03-07 23:04:55'),
(208, 31, 'Exam-Style Questions', 'Practice with exam-style questions on congruence and similarity', '', 45, '2025-03-07 23:04:55'),
(209, 31, 'Past Exam Paper Practice', 'Practice with past exam questions on congruence and similarity', '', 90, '2025-03-07 23:04:55'),
(210, 32, 'Using Different Types of Data', 'Understanding categorical, discrete, and continuous data', '', 30, '2025-03-07 23:04:55'),
(211, 32, 'Data Collection', 'Methods for gathering reliable data', '', 30, '2025-03-07 23:04:55'),
(212, 32, 'Sampling and Bias', 'Understanding sample selection and avoiding bias', '', 45, '2025-03-07 23:04:55'),
(213, 32, 'Review Exercise', 'Consolidated practice on data collection', '', 45, '2025-03-07 23:04:55'),
(214, 32, 'Exam-Style Questions', 'Practice with exam-style questions on data collection', '', 30, '2025-03-07 23:04:55'),
(215, 32, 'Past Exam Paper Practice', 'Practice with past exam questions on data collection', '', 60, '2025-03-07 23:04:55'),
(216, 33, 'Averages and Ranges', 'Understanding mean, median, mode, and range as measures of central tendency.', 'Medium', 30, '2025-03-09 17:38:53'),
(217, 33, 'Averages for Grouped Data', 'Calculating averages when data is grouped into intervals.', 'Medium', 35, '2025-03-09 17:38:53'),
(218, 33, 'Review Exercise', 'Practice questions on averages and ranges.', 'Medium', 40, '2025-03-09 17:38:53'),
(219, 33, 'Exam-Style Questions', 'Challenging exam-style problems for averages and ranges.', 'Medium', 45, '2025-03-09 17:38:53'),
(220, 33, 'Past Exam Paper Practice', 'Solving past exam questions on averages and ranges.', 'Medium', 50, '2025-03-09 17:38:53'),
(221, 34, 'Tables and Charts', 'Organizing and representing data using tables and various types of charts.', 'Medium', 25, '2025-03-09 17:38:53'),
(222, 34, 'Stem and Leaf Diagrams', 'Understanding and constructing stem-and-leaf plots.', 'Medium', 30, '2025-03-09 17:38:53'),
(223, 34, 'Frequency Polygons', 'Creating frequency polygons to display distributions.', 'Medium', 30, '2025-03-09 17:38:53'),
(224, 34, 'Histograms', 'Constructing and interpreting histograms.', 'Medium', 35, '2025-03-09 17:38:53'),
(225, 34, 'Cumulative Frequency Diagrams', 'Using cumulative frequency graphs to analyze data.', 'Medium', 40, '2025-03-09 17:38:53'),
(226, 34, 'Time Series', 'Identifying trends and patterns in time-series data.', 'Medium', 30, '2025-03-09 17:38:53'),
(227, 34, 'Scatter Graphs', 'Using scatter plots to explore relationships between variables.', 'Medium', 25, '2025-03-09 17:38:53'),
(228, 34, 'Appropriate Representation of Data', 'Choosing the best method to display different types of data.', 'Medium', 30, '2025-03-09 17:38:53'),
(229, 34, 'Review Exercise', 'Practice questions on data representation.', 'Medium', 40, '2025-03-09 17:38:53'),
(230, 34, 'Exam-Style Questions', 'Exam-style problems covering data representation.', 'Medium', 45, '2025-03-09 17:38:53'),
(231, 34, 'Past Exam Paper Practice', 'Solving past exam questions on data representation.', 'Medium', 50, '2025-03-09 17:38:53'),
(232, 35, 'Calculating Probabilities', 'Basic probability rules and calculations.', 'Medium', 30, '2025-03-09 17:38:53'),
(233, 35, 'Listing Outcomes', 'Using sample spaces to list all possible outcomes.', 'Medium', 25, '2025-03-09 17:38:53'),
(234, 35, 'Probability from Experiments', 'Experimental probability and relative frequency.', 'Medium', 35, '2025-03-09 17:38:53'),
(235, 35, 'Review Exercise', 'Practice problems on probability concepts.', 'Medium', 40, '2025-03-09 17:38:53'),
(236, 35, 'Exam-Style Questions', 'Challenging probability problems.', 'Medium', 45, '2025-03-09 17:38:53'),
(237, 35, 'Past Exam Paper Practice', 'Solving past probability exam questions.', 'Medium', 50, '2025-03-09 17:38:53'),
(238, 36, 'The AND Rule for Independent Events', 'Using the AND rule to calculate probabilities of independent events.', 'Medium', 30, '2025-03-09 17:38:53'),
(239, 36, 'The OR Rule', 'Applying the OR rule to find probabilities of mutually exclusive or non-mutually exclusive events.', 'Medium', 30, '2025-03-09 17:38:53'),
(240, 36, 'Using the AND/OR Rules', 'Combining probability rules to solve complex problems.', 'Medium', 35, '2025-03-09 17:38:53'),
(241, 36, 'Tree Diagrams', 'Using tree diagrams to visualize and calculate probabilities.', 'Medium', 40, '2025-03-09 17:38:53'),
(242, 36, 'Conditional Probability', 'Finding probabilities given specific conditions.', 'Medium', 45, '2025-03-09 17:38:53'),
(243, 36, 'Review Exercise', 'Practice questions on combined probability.', 'Medium', 40, '2025-03-09 17:38:53'),
(244, 36, 'Exam-Style Questions', 'Exam-level probability problems.', 'Medium', 45, '2025-03-09 17:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

DROP TABLE IF EXISTS `topics`;
CREATE TABLE IF NOT EXISTS `topics` (
  `topic_id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `year_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `topic_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `topic_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `order_number` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`topic_id`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_year_subject` (`year_level`,`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`topic_id`, `subject_id`, `year_level`, `topic_name`, `topic_description`, `order_number`, `created_at`) VALUES
(1, 1, 'Year 11', 'Linear Programming', 'Linear programming is a method to achieve the best outcome in a mathematical model whose requirements are represented by linear relationships.', 1, '2025-03-07 22:53:30'),
(2, 1, 'Year 11', 'Other Types of Graph', 'This includes various types of graphs such as bar graphs, histograms, and pie charts that represent data visually.', 2, '2025-03-07 22:53:30'),
(3, 1, 'Year 11', 'Functions', 'A function is a relation between a set of inputs and a set of possible outputs, where each input is related to exactly one output.', 3, '2025-03-07 22:53:30'),
(4, 1, 'Year 11', 'Pythagoras and Trigonometry', 'Pythagoras’ theorem relates the lengths of the sides in a right-angled triangle, and trigonometry deals with the relationships between the angles and sides of triangles.', 4, '2025-03-07 22:53:30'),
(5, 1, 'Year 11', 'Arithmetic, Multiples and Factors', 'Arithmetic involves basic operations like addition, subtraction, multiplication, and division. Multiples and factors are important concepts in number theory.', 5, '2025-03-07 22:53:30'),
(6, 1, 'Year 11', 'Approximations', 'Approximations are estimates of values that are close to but not exactly equal to the true value.', 6, '2025-03-07 22:53:30'),
(7, 1, 'Year 11', 'Fractions', 'A fraction represents a part of a whole or, more generally, any number of equal parts.', 7, '2025-03-07 22:53:30'),
(8, 1, 'Year 11', 'Ratio and Proportion', 'A ratio is a relationship between two numbers, and proportion is an equation stating that two ratios are equal.', 8, '2025-03-07 22:53:30'),
(9, 1, 'Year 11', 'Percentages', 'A percentage is a way of expressing a number as a fraction of 100.', 9, '2025-03-07 22:53:30'),
(10, 1, 'Year 11', 'Expressions', 'An expression is a combination of numbers, symbols, and operators (such as +, –, ×, ÷) that represents a value.', 10, '2025-03-07 22:53:30'),
(11, 1, 'Year 11', 'Powers and Roots', 'Powers involve multiplying a number by itself multiple times, while roots are the inverse operation, such as finding the square root.', 11, '2025-03-07 22:53:30'),
(12, 1, 'Year 11', 'Formulas', 'A formula is a mathematical rule expressed using symbols and variables to calculate a value.', 12, '2025-03-07 22:53:30'),
(13, 1, 'Year 11', 'Equations', 'An equation is a statement that two expressions are equal, often involving variables that need to be solved for.', 13, '2025-03-07 22:53:30'),
(14, 1, 'Year 11', 'Direct and Inverse Proportion', 'Direct proportion means as one quantity increases, the other also increases. Inverse proportion means as one quantity increases, the other decreases.', 14, '2025-03-07 22:53:30'),
(15, 1, 'Year 11', 'Quadratic Equations', 'Quadratic equations are second-order polynomial equations of the form ax² + bx + c = 0.', 15, '2025-03-07 22:53:30'),
(16, 1, 'Year 11', 'Simultaneous Equations', 'Simultaneous equations are a set of equations that are solved together, often involving multiple variables.', 16, '2025-03-07 22:53:30'),
(17, 1, 'Year 11', 'Inequalities', 'Inequalities represent relationships where one side is not necessarily equal to the other, but is greater than or less than.', 17, '2025-03-07 22:53:30'),
(18, 1, 'Year 11', 'Sequences', 'A sequence is an ordered list of numbers, and its terms follow a specific pattern or rule.', 18, '2025-03-07 22:53:30'),
(19, 1, 'Year 11', 'Straight-Line Graphs', 'Straight-line graphs represent linear relationships between two variables, typically in the form of y = mx + b.', 19, '2025-03-07 22:53:30'),
(20, 1, 'Year 11', 'Using Graphs', 'Graphs are used to visualize relationships between variables and help identify trends or patterns in data.', 20, '2025-03-07 22:53:30'),
(21, 1, 'Year 11', 'Sets', 'A set is a collection of distinct objects, considered as an object in its own right.', 21, '2025-03-07 22:53:30'),
(22, 1, 'Year 11', 'Angles and 2D Shapes', 'Angles and 2D shapes study the properties of flat shapes such as triangles, quadrilaterals, and circles, including angle relationships and symmetry.', 22, '2025-03-07 22:53:30'),
(23, 1, 'Year 11', 'Circle Geometry', 'Circle geometry focuses on the properties and theorems related to circles, such as tangents, secants, and arcs.', 23, '2025-03-07 22:53:30'),
(24, 1, 'Year 11', 'Units, Measuring and Estimating', 'This topic covers different units of measurement and methods for estimating quantities and dimensions.', 24, '2025-03-07 22:53:30'),
(25, 1, 'Year 11', 'Compound Measures', 'Compound measures involve two or more physical quantities, such as speed (distance/time), density (mass/volume), etc.', 25, '2025-03-07 22:53:30'),
(26, 1, 'Year 11', 'Constructions', 'Constructions are geometric drawings that use only a straightedge and a compass, following specific procedures.', 26, '2025-03-07 22:53:30'),
(27, 1, 'Year 11', 'Vectors', 'A vector represents a quantity with both magnitude and direction, used extensively in physics and mathematics.', 27, '2025-03-07 22:53:30'),
(28, 1, 'Year 11', 'Perimeter and Area', 'Perimeter is the distance around a shape, while area is the total space contained within a shape.', 28, '2025-03-07 22:53:30'),
(29, 1, 'Year 11', '3D Shapes', '3D shapes are objects with three dimensions: length, width, and height, such as cubes, spheres, and pyramids.', 29, '2025-03-07 22:53:30'),
(30, 1, 'Year 11', 'Transformations', 'Transformations refer to operations that alter the position, size, or orientation of a shape, including translations, rotations, and reflections.', 30, '2025-03-07 22:53:30'),
(31, 1, 'Year 11', 'Congruence and Similarity', 'Congruence refers to shapes that are identical in size and shape, while similarity refers to shapes that have the same shape but not necessarily the same size.', 31, '2025-03-07 22:53:30'),
(32, 1, 'Year 11', 'Collecting Data', 'Data collection involves gathering information to be used in statistical analysis or decision-making.', 32, '2025-03-07 22:53:30'),
(33, 1, 'Year 11', 'Averages and Ranges', 'Averages represent the central tendency of data, and range is the difference between the largest and smallest values in a set of data.', 33, '2025-03-07 22:53:30'),
(34, 1, 'Year 11', 'Displaying Data', 'Displaying data involves using visual representations such as bar charts, line graphs, and pie charts to convey information effectively.', 34, '2025-03-07 22:53:30'),
(35, 1, 'Year 11', 'Probability', 'Probability is the measure of how likely an event is to occur, ranging from 0 (impossible) to 1 (certain).', 35, '2025-03-07 22:53:30'),
(36, 1, 'Year 11', 'Probability for Combined Events', 'Probability for combined events involves calculating the likelihood of two or more events happening together, such as independent or dependent events.', 36, '2025-03-07 22:53:30'),
(37, 1, 'Year 10', 'Linear Programming', 'Introduction to linear programming and optimization techniques.', 1, '2025-03-09 17:52:21'),
(38, 1, 'Year 10', 'Other Types of Graph', 'Exploration of different types of graphs and their properties.', 2, '2025-03-09 17:52:21'),
(39, 1, 'Year 10', 'Functions', 'Understanding functions, their graphs, and applications.', 3, '2025-03-09 17:52:21'),
(40, 1, 'Year 10', 'Pythagoras and Trigonometry', 'Applying Pythagoras’ theorem and trigonometric ratios.', 4, '2025-03-09 17:52:21'),
(41, 1, 'Year 10', 'Arithmetic, Multiples and Factors', 'Number operations, finding multiples and factors.', 5, '2025-03-09 17:52:21'),
(42, 1, 'Year 10', 'Approximations', 'Rounding numbers, significant figures, and estimation.', 6, '2025-03-09 17:52:21'),
(43, 1, 'Year 10', 'Fractions', 'Operations with fractions, mixed numbers, and improper fractions.', 7, '2025-03-09 17:52:21'),
(44, 1, 'Year 10', 'Ratio and Proportion', 'Solving problems using ratios and proportions.', 8, '2025-03-09 17:52:21'),
(45, 1, 'Year 10', 'Percentages', 'Calculating percentages, percentage change, and real-life applications.', 9, '2025-03-09 17:52:21'),
(46, 1, 'Year 10', 'Expressions', 'Simplifying and manipulating algebraic expressions.', 10, '2025-03-09 17:52:21'),
(47, 1, 'Year 10', 'Powers and Roots', 'Laws of indices, square and cube roots.', 11, '2025-03-09 17:52:21'),
(48, 1, 'Year 10', 'Formulas', 'Writing and using formulas in problem-solving.', 12, '2025-03-09 17:52:21'),
(49, 1, 'Year 10', 'Equations', 'Solving linear, quadratic, and other types of equations.', 13, '2025-03-09 17:52:21'),
(50, 1, 'Year 10', 'Direct and Inverse Proportion', 'Understanding and solving problems on proportion.', 14, '2025-03-09 17:52:21'),
(51, 1, 'Year 10', 'Quadratic Equations', 'Solving quadratic equations by factorization, formula, and completing the square.', 15, '2025-03-09 17:52:21'),
(52, 1, 'Year 10', 'Simultaneous Equations', 'Solving simultaneous equations algebraically and graphically.', 16, '2025-03-09 17:52:21'),
(53, 1, 'Year 10', 'Inequalities', 'Solving and graphing inequalities.', 17, '2025-03-09 17:52:21'),
(54, 1, 'Year 10', 'Sequences', 'Understanding arithmetic and geometric sequences.', 18, '2025-03-09 17:52:21'),
(55, 1, 'Year 10', 'Straight-Line Graphs', 'Graphing linear equations and interpreting slopes.', 19, '2025-03-09 17:52:21'),
(56, 1, 'Year 10', 'Using Graphs', 'Using graphs to represent and interpret mathematical relationships.', 20, '2025-03-09 17:52:21');

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

DROP TABLE IF EXISTS `tutors`;
CREATE TABLE IF NOT EXISTS `tutors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`, `link`) VALUES
(1, 'Paul Naho', 'paul@bloom.com', '$2y$10$maFKYyb3pgQ0aawfdk6csORvXDbXdnFtVCzb8rUdWkgf3zW3wnSLO', '2025-03-03 17:29:05', '2025-03-03 18:44:58', 'https://meet.google.com/qbn-zfsj-zxa');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_topic_progress`
--
ALTER TABLE `student_topic_progress`
  ADD CONSTRAINT `student_topic_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_topic_progress_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_topic_progress_ibfk_3` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subtopics`
--
ALTER TABLE `subtopics`
  ADD CONSTRAINT `subtopics_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE;

--
-- Constraints for table `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
