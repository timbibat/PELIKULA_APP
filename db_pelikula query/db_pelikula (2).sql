-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2025 at 02:42 PM
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
-- Database: `db_pelikula`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `showtime` varchar(100) NOT NULL,
  `seat` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `booked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `showdate` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Upcoming'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `movie_id`, `first_name`, `last_name`, `email`, `showtime`, `seat`, `quantity`, `booked_at`, `showdate`, `status`) VALUES
(1, 1, 1, 'Timothy', 'Bibat', 'timothybibat654@gmail.com', '1:00 PM', 'General Admission', 10, '2025-09-17 04:33:41', '2025-09-17', 'Done'),
(2, 1, 1, 'Timothy', 'Bibat', 'artuzjrchristopher@gmail.com', '1:00 PM', 'General Admission', 10, '2025-09-17 04:33:53', '2025-09-17', 'Done'),
(3, 1, 1, 'Timothy', 'Bibat', 'pelikulacinema@gmail.com', '1:00 PM', 'General Admission', 10, '2025-09-17 04:36:05', '2025-09-17', 'Done'),
(4, 1, 1, 'Timothy', 'Artuz', 'timothybibat654@gmail.com', '7:00 PM', 'General Admission', 2, '2025-09-17 04:37:07', '2025-09-17', 'Done'),
(5, 1, 1, 'Timothy', 'Artuz', 'timothybibat654@gmail.com', '4:00 PM', 'General Admission', 2, '2025-09-17 04:40:12', '2025-09-17', 'Done'),
(6, 1, 1, 'Timothy', 'Bibat', 'pelikulacinema@gmail.com', '4:00 PM', 'General Admission', 1, '2025-09-17 04:44:42', '2025-09-17', 'Done'),
(7, 2, 1, 'testfirst', 'test', 'artuzjrchristopher@gmail.com', '1:00 PM', 'General Admission', 5, '2025-09-17 04:46:17', '2025-09-17', 'Done'),
(8, 2, 1, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '10:00 AM', 'General Admission', 3, '2025-09-17 05:19:51', '2025-09-17', 'Done'),
(9, 1, 1, 'Timothy', 'Bibat', 'artuzjrchristopher@gmail.com', '10:00 AM', 'General Admission', 2, '2025-09-17 10:24:41', '2025-09-17', 'Done'),
(10, 1, 2, 'Timothy', 'Bibat', 'pelikulacinema@gmail.com', '8:00 PM', 'General Admission', 2, '2025-09-17 10:28:28', '2025-09-17', 'Done'),
(11, 1, 1, 'CJ', 'Bibat', 'pelikulacinema@gmail.com', '7:00 PM', 'General Admission', 1, '2025-09-17 10:40:46', '2025-09-17', 'Done'),
(12, 2, 1, 'Denmar', 'Redondo', 'denmar.redondo@my.nst.edu.ph', '10:00 AM', 'General Admission', 5, '2025-09-18 06:05:42', '2025-09-18', 'Done'),
(13, 2, 1, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '4:00 PM', 'General Admission', 2, '2025-09-18 06:21:01', '2025-09-18', 'Done'),
(14, 2, 2, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '8:00 PM', 'General Admission', 3, '2025-09-18 06:49:46', '2025-09-18', 'Done'),
(15, 2, 1, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '2025-09-19 10:00 AM', 'General Admission', 2, '2025-09-18 07:57:00', '2025-09-18', 'Upcoming'),
(16, 2, 2, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '11:00 AM', 'General Admission', 3, '2025-09-18 08:04:51', '2025-09-19', 'Cancelled'),
(17, 2, 2, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '5:00 PM', 'General Admission', 3, '2025-09-18 08:47:39', NULL, 'Upcoming'),
(18, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 1, '2025-09-18 09:28:40', NULL, 'Upcoming'),
(19, 2, 2, 'Christopher Jr', 'Artuz', 'artuzjrchristopher@gmail.com', '8:00 PM', 'General Admission', 3, '2025-09-18 09:51:44', NULL, 'Upcoming'),
(20, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 2, '2025-09-18 09:55:17', NULL, 'Upcoming'),
(21, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 2, '2025-09-18 09:58:52', NULL, 'Upcoming'),
(22, 2, 1, 'pol', 'sulit', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 10, '2025-09-18 10:02:51', NULL, 'Upcoming'),
(23, 2, 1, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '1:00 PM', 'General Admission', 1, '2025-09-19 02:00:15', NULL, 'Upcoming'),
(24, 2, 2, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '11:00 AM', 'General Admission', 1, '2025-09-19 02:11:43', NULL, 'Upcoming'),
(25, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '1:00 PM', 'General Admission', 2, '2025-09-19 02:36:09', NULL, 'Upcoming'),
(26, 3, 1, 'sijey', 'Tyler', 'c.artuz143@gmail.com', '1:00 PM', 'General Admission', 1, '2025-09-19 03:11:40', NULL, 'Upcoming'),
(27, 3, 2, 'sijey', 'Tyler', 'c.artuz143@gmail.com', '11:00 AM', 'General Admission', 1, '2025-09-19 12:20:56', '2025-09-20', 'Upcoming');

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE `replies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `replies`
--

