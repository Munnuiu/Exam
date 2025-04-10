-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 09:54 AM
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
-- Database: `muunu`
--

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `major_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `major_id`, `name`, `year`, `created_at`) VALUES
(1, 1, '422', 2024, '2025-04-04 03:32:33'),
(2, 3, '123', 2022, '2025-04-04 04:00:19'),
(3, 1, '342', 2025, '2025-04-04 04:10:41'),
(4, 4, '232', 2019, '2025-04-08 06:23:24');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'ingener'),
(11, 'angaahh'),
(12, 'Инженер');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `exam_name`, `teacher_id`, `created_at`, `description`, `start_time`, `end_time`) VALUES
(28, 'Монгол хэл', 15, '2025-03-27 06:17:14', 'Монгол үндэстний эрт эдүгээ цагийн хэл аялга, үсэг бичгийг хамтад нь Монгол хэл бичиг гэнэ. Түүнээс Монгол үндэстний эх хэлийг Монгол хэл (монгол бичгээр монггул хэлэ, тод монголоор монггол хэлэн) гэдэг', '2025-03-27 14:16:00', '2025-03-27 19:23:00'),
(29, 'Математик', 15, '2025-03-27 07:15:42', '<figure class=\"media\"><oembed url=\"https://youtu.be/C0SvkLwGmPg?si=6BLSXSvcWflDlbr1\"></oembed></figure>Математикийн тривиа гэж юу вэ? Математик нь сэтгэл хөдөлгөм байж болно, ялангуяа математикийн шалгалтын асуултууд Хэрэв та үүнийг зөв авч үзвэл', '2025-03-28 15:15:00', '2025-03-29 15:21:00'),
(30, 'mongol', 13, '2025-03-28 08:32:25', 'цуөыбыбөцуө &nbsp;', '2025-03-28 23:32:00', '2025-04-05 23:32:00'),
(31, 'йЫЁ', 13, '2025-03-28 08:32:47', 'ЙЫБЁЙ', '2025-03-28 23:32:00', '2025-04-01 23:32:00'),
(32, 'мум', 13, '2025-03-28 09:39:07', 'фцбфбфцб', '2025-03-29 00:39:00', '2025-04-01 00:39:00'),
(33, 'aefa', 13, '2025-03-28 09:56:48', 'afaw', '2025-03-29 00:56:00', '2025-04-04 00:56:00'),
(34, 'asdcas', 13, '2025-03-28 09:57:23', 'asdvasdv', '2025-03-29 00:57:00', '2025-04-04 00:57:00'),
(35, 'dedsd', 13, '2025-03-28 10:02:25', '<p><strong>dsfsergjhiyuuhbuyvi</strong></p>', '2025-03-28 01:02:00', '2025-03-29 01:02:00'),
(36, 'wdWA', 15, '2025-04-01 02:56:38', 'WDWQ', '2025-04-01 10:56:00', '2025-05-02 10:56:00'),
(37, 'web', 22, '2025-04-02 01:53:46', 'goy', '2025-04-02 09:53:00', '2025-04-03 09:53:00'),
(38, 'dsa', 15, '2025-04-02 02:23:57', 'sad', '2025-04-02 10:25:00', '2025-05-10 10:23:00'),
(39, 'Математик', 15, '2025-04-02 02:24:13', 'wqefw', '2025-04-02 10:24:00', '2025-04-11 10:24:00'),
(40, 'nono', 13, '2025-04-02 01:42:19', '<p>hha</p>', '2025-04-02 15:42:00', '2025-04-17 15:42:00'),
(41, 'asASCweqfwre', 13, '2025-04-02 01:45:37', '<p><i>ASCASDC</i></p>', '2025-04-02 15:45:00', '2025-04-16 15:45:00'),
(42, 'AXA', 15, '2025-04-03 08:15:02', 'axa', '2025-04-03 16:14:00', '2025-04-04 16:14:00'),
(43, 'web program', 25, '2025-04-03 08:22:22', '<p><i><strong>web program</strong></i></p>', '2025-04-03 16:22:00', '2025-04-04 16:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` float NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `exam_id`, `student_id`, `score`, `submitted_at`, `answer`) VALUES
(30, 29, 12, 0, '2025-03-28 11:58:23', '[]'),
(31, 28, 12, 0, '2025-03-27 08:46:58', '[]'),
(33, 36, 12, 0, '2025-04-02 01:51:26', '[]'),
(34, 36, 12, 0, '2025-04-02 01:51:26', '[]'),
(35, 36, 12, 0, '2025-04-02 01:51:26', '[]'),
(36, 36, 12, 0, '2025-04-02 01:51:26', '[]'),
(37, 36, 12, 0, '2025-04-02 01:51:26', '[]'),
(38, 36, 12, 66.6667, '2025-04-02 01:51:30', NULL),
(39, 36, 12, 66.6667, '2025-04-02 01:51:47', NULL),
(40, 36, 12, 0, '2025-04-02 01:52:03', NULL),
(41, 36, 12, 33.3333, '2025-04-02 01:52:17', NULL),
(42, 37, 12, 50, '2025-04-02 01:55:27', NULL),
(44, 36, 12, 100, '2025-04-02 07:43:43', NULL),
(45, 36, 12, 66.6667, '2025-04-03 07:14:08', NULL),
(47, 42, 12, 25, '2025-04-03 08:17:26', NULL),
(48, 43, 27, 75, '2025-04-03 08:40:41', NULL),
(49, 36, 27, 0, '2025-04-03 08:41:44', NULL),
(50, 36, 12, 33.3333, '2025-04-08 06:05:21', NULL),
(51, 36, 30, 66.6667, '2025-04-08 06:26:41', NULL),
(52, 38, 34, 0, '2025-04-09 07:03:26', NULL),
(53, 36, 12, 100, '2025-04-09 07:47:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `majors`
--

CREATE TABLE `majors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `majors`
--

INSERT INTO `majors` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'КПХ', 'IT', '2025-04-04 03:25:00'),
(3, 'Барилга', 'у', '2025-04-04 03:59:57'),
(4, 'gd', 'd', '2025-04-08 06:23:03');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `option1` varchar(255) DEFAULT NULL,
  `option2` varchar(255) DEFAULT NULL,
  `option3` varchar(255) DEFAULT NULL,
  `option4` varchar(255) DEFAULT NULL,
  `correct_option` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `option1`, `option2`, `option3`, `option4`, `correct_option`) VALUES
(22, 28, 'Монголчууд хаана дуулахыг цээрлэдэг вэ?', 'гэртээ', 'ууланд', 'усанд', 'орондоо', 3),
(24, 29, 'Математикийн тривиа гэж юу вэ? Математик нь сэтгэл хөдөлгөм байж болно, ялангуяа математикийн шалгалтын асуултууд Хэрэв та үүнийг зөв авч үзвэл. Мөн хүүхдүүд практик, сонирхолтой сургалтын үйл ажиллагаа, ажлын хуудас зэрэгт хамрагдсанаар илүү үр дүнтэй суралцдаг.', 'Зөв', '№-', '№₮', '№:', 1),
(25, 29, 'aeaSDA', 'ASDF', 'asdf', 'aa', 'aewfa', 4),
(26, 28, 'jhgviv', 'khjoui', 'kjhb', 'ih', 'uyouy', 3),
(27, 36, 'SAETAAWET', 'ERTE', 'ERT', 'ERA', 'ER', 1),
(28, 36, 'ERAWEARA', 'E', 'E', 'ERF', 'AE', 1),
(29, 36, ',EMJD', ';OQWER', 'QRTGQ34', 'QEWR4E', 'GEWEGRE', 3),
(30, 37, 'web gej yu we', 'hicheel', 'surlag', 'jawhaa', 'bayrhvv', 4),
(31, 37, 'bayrvv gej hen be', 'jawhaa', 'bayrhvv ym aa', 'deegii', 'choinym ymaa', 4),
(32, 40, 'nono gej hen be', 'bi', 'chi', 'nomuun', 'beb', 3),
(33, 42, 'aha gej yu we', 'we', 'we', 'ew', '2', 4),
(34, 42, 'erfew', 'werg', 'werg', 'erwg', 'erqg', 2),
(35, 42, 'ewrwergwer', 'ergwerg', 'ergwer', 'erg', 'werg', 2),
(36, 42, 'ertgetrg', 'ertert', 'ertert', 'rtert', 'ertgert', 1),
(37, 43, 'web gej yu we', 'localhost', 'online hutuch', 'web', 'medehgvie unay', 2),
(38, 43, 'Цэнхэр, шар өнгөний дундаас ямар өнгө гардаг вэ?', 'Ногоон', 'улаан', 'хар', 'шар', 1),
(39, 43, 'МУ-ын үндсэн хууль хэдэн бүлэгтэй вэ?', '2', '7', '4', '6', 4),
(40, 43, 'Монголын хамгийн сүүлчийн хааны хатан хэн бэ?  ', 'Дондогдулам', 'Мандухай', 'Хулан', 'Бөртэй', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) NOT NULL DEFAULT 'munu@gmail.com',
  `profile_picture` varchar(255) DEFAULT 'default.png',
  `class_id` int(11) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `Password`, `role`, `created_at`, `email`, `profile_picture`, `class_id`, `student_id`, `last_login`, `department_id`, `is_active`, `phone`) VALUES
(12, 'suragch', '$2y$10$9BVA8/tecefkEY0Po9zCIutrZOiLt/ukvEv9IB5.V5/9Q6tilduzS', 'student', '2025-03-06 04:17:52', 'delger@gmail.com', '395180845_845581543931019_4694667982808471389_n.jpg', 3, 'ed21d3254', NULL, NULL, 1, '80989400'),
(13, 'munnu', '$2y$10$4AofibwAxJlhHj5F4XYUeOcNvQDGFpqzni15hOQqUFa3ls/JUxed2', 'admin', '2025-03-06 04:21:57', 'delgermrnotgontgs@gmail.com', 'лого.avif', NULL, NULL, NULL, NULL, 1, '80989400'),
(15, 'munnu1', '$2y$10$gafp92pLbkWhOddFcHUkQuZCiMgYirixAGGcPxKYTAUTXsSkD5Dd6', 'teacher', '2025-03-10 05:46:48', 'munu@gmail.com', 'bi.jpg', NULL, NULL, NULL, 12, 1, '80989400'),
(22, 'jawhaa', '$2y$10$9uhQ6X17jjoBQDdb8EBbSOkYUWjnv/UHuvHjRrvREIFKz24lypTAe', 'teacher', '2025-04-02 01:52:53', 'jawhaa@gmail.com', 'Inter3-1.jpg', NULL, NULL, NULL, NULL, 1, '80989400'),
(25, 'Эрдэнэбаяр', '$2y$10$tswOIrh/S07IPL/9Dj0tWeiJWVAJ7N7idThIOC99NUXs.IRVI4zgy', 'teacher', '2025-04-03 08:20:29', 'erka03200@gmail.com', '67ee44e48adbb.jpg', NULL, NULL, NULL, 1, 1, NULL),
(26, 'Амартайван', '$2y$10$mXxd6pIhSDkUYroZ9jOqZu/CUvgxCi5/DbwnKpyYsF99iSvsen9Bq', 'teacher', '2025-04-03 08:21:26', 'amar@gmail.com', '67ee4515e5822.jpg', NULL, NULL, NULL, 12, 1, NULL),
(27, 'ganhuyg', '$2y$10$ne63C0srMSwe6InZd/KRuutlH9B.2jXgq19CiYxEVRiO6xffgiXpC', 'student', '2025-04-03 08:39:37', 'gan@gmail.com', 'Screenshot 2024-12-17 220219 1.png', 2, 'ed21d3254', NULL, NULL, 1, NULL),
(28, 'Баярхүү', '$2y$10$n8hi.nLcjHBT/cnbJvl3iOZWkkLOhUcw1m1SZW8F6eJVk2JTfEN62', 'student', '2025-04-08 03:14:31', 'baku@gmail.com', 'default.png', 1, '3wr', NULL, NULL, 1, NULL),
(29, 'sdf', '$2y$10$FOUaQFrkK1lMZ0Vx6/7MuOCzkkqMYZwDtHCu0aEqxPyyV1pWyvKti', 'teacher', '2025-04-08 06:25:32', 'de@gmail.com', 'default.png', NULL, NULL, NULL, NULL, 1, NULL),
(30, 'weq', '$2y$10$ow.1NNL7DvySz4F43PG2OeSmDDjfCWbRLFJH54ncZqwUfSzGnzMG.', 'student', '2025-04-08 06:25:48', 'del@gmail.com', 'default.png', NULL, NULL, NULL, NULL, 1, NULL),
(31, 'munnu iu', '$2y$10$GFh0vS1OAXPsi7F4Riocx.H7.i2FR03/1rXKA6.shr/09/k.4wf9e', 'teacher', '2025-04-09 03:41:45', 'user1@gmail.com', '67f5f40c5118b.png', NULL, NULL, NULL, 12, 1, '12345234'),
(32, 'munnu iu', '$2y$10$jS8MubLvDuio3rgfHSXUyeQFq5.QbKqrA7U.yiWNUbKtLrqVUjfEK', 'student', '2025-04-09 04:42:58', 'delger@gmail.com', 'default.png', NULL, NULL, NULL, NULL, 1, '80989400'),
(33, 'munnu iu', '$2y$10$7oNGhopyxwSe0IJbLfZKEujbGvZw850S2pLEqxc17ixr68SvK5yfO', 'teacher', '2025-04-09 06:02:43', 'test@example.com', 'default.png', NULL, NULL, NULL, 1, 1, '80989400'),
(34, 'Өсөхбаяр', '$2y$10$Sjk1uq2wt1hw.i3HTKNDqe4rca7wo1hy2h9q/0KfhCIcrAPamVm6.', 'student', '2025-04-09 07:01:36', 'ubuynbat@gmail.com', 'Screenshot_2024-11-30_155431-removebg-preview.png', 1, NULL, NULL, NULL, 1, '89370128');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `major_id` (`major_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `majors`
--
ALTER TABLE `majors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `majors`
--
ALTER TABLE `majors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`major_id`) REFERENCES `majors` (`id`);

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
