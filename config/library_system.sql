-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2024 at 03:43 PM
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
  `Status` enum('available','unavailable','Available','Borrowed','Reserved') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`BookID`, `Title`, `Author`, `ISBN`, `Publisher`, `Edition`, `PublicationDate`, `Genre`, `Status`) VALUES
(78, '', 'Harper Lee', '978-0061120084', 'J.B. Lippincott & Co.', NULL, '1960-07-11', NULL, 'available'),
(79, '', 'Patrick M. Fitzpatrick', '978-0534376034', 'Brooks Cole', NULL, '2002-01-01', NULL, 'available'),
(80, '', 'American Psychological Association', '978-1433832176', 'APA Publishing', NULL, '2020-12-15', NULL, 'available'),
(81, '', 'Yuval Noah Harari', '978-0062316097', 'Harper', NULL, '2015-02-10', NULL, 'available'),
(124, '', 'adsad', 'aada', 'asdad', NULL, '2024-12-10', NULL, 'available'),
(158, '', '11', '11', '11', NULL, '2024-12-11', NULL, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_transactions`
--

CREATE TABLE `borrow_transactions` (
  `ID` int(11) NOT NULL,
  `BorrowerID` int(100) NOT NULL,
  `Borrower_first_name` varchar(255) NOT NULL,
  `Borrower_middle_name` varchar(255) DEFAULT NULL,
  `Borrower_last_name` varchar(255) NOT NULL,
  `Borrower_suffix` varchar(255) DEFAULT NULL,
  `ApproverID` int(11) NOT NULL,
  `Approver_first_name` varchar(255) NOT NULL,
  `Approver_middle_name` varchar(255) DEFAULT NULL,
  `Approver_last_name` varchar(255) NOT NULL,
  `Approver_suffix` varchar(255) DEFAULT NULL,
  `ResourceID` int(11) NOT NULL,
  `ResourceType` enum('Book','MediaResource','Periodical') NOT NULL,
  `AccessionNumber` varchar(50) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks borrowing transactions for library resources';

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `ID` int(11) NOT NULL,
  `BorrowTransactionID` int(11) NOT NULL,
  `BorrowerID` int(100) NOT NULL,
  `Borrower_first_name` varchar(255) NOT NULL,
  `Borrower_middle_name` varchar(255) DEFAULT NULL,
  `Borrower_last_name` varchar(255) NOT NULL,
  `Borrower_suffix` varchar(255) DEFAULT NULL,
  `ApproverID` int(100) DEFAULT NULL,
  `Approver_first_name` varchar(255) DEFAULT NULL,
  `Approver_middle_name` varchar(255) DEFAULT NULL,
  `Approver_last_name` varchar(255) DEFAULT NULL,
  `Approver_suffix` varchar(100) DEFAULT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `DateGenerated` date NOT NULL DEFAULT curdate(),
  `DatePaid` date DEFAULT NULL,
  `PaidStatus` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `ReceiptPrinted` enum('no','yes') NOT NULL DEFAULT 'no'
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
(78, 'To Kill a Mockingbird', 'B-2024-001', 'Fiction', 'Book', 'Checked Out'),
(79, 'Advanced Calculus', 'B-2024-002', 'Academic', 'Book', 'Available'),
(80, 'APA Handbook of Psychology', 'B-2024-003', 'Reference', 'Book', 'Available'),
(81, 'Sapiens: A Brief History of Humankind', 'B-2024-004', 'Non-Fiction', 'Book', 'Available'),
(102, 'Harry Potter and the Sorcerer\'s Stone', 'R-2024-001', 'AudioBook', 'MediaResource', 'Available'),
(107, 'The Shawshank Redemption', 'R-2024-002', 'Film', 'MediaResource', 'Available'),
(108, 'Thriller by Michael Jackson', 'R-2024-003', 'Music', 'MediaResource', 'Available'),
(109, 'The Godfather Trilogy', 'R-2024-004', 'Film', 'MediaResource', 'Available'),
(118, 'The Global Times', 'P-2024-001', 'Newspaper', 'Periodical', 'Available'),
(119, 'Tech Insider Weekly', 'P-2024-002', 'Newsletter', 'Periodical', 'Available'),
(120, 'Style & Design Magazine', 'P-2024-003', 'Magazine', 'Periodical', 'Available'),
(122, 'Corporate Insights Bulletin', 'P-2024-005', 'Bulletin', 'Periodical', 'Available'),
(123, 'Journal of Modern Economics', 'P-2024-004', 'Journal', 'Periodical', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `mediaresources`
--

CREATE TABLE `mediaresources` (
  `MediaResourceID` int(11) NOT NULL,
  `ResourceID` int(11) NOT NULL,
  `Format` varchar(50) DEFAULT NULL,
  `Runtime` varchar(50) DEFAULT NULL,
  `MediaType` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mediaresources`
--

INSERT INTO `mediaresources` (`MediaResourceID`, `ResourceID`, `Format`, `Runtime`, `MediaType`) VALUES
(32, 102, 'DVD', '8 hours 17 minutes', 'AudioBook'),
(33, 107, 'Digital', '142 minutes', 'Film'),
(34, 108, 'Blu-ray', '42 minutes', 'Music'),
(35, 109, 'VHS', '537 minutes', 'Film');

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
  `PublicationDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `periodicals`
--

INSERT INTO `periodicals` (`PeriodicalID`, `ResourceID`, `ISSN`, `Volume`, `Issue`, `PublicationDate`) VALUES
(14, 118, '1234-5678', '2024', '15', '2024-11-01'),
(15, 119, '9876-5432', '2024', '3', '2024-11-15'),
(16, 120, '5678-1234', '5', '12', '2024-10-25'),
(18, 122, '4444-5555', '2024', '2', '2024-07-15'),
(19, 123, '1122-3344', '42', '8', '2024-08-30');

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
  `user_type` enum('student','faculty','staff','admin') NOT NULL,
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
(17, 'admin', 'admin', 'admin', 'admin', 'admin@gmail.com', 'admin', NULL, '2001-06-14', 'Blck. 03', 'Prk. Lansang Village', 'Brgy. Sinawal', 'General Santos City', '09514810354', 'active', '1009147', '$2y$10$uAhLQsZI5HZG8U96WkJ0R.NC301KyTw2xbUz6LBAV6EWiFP1zvXvm', '2024-12-15 14:38:31', '2024-12-15 14:41:15');

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
  ADD PRIMARY KEY (`ID`),
  ADD KEY `borrower_id` (`BorrowerID`),
  ADD KEY `approver_id` (`ApproverID`),
  ADD KEY `accession_number` (`AccessionNumber`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `BorrowTransactionID` (`BorrowTransactionID`);

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
  MODIFY `BookID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `libraryresources`
--
ALTER TABLE `libraryresources`
  MODIFY `ResourceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `mediaresources`
--
ALTER TABLE `mediaresources`
  MODIFY `MediaResourceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `periodicals`
--
ALTER TABLE `periodicals`
  MODIFY `PeriodicalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `borrower_ibfk_1` FOREIGN KEY (`BorrowerID`) REFERENCES `borrow_transactions` (`BorrowerID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`BorrowTransactionID`) REFERENCES `borrow_transactions` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
