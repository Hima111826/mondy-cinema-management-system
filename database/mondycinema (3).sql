-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 11, 2025 at 08:54 AM
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
-- Database: `mondycinema`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `seed_seats_all`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `seed_seats_all` (IN `p_rows` INT, IN `p_per_row` INT)   BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE v_sid INT;
  DECLARE cur CURSOR FOR SELECT id FROM showtimes;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO v_sid;
    IF done THEN
      LEAVE read_loop;
    END IF;
    CALL seed_seats_for_showtime(v_sid, p_rows, p_per_row);
  END LOOP;
  CLOSE cur;
END$$

DROP PROCEDURE IF EXISTS `seed_seats_for_showtime`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `seed_seats_for_showtime` (IN `p_showtime_id` INT, IN `p_rows` INT, IN `p_per_row` INT)   BEGIN
  DECLARE r INT DEFAULT 1;
  DECLARE c INT;
  WHILE r <= p_rows DO
    SET c = 1;
    WHILE c <= p_per_row DO
      INSERT IGNORE INTO seats(showtime_id, seat_no)
      VALUES (p_showtime_id, CONCAT(CHAR(64 + r), c));
      SET c = c + 1;
    END WHILE;
    SET r = r + 1;
  END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(5, 'mondycinema@gmail.com', '$2y$10$3/c29slqUJhAgWKdeFyQTuac1pcbjzSSUwtNx/aDk88NP.8soHbJC', '2025-08-25 15:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `showtime_id` int NOT NULL,
  `qty` int NOT NULL DEFAULT '0',
  `status` enum('pending','confirmed','rejected','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `processed_by_admin_id` int DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bookings_user` (`user_id`),
  KEY `idx_bookings_showtime` (`showtime_id`),
  KEY `idx_bookings_status` (`status`),
  KEY `fk_bookings_admin` (`processed_by_admin_id`),
  KEY `idx_bookings_user_created` (`user_id`,`created_at`)
) ;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `showtime_id`, `qty`, `status`, `total_price`, `processed_by_admin_id`, `decided_at`, `cancel_reason`, `updated_at`, `created_at`) VALUES
(7, 1, 5, 1, 'cancelled', 1200.00, NULL, '2025-08-25 16:03:58', NULL, '2025-08-25 16:03:58', '2025-08-24 04:10:17'),
(8, 1, 5, 2, 'cancelled', 2400.00, NULL, '2025-08-24 13:18:37', NULL, '2025-08-24 13:18:37', '2025-08-24 04:10:43'),
(9, 1, 6, 2, 'confirmed', 2000.00, NULL, '2025-08-25 15:58:12', NULL, '2025-08-25 15:58:12', '2025-08-25 15:57:37'),
(11, 2, 6, 3, 'confirmed', 3000.00, NULL, '2025-08-25 16:20:25', NULL, '2025-08-25 16:20:25', '2025-08-25 16:18:40'),
(12, 5, 6, 1, 'confirmed', 1000.00, NULL, '2025-08-25 16:28:47', NULL, '2025-08-25 16:28:47', '2025-08-25 16:28:07'),
(13, 1, 6, 1, 'confirmed', 1000.00, NULL, '2025-08-26 05:02:59', NULL, '2025-08-26 05:02:59', '2025-08-26 05:01:16'),
(14, 1, 6, 1, 'confirmed', 1000.00, NULL, '2025-08-26 05:28:12', NULL, '2025-08-26 05:28:12', '2025-08-26 05:27:51'),
(15, 5, 6, 1, 'confirmed', 1000.00, NULL, '2025-08-26 06:19:50', NULL, '2025-08-26 06:19:50', '2025-08-26 06:19:39'),
(16, 5, 6, 1, 'confirmed', 1000.00, NULL, '2025-08-26 06:39:28', NULL, '2025-08-26 06:39:28', '2025-08-26 06:39:18');

