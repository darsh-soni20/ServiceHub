-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 31, 2026 at 07:26 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `servicemaster`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminid` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminid`, `username`, `password`) VALUES
(1, 'admin@servicehub.com', 'Admin@123');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bookingid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `providerid` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` enum('pending','confirmed','in-progress','completed','cancelled','canceled','declined') DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `paymentmode` enum('cash','online') NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bookingid`, `userid`, `providerid`, `serviceid`, `date`, `time`, `status`, `description`, `paymentmode`, `amount`) VALUES
(1, 1, 1, 1, '2026-07-13', '10:00:00', 'confirmed', 'Regular deep cleaning needed', 'online', 1500.00),
(3, 1, 2, 3, '2026-07-18', '11:00:00', 'confirmed', 'Fan installation', 'online', 600.00),
(4, 1, 1, 1, '2026-07-25', '10:00:00', 'declined', 'Deep cleaning needed\nAddress: 123 Test Street, Mumbai', 'cash', 1500.00),
(8, 3, 1, 1, '2026-07-28', '10:00:00', 'declined', 'Test\nSlot: Morning (9:00 AM - 12:00 PM)\nAddress: 123 Test St', 'cash', 499.00),
(10, 1, 1, 1, '2026-07-29', '16:00:00', 'confirmed', '\nSlot: Evening (3:00 PM - 6:00 PM)\nAddress: vjvjvjlvlvvvi', 'cash', 79.00),
(11, 4, 1, 1, '2026-08-01', '13:00:00', 'declined', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: wdkflw;dc,weiodklwfmcfelfv', 'online', 59.00),
(12, 5, 1, 1, '2026-07-31', '13:00:00', 'confirmed', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: ksdlc;msdc;odklmfv elkfgjeg.v', 'online', 59.00),
(13, 5, 1, 1, '2026-08-06', '13:00:00', 'confirmed', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: dfqwedcsdfergrtg', 'online', 99.00),
(14, 5, 3, 1, '2026-07-31', '13:00:00', 'confirmed', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: swocpkafmcpeofvkegj', 'online', 99.00),
(15, 4, 2, 1, '2026-08-19', '13:00:00', 'declined', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: dlaejfwd\'mdfewfd', 'online', 129.00),
(16, 5, 3, 1, '2026-08-12', '16:00:00', 'confirmed', '\nSlot: Evening (3:00 PM - 6:00 PM)\nAddress: ;lsaf\'wkfme;okvtrgnrogkemv j;gkr', 'cash', 129.00),
(17, 5, 3, 1, '2026-07-30', '13:00:00', 'declined', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: svklmv, vkl\'ge;lcw.d,f', 'online', 249.00),
(18, 5, 3, 1, '2026-08-04', '13:00:00', 'pending', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: doc;fhkjwrnfm\'orwvlkgmbthnpkl', 'online', 199.00),
(19, 4, 2, 1, '2026-08-05', '13:00:00', 'confirmed', '\nSlot: Afternoon (12:00 PM - 3:00 PM)\nAddress: weldf;ckw,dd;lec, dwmkccd', 'cash', 3549.00);

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `providerid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `category` varchar(50) NOT NULL,
  `experience` int(11) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `pincode` varchar(100) NOT NULL,
  `document` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`providerid`, `name`, `email`, `phone`, `category`, `experience`, `address`, `city`, `pincode`, `document`, `status`, `password`) VALUES
(1, 'Ravi Kumar', 'ravi.kumar@email.com', '897654321', 'Electrical', 5, '123 Main Street', 'Mumbai', '400001', 'doc_ravi.pdf', 'Active', 'Provider@123'),
(2, 'Suresh Sharma', 'sureshsharma@email.com', '8765432109', 'Electrical', 8, 'Andheri West', 'Mumbai', '400053', 'doc_suresh.pdf', 'Active', 'Provider@123'),
(3, 'Anil Singh', 'anil.plumb@email.com', '7654321098', 'Plumbing', 6, 'Dadar East', 'Mumbai', '400014', 'doc_anil.pdf', 'Active', 'Provider@123');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `reviewid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `providerid` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `reviews` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`reviewid`, `userid`, `providerid`, `rating`, `reviews`) VALUES
(1, 1, 1, 5, 'Excellent service! The team was very professional and thorough.');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `serviceid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`serviceid`, `name`, `description`) VALUES
(1, 'Home Cleaning', 'Professional home cleaning services including deep cleaning, regular cleaning, and move-in/move-out cleaning.'),
(2, 'Plumbing', 'Pipe repair, installation, leak fixing, drain cleaning, and all plumbing maintenance services.'),
(3, 'Electrical', 'Wiring, repairs, installations, switchboard fixing, and electrical maintenance.'),
(5, 'AC Repair', 'AC installation, repair, gas refilling, servicing, and annual maintenance contracts.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `pincode` varchar(100) NOT NULL,
  `password` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userid`, `name`, `email`, `phone`, `address`, `city`, `pincode`, `password`) VALUES
(1, 'Rahul Kumar', 'rahul@example.com', '9111122222', 'Flat 101, Sunshine Apartments', 'Mumbai', '400001', 'Customer@123'),
(2, 'Test User', 'test_1784869152@example.com', '9999999999', 'Test Address', 'Test City', '123456', 'Test@123'),
(3, 'Test User', 'test@example.com', '9876543210', '123 Test St', 'Pending', '000000', '$2y$10$x9tKj1lwYilDPKXtpeiPz.Di5Aah10/hjHj5ZsDYR.ma0nr/sWW5.'),
(4, 'samarth', 'sam@gmail.com', '1234567890', 'wdkflw;dc,weiodklwfmcfelfv', 'Pending', '000000', '$2y$10$eUJwOrDgBCVDphUKVsZTSO3DQKhY3VcGWa3AXTcbbxMw5nNhEHP7y'),
(5, 'Sam Patel', 'sam1@gmail.com', '0987654321', 'sadaksdlfevkrlgj b', 'Rajkot', '360004', 'sam@1811');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminid`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bookingid`),
  ADD KEY `userid` (`userid`),
  ADD KEY `providerid` (`providerid`),
  ADD KEY `serviceid` (`serviceid`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`providerid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`reviewid`),
  ADD KEY `userid` (`userid`),
  ADD KEY `providerid` (`providerid`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`serviceid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bookingid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `providerid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `reviewid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `serviceid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`providerid`) REFERENCES `providers` (`providerid`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_3` FOREIGN KEY (`serviceid`) REFERENCES `services` (`serviceid`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`providerid`) REFERENCES `providers` (`providerid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