INSERT INTO `replies` (`id`, `user_id`, `booking_id`, `message`, `created_at`) VALUES
(1, 2, NULL, 'hello pls reply\r\n\r\n\r\nOn Wed, Sep 17, 2025 at 1:54 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Name:* Christopher Artuz\r\n>\r\n> *Movie:* Avengers: Endgame\r\n>\r\n> *Showtime:* 2PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>\r\n> *You can reply to this email if you have questions.*\r\n>', '2025-09-18 17:50:21'),
(2, 2, NULL, '0101010101010010010010011100101\r\n\r\nOn Wed, Sep 17, 2025 at 2:33 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Name:* Christopher jajaja Artuz\r\n>\r\n> *Movie:* Avengers: Endgame\r\n>\r\n> *Showtime:* 2PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>\r\n> *You can reply to this email if you have questions.*\r\n>', '2025-09-18 17:50:22'),
(3, 2, NULL, 'Booking confirmed.\r\n\r\nOn Wed, Sep 17, 2025 at 1:19 PM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Name:* Cj Artuz\r\n>\r\n> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>\r\n> *Showtime:* 10:00 AM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 3\r\n>\r\n> *Total Price:* ₱945\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-18 17:50:27'),
(4, 1, NULL, '<h2>Booking Confirmed!</h2><p><strong>Booking ID:</strong> 11</p><p><strong>Name:</strong> CJ Bibat</p><p><strong>Movie:</strong> Demon Slayer - Kimetsu no Yaiba - Infinity Castle</p><p><strong>Showtime:</strong> 7:00 PM</p><p><strong>Seat:</strong> General Admission</p><p><strong>Quantity:</strong> 1</p><p><strong>Total Price:</strong> ₱315</p><p>Thank you for booking with PELIKULA!</p>', '2025-09-18 17:50:28'),
(5, 2, NULL, 'test reply 1\r\n\r\nOn Thu, Sep 18, 2025 at 5:28 PM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 18\r\n>\r\n> *Name:* Christopher Artuz\r\n>\r\n> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>\r\n> *Showtime:* 7:00 PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 1\r\n>\r\n> *Total Price:* ₱315\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-18 17:50:29'),
(6, 2, NULL, 'Hello, test reply\r\n\r\nOn Thu, Sep 18, 2025 at 5:51 PM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 19\r\n>\r\n> *Name:* Christopher Jr Artuz\r\n>\r\n> *Movie:* The Conjuring: Last Rites\r\n>\r\n> *Showtime:* 8:00 PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 3\r\n>\r\n> *Total Price:* ₱945\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-19 10:31:09'),
(7, 2, NULL, 'test of minimal replies, thanks\r\n\r\nOn Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 23\r\n>\r\n> *Name:* Cj Artuz\r\n>\r\n> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>\r\n> *Showtime:* 1:00 PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 1\r\n>\r\n> *Total Price:* ₱315\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-19 10:31:13'),
(8, 2, NULL, 'test2 of minimum replies\r\n\r\nOn Fri, Sep 19, 2025 at 10:00 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> test of minimal replies, thanks\r\n>\r\n> On Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com>\r\n> wrote:\r\n>\r\n>> Booking Confirmed!\r\n>>\r\n>> *Booking ID:* 23\r\n>>\r\n>> *Name:* Cj Artuz\r\n>>\r\n>> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>>\r\n>> *Showtime:* 1:00 PM\r\n>>\r\n>> *Seat:* General Admission\r\n>>\r\n>> *Quantity:* 1\r\n>>\r\n>> *Total Price:* ₱315\r\n>>\r\n>> Thank you for booking with PELIKULA!\r\n>>\r\n>', '2025-09-19 10:31:14'),
(9, 2, NULL, 'test3 of getting minimum replies\r\n\r\nOn Fri, Sep 19, 2025 at 10:02 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> test2 of minimum replies\r\n>\r\n> On Fri, Sep 19, 2025 at 10:00 AM Christopher artuz jr <\r\n> artuzjrchristopher@gmail.com> wrote:\r\n>\r\n>> test of minimal replies, thanks\r\n>>\r\n>> On Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com>\r\n>> wrote:\r\n>>\r\n>>> Booking Confirmed!\r\n>>>\r\n>>> *Booking ID:* 23\r\n>>>\r\n>>> *Name:* Cj Artuz\r\n>>>\r\n>>> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>>>\r\n>>> *Showtime:* 1:00 PM\r\n>>>\r\n>>> *Seat:* General Admission\r\n>>>\r\n>>> *Quantity:* 1\r\n>>>\r\n>>> *Total Price:* ₱315\r\n>>>\r\n>>> Thank you for booking with PELIKULA!\r\n>>>\r\n>>', '2025-09-19 10:31:16'),
(10, 2, NULL, 'last replies\r\n\r\nOn Fri, Sep 19, 2025 at 10:05 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> test3 of getting minimum replies\r\n>\r\n> On Fri, Sep 19, 2025 at 10:02 AM Christopher artuz jr <\r\n> artuzjrchristopher@gmail.com> wrote:\r\n>\r\n>> test2 of minimum replies\r\n>>\r\n>> On Fri, Sep 19, 2025 at 10:00 AM Christopher artuz jr <\r\n>> artuzjrchristopher@gmail.com> wrote:\r\n>>\r\n>>> test of minimal replies, thanks\r\n>>>\r\n>>> On Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com>\r\n>>> wrote:\r\n>>>\r\n>>>> Booking Confirmed!\r\n>>>>\r\n>>>> *Booking ID:* 23\r\n>>>>\r\n>>>> *Name:* Cj Artuz\r\n>>>>\r\n>>>> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>>>>\r\n>>>> *Showtime:* 1:00 PM\r\n>>>>\r\n>>>> *Seat:* General Admission\r\n>>>>\r\n>>>> *Quantity:* 1\r\n>>>>\r\n>>>> *Total Price:* ₱315\r\n>>>>\r\n>>>> Thank you for booking with PELIKULA!\r\n>>>>\r\n>>>', '2025-09-19 10:31:18'),
(11, 2, NULL, 'reply 123, new\r\n\r\nOn Fri, Sep 19, 2025 at 10:11 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 24\r\n>\r\n> *Name:* Christopher Artuz\r\n>\r\n> *Movie:* The Conjuring: Last Rites\r\n>\r\n> *Showtime:* 11:00 AM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 1\r\n>\r\n> *Total Price:* ₱315\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-19 10:31:19'),
(12, 2, NULL, 'replies plss test if working\r\n\r\nOn Fri, Sep 19, 2025 at 10:12 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> reply 123, new\r\n>\r\n> On Fri, Sep 19, 2025 at 10:11 AM PELIKULA <pelikulacinema@gmail.com>\r\n> wrote:\r\n>\r\n>> Booking Confirmed!\r\n>>\r\n>> *Booking ID:* 24\r\n>>\r\n>> *Name:* Christopher Artuz\r\n>>\r\n>> *Movie:* The Conjuring: Last Rites\r\n>>\r\n>> *Showtime:* 11:00 AM\r\n>>\r\n>> *Seat:* General Admission\r\n>>\r\n>> *Quantity:* 1\r\n>>\r\n>> *Total Price:* ₱315\r\n>>\r\n>> Thank you for booking with PELIKULA!\r\n>>\r\n>', '2025-09-19 10:31:20'),
(13, 2, NULL, 'replies of minimum test 1\r\n\r\nOn Fri, Sep 19, 2025 at 10:15 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> replies plss test if working\r\n>\r\n> On Fri, Sep 19, 2025 at 10:12 AM Christopher artuz jr <\r\n> artuzjrchristopher@gmail.com> wrote:\r\n>\r\n>> reply 123, new\r\n>>\r\n>> On Fri, Sep 19, 2025 at 10:11 AM PELIKULA <pelikulacinema@gmail.com>\r\n>> wrote:\r\n>>\r\n>>> Booking Confirmed!\r\n>>>\r\n>>> *Booking ID:* 24\r\n>>>\r\n>>> *Name:* Christopher Artuz\r\n>>>\r\n>>> *Movie:* The Conjuring: Last Rites\r\n>>>\r\n>>> *Showtime:* 11:00 AM\r\n>>>\r\n>>> *Seat:* General Admission\r\n>>>\r\n>>> *Quantity:* 1\r\n>>>\r\n>>> *Total Price:* ₱315\r\n>>>\r\n>>> Thank you for booking with PELIKULA!\r\n>>>\r\n>>', '2025-09-19 10:31:22'),
(14, 2, NULL, 'replies of minimum test 2\n\nOn Fri, Sep 19, 2025 at 10:18 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:38:04'),
(15, 2, NULL, 'REPLIES TEST 1', '2025-09-19 10:38:05'),
(16, 2, NULL, 'REPLIES TEST 2\n\nOn Fri, Sep 19, 2025 at 10:36 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:39:13'),
(17, 2, NULL, 'REPLIES TEST 3\n\nOn Fri, Sep 19, 2025 at 10:38 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:48:12'),
(18, 2, NULL, 'REPLIES TEST 4\n\nOn Fri, Sep 19, 2025 at 10:47 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:49:33'),
(19, 3, NULL, 'Awesome Movie!', '2025-09-19 11:12:47'),
(20, 3, NULL, 'Can\'t wait for this movie!', '2025-09-19 20:21:49');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_movies`
--

CREATE TABLE `tbl_movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `showtimes` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_movies`
--

INSERT INTO `tbl_movies` (`id`, `title`, `description`, `duration`, `showtimes`, `price`, `created_at`) VALUES
(1, 'Demon Slayer - Kimetsu no Yaiba - Infinity Castle', 'Tanjiro and the Demon Slayer Corps prepare for the final battle inside the Infinity Castle.', '2 hours 35 minutes', '10:00 AM,1:00 PM,4:00 PM,7:00 PM', 315.00, '2025-09-17 03:56:19'),
(2, 'The Conjuring: Last Rites', 'The Warrens face their most terrifying case yet in the chilling conclusion to the saga.', '2 hours 17 minutes', '11:00 AM,2:00 PM,5:00 PM,8:00 PM', 315.00, '2025-09-17 03:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `is_verified`, `verification_token`, `created_at`, `is_admin`) VALUES
(1, 'pelikulacinema@gmail.com', NULL, 1, '54d27746a45880053da11a16d7dd52c9', '2025-09-17 04:15:01', 1),
(2, 'artuzjrchristopher@gmail.com', NULL, 1, '3570a05da2e667f02a0d87ca7920653e', '2025-09-17 04:21:49', 0),
(3, 'c.artuz143@gmail.com', NULL, 1, '2069ad9aaeece5a7471ca87b997dcec3', '2025-09-19 03:08:20', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `replies`
--
ALTER TABLE `replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `tbl_movies`
--
ALTER TABLE `tbl_movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tbl_movies`
--
ALTER TABLE `tbl_movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `replies`
--
ALTER TABLE `replies`
  ADD CONSTRAINT `replies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `replies_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
