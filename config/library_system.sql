-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 25, 2024 at 04:34 PM
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
-- Database: `library_system`
--
CREATE DATABASE IF NOT EXISTS `library_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `library_system`;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `BookID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Author` varchar(255) NOT NULL,
  `ISBN` varchar(20) NOT NULL,
  `Publisher` varchar(255) DEFAULT NULL,
  `Edition` varchar(50) DEFAULT NULL,
  `PublicationDate` date DEFAULT NULL,
  `Genre` varchar(100) DEFAULT NULL,
  `Quantity` int(11) DEFAULT 0,
  `AvailableQuantity` int(11) DEFAULT 0,
  `Status` enum('available','unavailable','Available','Borrowed','Reserved') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`BookID`, `Title`, `Author`, `ISBN`, `Publisher`, `Edition`, `PublicationDate`, `Genre`, `Quantity`, `AvailableQuantity`, `Status`) VALUES
(72, '', 'osias', '54141', 'Shinokawa', NULL, '2010-02-16', NULL, 0, 0, 'available'),
(76, '', 'qwe', 'qwe', 'qew', NULL, '2024-11-25', NULL, 0, 0, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_transactions`
--

CREATE TABLE `borrow_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `late_fee` decimal(10,2) DEFAULT 0.00,
  `resource_type` enum('Book','MediaResource','Periodical') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_transactions`
--