--
-- Triggers `bookings`
--
DROP TRIGGER IF EXISTS `trg_bookings_au_status`;
DELIMITER $$
CREATE TRIGGER `trg_bookings_au_status` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
  IF NEW.status IN ('cancelled','rejected') AND OLD.status NOT IN ('cancelled','rejected') THEN
    DELETE FROM booking_seats WHERE booking_id = NEW.id;
  END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_bookings_bu_status`;
DELIMITER $$
CREATE TRIGGER `trg_bookings_bu_status` BEFORE UPDATE ON `bookings` FOR EACH ROW BEGIN
  IF NEW.status <> OLD.status THEN
    SET NEW.decided_at = CURRENT_TIMESTAMP;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `booking_seats`
--

DROP TABLE IF EXISTS `booking_seats`;
CREATE TABLE IF NOT EXISTS `booking_seats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `seat_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_seat` (`booking_id`,`seat_id`),
  UNIQUE KEY `uq_booking_seats_seat` (`seat_id`),
  KEY `idx_booking_seats_booking` (`booking_id`),
  KEY `idx_booking_seats_seat` (`seat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `booking_seats`
--
DROP TRIGGER IF EXISTS `trg_booking_seats_ad`;
DELIMITER $$
CREATE TRIGGER `trg_booking_seats_ad` AFTER DELETE ON `booking_seats` FOR EACH ROW BEGIN
  DECLARE v_cnt INT;
  DECLARE v_price DECIMAL(10,2);

  -- Free the seat
  UPDATE seats SET is_booked = 0 WHERE id = OLD.seat_id;

  -- Recompute qty and total for the affected booking
  SELECT COUNT(*)
    INTO v_cnt
  FROM booking_seats
  WHERE booking_id = OLD.booking_id;

  SELECT st.ticket_price
    INTO v_price
  FROM bookings b
  JOIN showtimes st ON st.id = b.showtime_id
  WHERE b.id = OLD.booking_id;

  UPDATE bookings
    SET qty = v_cnt,
        total_price = v_cnt * IFNULL(v_price, 0)
  WHERE id = OLD.booking_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_booking_seats_ai`;
DELIMITER $$
CREATE TRIGGER `trg_booking_seats_ai` AFTER INSERT ON `booking_seats` FOR EACH ROW BEGIN
  DECLARE v_cnt INT;
  DECLARE v_price DECIMAL(10,2);
  -- Lock the seat
  UPDATE seats SET is_booked = 1 WHERE id = NEW.seat_id;

  -- Recompute qty and total for the affected booking
  SELECT COUNT(*)
    INTO v_cnt
  FROM booking_seats
  WHERE booking_id = NEW.booking_id;

  SELECT st.ticket_price
    INTO v_price
  FROM bookings b
  JOIN showtimes st ON st.id = b.showtime_id
  WHERE b.id = NEW.booking_id;

  UPDATE bookings
    SET qty = v_cnt,
        total_price = v_cnt * IFNULL(v_price, 0)
  WHERE id = NEW.booking_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `booking_id` int DEFAULT NULL,
  `payment_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_admin` (`is_admin`),
  KEY `booking_id` (`booking_id`),
  KEY `payment_id` (`payment_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `is_read`, `is_admin`, `booking_id`, `payment_id`, `created_at`) VALUES
(6, 5, 'renuka', 'renuka02@gmail.com', 'Payment received: #6 for booking #15', 'User renuka (renuka02@gmail.com) paid Rs. 1,000.00 for booking #15 (movie: Kandy Nights, showtime: Fri, 29 Aug 2025 03:56 PM).', 1, 0, NULL, NULL, '2025-08-26 11:50:43'),
(7, 5, 'renuka', 'renuka02@gmail.com', NULL, 'can you receive me show times', 1, 0, NULL, NULL, '2025-08-26 11:51:23'),
(10, 5, 'renuka', 'renuka02@gmail.com', 'Payment received: #7 for booking #16', 'User renuka (renuka02@gmail.com) paid Rs. 1,000.00 for booking #16 (movie: Kandy Nights, showtime: Fri, 29 Aug 2025 03:56 PM).', 1, 0, NULL, NULL, '2025-08-26 12:09:59'),
(11, 5, 'renuka', 'renuka02@gmaqil.com', NULL, 'hii', 1, 0, NULL, NULL, '2025-08-26 12:10:27');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

DROP TABLE IF EXISTS `movies`;
CREATE TABLE IF NOT EXISTS `movies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genre` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_min` int DEFAULT NULL,
  `rating` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `poster_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `release_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `genre`, `duration_min`, `rating`, `description`, `poster_url`, `created_at`, `updated_at`, `release_date`) VALUES
(1, 'The Galactic Voyage', 'Sci-Fi', 130, 'PG-13', 'An epic journey through the stars.', '/assets/img/placeholder.jpg', '2025-08-20 10:21:30', '2025-08-24 12:37:40', NULL),
(2, 'Kandy Twist', 'Drama', 102, 'PG', 'A heartwarming tale set in Kandy.', '/assets/img/placeholder.jpg', '2025-08-20 10:21:30', '2025-08-26 13:14:44', NULL),
(3, 'Maargan', 'Sci-Fi', 120, 'PG-13', 'Maargan is an intense and engaging film that masterfully blends suspense and mystery.', NULL, '2025-08-26 12:34:27', '2025-08-26 12:34:27', '2025-08-30'),
(4, 'Until Down', 'Sci-Fi', 145, 'PG-13', 'A group of friends trapped in a time loop, where mysterious foes chase and kill them in gruesome ways, must survive until dawn to escape it.', NULL, '2025-08-26 12:46:24', '2025-08-26 12:46:24', '2025-09-05');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notifications_booking` (`booking_id`),
  KEY `idx_notifications_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('cash','card','online') COLLATE utf8mb4_unicode_ci DEFAULT 'online',
  `status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `method`, `status`, `paid_at`) VALUES
(5, 12, 1000.00, 'card', 'paid', '2025-08-26 05:47:48'),
(6, 15, 1000.00, 'card', 'paid', '2025-08-26 06:20:43'),
(7, 16, 1000.00, 'card', 'paid', '2025-08-26 06:39:59');

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

DROP TABLE IF EXISTS `seats`;
CREATE TABLE IF NOT EXISTS `seats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `showtime_id` int NOT NULL,
  `seat_no` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_booked` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_showtime_seat` (`showtime_id`,`seat_no`),
  KEY `idx_seats_showtime` (`showtime_id`)
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`id`, `showtime_id`, `seat_no`, `is_booked`) VALUES
(1, 1, 'A1', 0),
(2, 1, 'A2', 0),
(3, 1, 'A3', 0),
(4, 1, 'A4', 0),
(5, 1, 'A5', 0),
(6, 1, 'A6', 0),
(7, 1, 'A7', 0),
(8, 1, 'A8', 0),
(9, 1, 'A9', 0),
(10, 1, 'A10', 0),
(11, 1, 'B1', 0),
(12, 1, 'B2', 0),
(13, 1, 'B3', 0),
(14, 1, 'B4', 0),
(15, 1, 'B5', 0),
(16, 1, 'B6', 0),
(17, 1, 'B7', 0),
(18, 1, 'B8', 0),
(19, 1, 'B9', 0),
(20, 1, 'B10', 0),
(21, 1, 'C1', 0),
(22, 1, 'C2', 0),
(23, 1, 'C3', 0),
(24, 1, 'C4', 0),
(25, 1, 'C5', 0),
(26, 1, 'C6', 0),
(27, 1, 'C7', 0),
(28, 1, 'C8', 0),
(29, 1, 'C9', 0),
(30, 1, 'C10', 0),
(31, 1, 'D1', 0),
(32, 1, 'D2', 0),
(33, 1, 'D3', 0),
(34, 1, 'D4', 0),
(35, 1, 'D5', 0),
(36, 1, 'D6', 0),
(37, 1, 'D7', 0),
(38, 1, 'D8', 0),
(39, 1, 'D9', 0),
(40, 1, 'D10', 0),
(41, 1, 'E1', 0),
(42, 1, 'E2', 0),
(43, 1, 'E3', 0),
(44, 1, 'E4', 0),
(45, 1, 'E5', 0),
(46, 1, 'E6', 0),
(47, 1, 'E7', 0),
(48, 1, 'E8', 0),
(49, 1, 'E9', 0),
(50, 1, 'E10', 0),
(51, 2, 'A1', 0),
(52, 2, 'A2', 0),
(53, 2, 'A3', 0),
(54, 2, 'A4', 0),
(55, 2, 'A5', 0),
(56, 2, 'A6', 0),
(57, 2, 'A7', 0),
(58, 2, 'A8', 0),
(59, 2, 'A9', 0),
(60, 2, 'A10', 0),
(61, 2, 'B1', 0),
(62, 2, 'B2', 0),
(63, 2, 'B3', 0),
(64, 2, 'B4', 0),
(65, 2, 'B5', 0),
(66, 2, 'B6', 0),
(67, 2, 'B7', 0),
(68, 2, 'B8', 0),
(69, 2, 'B9', 0),
(70, 2, 'B10', 0),
(71, 2, 'C1', 0),
(72, 2, 'C2', 0),
(73, 2, 'C3', 0),
(74, 2, 'C4', 0),
(75, 2, 'C5', 0),
(76, 2, 'C6', 0),
(77, 2, 'C7', 0),
(78, 2, 'C8', 0),
(79, 2, 'C9', 0),
(80, 2, 'C10', 0),
(81, 2, 'D1', 0),
(82, 2, 'D2', 0),
(83, 2, 'D3', 0),
(84, 2, 'D4', 0),
(85, 2, 'D5', 0),
(86, 2, 'D6', 0),
(87, 2, 'D7', 0),
(88, 2, 'D8', 0),
(89, 2, 'D9', 0),
(90, 2, 'D10', 0),
(91, 2, 'E1', 0),
(92, 2, 'E2', 0),
(93, 2, 'E3', 0),
(94, 2, 'E4', 0),
(95, 2, 'E5', 0),
(96, 2, 'E6', 0),
(97, 2, 'E7', 0),
(98, 2, 'E8', 0),
(99, 2, 'E9', 0),
(100, 2, 'E10', 0),
(101, 3, 'A1', 0),
(102, 3, 'A2', 0),
(103, 3, 'A3', 0),
(104, 3, 'A4', 0),
(105, 3, 'A5', 0),
(106, 3, 'A6', 0),
(107, 3, 'A7', 0),
(108, 3, 'A8', 0),
(109, 3, 'A9', 0),
(110, 3, 'A10', 0),
(111, 3, 'B1', 0),
(112, 3, 'B2', 0),
(113, 3, 'B3', 0),
(114, 3, 'B4', 0),
(115, 3, 'B5', 0),
(116, 3, 'B6', 0),
(117, 3, 'B7', 0),
(118, 3, 'B8', 0),
(119, 3, 'B9', 0),
(120, 3, 'B10', 0),
(121, 3, 'C1', 0),
(122, 3, 'C2', 0),
(123, 3, 'C3', 0),
(124, 3, 'C4', 0),
(125, 3, 'C5', 0),
(126, 3, 'C6', 0),
(127, 3, 'C7', 0),
(128, 3, 'C8', 0),
(129, 3, 'C9', 0),
(130, 3, 'C10', 0),
(131, 3, 'D1', 0),
(132, 3, 'D2', 0),
(133, 3, 'D3', 0),
(134, 3, 'D4', 0),
(135, 3, 'D5', 0),
(136, 3, 'D6', 0),
(137, 3, 'D7', 0),
(138, 3, 'D8', 0),
(139, 3, 'D9', 0),
(140, 3, 'D10', 0),
(141, 3, 'E1', 0),
(142, 3, 'E2', 0),
(143, 3, 'E3', 0),
(144, 3, 'E4', 0),
(145, 3, 'E5', 0),
(146, 3, 'E6', 0),
(147, 3, 'E7', 0),
(148, 3, 'E8', 0),
(149, 3, 'E9', 0),
(150, 3, 'E10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cinema_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_limit` int DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `cinema_name`, `contact_email`, `booking_limit`, `created_at`) VALUES
(1, 'MondyCinema', 'admin@mondycinema.com', 5, '2025-08-21 09:50:25');

-- --------------------------------------------------------

--
-- Table structure for table `showtimes`
--

DROP TABLE IF EXISTS `showtimes`;
CREATE TABLE IF NOT EXISTS `showtimes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `movie_id` int NOT NULL,
  `date_time` datetime NOT NULL,
  `screen` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket_price` decimal(10,2) DEFAULT '0.00',
  `total_seats` int DEFAULT '50',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_showtimes_movie` (`movie_id`),
  KEY `idx_showtimes_datetime` (`date_time`),
  KEY `idx_showtimes_movie_datetime` (`movie_id`,`date_time`)
) ;

--
-- Dumping data for table `showtimes`
--

INSERT INTO `showtimes` (`id`, `movie_id`, `date_time`, `screen`, `ticket_price`, `total_seats`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-08-21 15:52:08', 'Screen 1', 1200.00, 50, '2025-08-20 10:22:08', '2025-08-21 15:33:25'),
(2, 1, '2025-08-22 15:52:08', 'Screen 1', 1200.00, 50, '2025-08-20 10:22:08', '2025-08-21 15:33:25'),
(3, 2, '2025-08-21 15:52:08', 'Screen 2', 1000.00, 40, '2025-08-20 10:22:08', '2025-08-21 15:33:25'),
(4, 2, '2025-08-23 04:20:09', '2', 1200.00, 50, '2025-08-23 04:20:41', '2025-08-23 04:20:41'),
(5, 1, '2025-08-25 19:30:12', 'screen 1', 1200.00, 50, '2025-08-23 04:30:52', '2025-08-23 04:30:52'),
(6, 2, '2025-08-29 15:56:29', 'screen 2', 1000.00, 50, '2025-08-25 15:56:56', '2025-08-25 15:56:56'),
(8, 4, '2025-08-28 18:37:18', 'screen 2', 1000.00, 50, '2025-08-26 18:37:45', '2025-08-26 18:37:45'),
(9, 3, '2025-08-29 18:38:55', 'screen 1', 1200.00, 50, '2025-08-26 18:39:33', '2025-08-26 18:39:33'),
(10, 3, '2025-09-20 05:31:10', 'screen 1', 1000.00, 50, '2025-09-11 05:31:44', '2025-09-11 05:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'Sasitha Sadaruwan Kumarathunge', 'sasithasadaruwan01@gmail.com', '$2y$10$Ugu/dKMfApEeLzgC9yipSuBwB7tRSTotr/iDuRA2fWjDn0pgLuZT.', '0759227925', '2025-08-22 15:44:57', '2025-08-23 05:11:44'),
(2, 'renuka', 'renuka@gmail.com', '$2y$10$KGa4tjYm8N7F4i3mpjrTIO2Zi1s7DPux46fcM0XDUd.IG2SWDnhDC', '0719180006', '2025-08-25 16:14:06', '2025-08-25 16:14:06'),
(5, 'renuka', 'renuka02@gmail.com', '$2y$10$jVw688uFQwqLmjzDsJjIKOAtvDCtvR0NAk3Z6uSjHGtZN3sSgg3DS', '0719180005', '2025-08-25 16:15:23', '2025-08-26 18:16:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `movies`
--
ALTER TABLE `movies` ADD FULLTEXT KEY `ft_movies` (`title`,`genre`,`description`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_admin` FOREIGN KEY (`processed_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bookings_showtime` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_seats`
--
ALTER TABLE `booking_seats`
  ADD CONSTRAINT `fk_booking_seats_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_seats_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `fk_seats_showtime` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `fk_showtimes_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
