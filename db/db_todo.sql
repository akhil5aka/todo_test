-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2024 at 06:27 PM
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
-- Database: `db_todo`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_todo_files`
--

CREATE TABLE `tb_todo_files` (
  `id` int(11) NOT NULL,
  `todo_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_todo_files`
--

INSERT INTO `tb_todo_files` (`id`, `todo_id`, `file_name`) VALUES
(7, 4, 'Screenshot_2024-08-20_210222_-_Copy2.png'),
(8, 4, 'Screenshot_2024-08-20_2102222.png'),
(9, 4, 'Screenshot_2024-08-20_2103062.png');

-- --------------------------------------------------------

--
-- Table structure for table `tb_to_list`
--

CREATE TABLE `tb_to_list` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `description` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_to_list`
--

INSERT INTO `tb_to_list` (`id`, `date`, `description`) VALUES
(1, '2024-09-20 00:00:00', 'sdfsd'),
(4, '2024-09-20 00:00:00', 'sdfkhsdjh');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `id` int(11) NOT NULL,
  `usr_name` varchar(200) NOT NULL,
  `password` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`id`, `usr_name`, `password`) VALUES
(1, 'AK', '54592d9d74c9a90049bdf3693bb2cc3db0334a02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_todo_files`
--
ALTER TABLE `tb_todo_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `todo_id` (`todo_id`);

--
-- Indexes for table `tb_to_list`
--
ALTER TABLE `tb_to_list`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_todo_files`
--
ALTER TABLE `tb_todo_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tb_to_list`
--
ALTER TABLE `tb_to_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_todo_files`
--
ALTER TABLE `tb_todo_files`
  ADD CONSTRAINT `tb_todo_files_ibfk_1` FOREIGN KEY (`todo_id`) REFERENCES `tb_to_list` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