INSERT INTO `borrow_transactions` (`id`, `user_id`, `resource_id`, `borrow_date`, `due_date`, `return_date`, `status`, `late_fee`, `resource_type`) VALUES
(7, 5, 72, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(8, 5, 73, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(9, 8, 74, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(10, 8, 72, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(11, 8, 72, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(12, 8, 72, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(13, 11, 73, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(14, 11, 74, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(15, 11, 72, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(16, 10, 72, '2024-11-25', '2024-12-09', NULL, 'borrowed', 0.00, 'Book'),
(17, 10, 73, '2024-11-25', '2024-12-09', NULL, 'borrowed', 0.00, 'Book'),
(18, 10, 74, '2024-11-25', '2024-12-09', NULL, 'borrowed', 0.00, 'Book'),
(19, 10, 76, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(20, 10, 77, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(21, 10, 75, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(22, 10, 76, '2024-11-25', '2024-12-09', '2024-11-25', 'returned', 0.00, 'Book'),
(23, 10, 76, '2024-11-10', '2024-11-24', '2024-11-25', 'returned', 100.00, 'Book'),
(25, 10, 76, '2024-11-25', '2024-12-09', NULL, 'borrowed', 100.00, 'Book'),
(26, 11, 77, '2024-11-25', '2024-12-09', NULL, 'overdue', 1600.00, 'Book');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `fine_amount` decimal(10,2) NOT NULL,
  `paid` enum('yes','no') DEFAULT 'no',
  `payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `InventoryID` int(11) NOT NULL,
  `ResourceID` int(11) NOT NULL,
  `AccessionNumber` varchar(50) NOT NULL,
  `Status` enum('Available','Borrowed','Reserved') DEFAULT 'Available',
  `BorrowerID` int(11) DEFAULT NULL,
  `DueDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `libraryresources`
--

CREATE TABLE `libraryresources` (
  `ResourceID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `AccessionNumber` varchar(50) NOT NULL,
  `Category` varchar(100) NOT NULL,
  `ResourceType` enum('Book','Periodical','MediaResource') NOT NULL,
  `AvailabilityStatus` enum('Available','Checked Out') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `libraryresources`
--

INSERT INTO `libraryresources` (`ResourceID`, `Title`, `AccessionNumber`, `Category`, `ResourceType`, `AvailabilityStatus`) VALUES
(75, '123', 'PER-2024-9674', 'Science', 'Periodical', 'Available'),
(76, 'qwe', 'B-2024-001', 'Fiction', 'Book', 'Checked Out'),
(77, 'asd', 'Med20240001', 'Film', 'MediaResource', 'Checked Out');

-- --------------------------------------------------------

--
-- Table structure for table `mediaresources`
--

CREATE TABLE `mediaresources` (
  `MediaResourceID` int(11) NOT NULL,
  `ResourceID` int(11) NOT NULL,
  `Format` varchar(50) DEFAULT NULL,
  `Runtime` int(11) DEFAULT NULL,
  `MediaType` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mediaresources`
--

INSERT INTO `mediaresources` (`MediaResourceID`, `ResourceID`, `Format`, `Runtime`, `MediaType`) VALUES
(13, 77, 'DVD', 0, 'Film');

-- --------------------------------------------------------

--
-- Table structure for table `periodicals`
--

CREATE TABLE `periodicals` (
  `PeriodicalID` int(11) NOT NULL,
  `ResourceID` int(11) NOT NULL,
  `ISSN` varchar(20) NOT NULL,
  `Volume` varchar(50) DEFAULT NULL,
  `Issue` varchar(50) DEFAULT NULL,
  `PublicationDate` date DEFAULT NULL,
  `Publisher` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `periodicals`
--

INSERT INTO `periodicals` (`PeriodicalID`, `ResourceID`, `ISSN`, `Volume`, `Issue`, `PublicationDate`, `Publisher`) VALUES
(6, 75, '123', '123', '123', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` enum('student','faculty','staff') NOT NULL,
  `borrow_limit` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `purok` varchar(255) DEFAULT NULL,
  `barangay` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `membership_id` varchar(7) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `user_type`, `borrow_limit`, `date_of_birth`, `street`, `purok`, `barangay`, `city`, `phone_number`, `status`, `membership_id`, `password`, `created_at`, `updated_at`) VALUES
(7, '123', '123', '123', '12312312', 'osiasrussell@gmail.com', 'staff', NULL, '0000-00-00', '312312', '231', '2312', '123', '123', 'active', '4008271', '$2y$10$yBdXGxBMN44WWyh3u6x6/.6n9MtJJJgyLFKrmiAd0YNKhiQG61USK', '2024-11-25 12:18:39', '2024-11-25 12:18:39'),
(8, 'Russell', '', 'Osias', '5645', '123@gmail.com', 'student', NULL, '2679-07-09', '46456', '446645', '65347', '774', '465645', 'active', '2001898', '$2y$10$owrwmNK/viPoHK912VNsyON2CS6pKb70C96qs..DzH1vX/PTgISgC', '2024-11-25 12:19:40', '2024-11-25 12:19:40'),
(9, 'Mark John', 'Rama', 'Jopia', '', 'mark@gmail.com', 'staff', NULL, '2001-06-14', 'Blck. 3', 'Prk. Lansang Village', 'Brgy. Sinawal', 'General Santos City', '09514810354', 'active', '4007136', '$2y$10$27ZLPTVkxRDVbvswGLokiOoR3UmT6PXG6jwgYSVUEKiyI3VA4qrDS', '2024-11-25 12:53:20', '2024-11-25 12:53:20'),
(10, 'Mark John', 'Rama', 'Jopia', '', 'markjohn@gmail.com', 'faculty', NULL, '2001-06-14', 'Blck. 3', 'Prk. Lansang Village', 'Brgy. Sinawal', 'General Santos City', '09514810354', 'active', '3007180', '$2y$10$toiLWBbJcBmCmPakXUBTGuHUbABSJTgcYJob1yfR7FoIKdPBepZyW', '2024-11-25 12:54:51', '2024-11-25 12:54:51'),
(11, 'Mark John', 'Rama', 'Jopia', '', 'markjohnjopia@gmail.com', 'student', NULL, '2001-06-14', 'Blck. 3', 'Prk. Lansang Village', 'Brgy. Sinawal', 'General Santos City', '09514810354', 'active', '2008485', '$2y$10$xUeP1OFw6bcCCJRT4D2MRO/BLAnEM757cfvO.miRmnGa2JoG9f.XS', '2024-11-25 12:55:45', '2024-11-25 12:55:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`BookID`),
  ADD UNIQUE KEY `ISBN` (`ISBN`);

--
-- Indexes for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `libraryresources`
--
ALTER TABLE `libraryresources`
  ADD PRIMARY KEY (`ResourceID`),
  ADD UNIQUE KEY `AccessionNumber` (`AccessionNumber`),
  ADD UNIQUE KEY `AccessionNumber_2` (`AccessionNumber`);

--
-- Indexes for table `mediaresources`
--
ALTER TABLE `mediaresources`
  ADD PRIMARY KEY (`MediaResourceID`),
  ADD KEY `ResourceID` (`ResourceID`);

--
-- Indexes for table `periodicals`
--
ALTER TABLE `periodicals`
  ADD PRIMARY KEY (`PeriodicalID`),
  ADD KEY `ResourceID` (`ResourceID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `membership_id` (`membership_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `BookID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `libraryresources`
--
ALTER TABLE `libraryresources`
  MODIFY `ResourceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `mediaresources`
--
ALTER TABLE `mediaresources`
  MODIFY `MediaResourceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `periodicals`
--
ALTER TABLE `periodicals`
  MODIFY `PeriodicalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `borrow_transactions` (`id`);

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `borrow_transactions` (`id`);

--
-- Constraints for table `mediaresources`
--
ALTER TABLE `mediaresources`
  ADD CONSTRAINT `mediaresources_ibfk_1` FOREIGN KEY (`ResourceID`) REFERENCES `libraryresources` (`ResourceID`) ON DELETE CASCADE;

--
-- Constraints for table `periodicals`
--
ALTER TABLE `periodicals`
  ADD CONSTRAINT `periodicals_ibfk_1` FOREIGN KEY (`ResourceID`) REFERENCES `libraryresources` (`ResourceID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
