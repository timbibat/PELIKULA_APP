-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2025 at 03:47 PM
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
(15, 2, 1, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '2025-09-19 10:00 AM', 'General Admission', 2, '2025-09-18 07:57:00', '2025-09-18', 'Cancelled'),
(16, 2, 2, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '11:00 AM', 'General Admission', 3, '2025-09-18 08:04:51', '2025-09-19', 'Cancelled'),
(17, 2, 2, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '5:00 PM', 'General Admission', 3, '2025-09-18 08:47:39', NULL, 'Cancelled'),
(18, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 1, '2025-09-18 09:28:40', NULL, 'Cancelled'),
(19, 2, 2, 'Christopher Jr', 'Artuz', 'artuzjrchristopher@gmail.com', '8:00 PM', 'General Admission', 3, '2025-09-18 09:51:44', NULL, 'Cancelled'),
(20, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 2, '2025-09-18 09:55:17', NULL, 'Cancelled'),
(21, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 2, '2025-09-18 09:58:52', NULL, 'Cancelled'),
(22, 2, 1, 'pol', 'sulit', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 10, '2025-09-18 10:02:51', NULL, 'Cancelled'),
(23, 2, 1, 'Cj', 'Artuz', 'artuzjrchristopher@gmail.com', '1:00 PM', 'General Admission', 1, '2025-09-19 02:00:15', NULL, 'Cancelled'),
(24, 2, 2, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '11:00 AM', 'General Admission', 1, '2025-09-19 02:11:43', NULL, 'Cancelled'),
(25, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '1:00 PM', 'General Admission', 2, '2025-09-19 02:36:09', NULL, 'Cancelled'),
(26, 3, 1, 'sijey', 'Tyler', 'c.artuz143@gmail.com', '1:00 PM', 'General Admission', 1, '2025-09-19 03:11:40', NULL, 'Upcoming'),
(27, 3, 2, 'sijey', 'Tyler', 'c.artuz143@gmail.com', '11:00 AM', 'General Admission', 1, '2025-09-19 12:20:56', '2025-09-20', 'Done'),
(28, 2, 1, 'Christopher Jr', 'Artuz', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 1, '2025-09-22 08:41:27', NULL, 'Cancelled'),
(29, 2, 2, 'TEST', 'TEST', 'artuzjrchristopher@gmail.com', '8:00 PM', 'General Admission', 3, '2025-09-22 08:45:45', '2025-09-22', 'Cancelled'),
(30, 2, 1, 'test name', 'test lname', 'artuzjrchristopher@gmail.com', '7:00 PM', 'General Admission', 1, '2025-09-22 08:55:41', '2025-09-22', 'Done'),
(31, 2, 2, 'lenlen', 'Artuz', 'artuzjrchristopher@gmail.com', '8:00 PM', 'General Admission', 1, '2025-09-22 09:13:19', '2025-09-22', 'Done'),
(32, 2, 1, 'pelikula', 'cinema', 'pelikulatix@gmail.com', '10:00 AM', 'General Admission', 10, '2025-09-24 11:26:01', '2025-09-25', 'Upcoming'),
(33, 2, 1, 'Christopher', 'Artuz', 'artuzjrchristopher@gmail.com', '10:00 AM', 'General Admission', 3, '2025-09-24 12:00:04', '2025-09-25', 'Upcoming'),
(34, 2, 2, 'Thor', 'Tyler', 'storageforgallery15gb.2@gmail.com', '11:00 AM', 'General Admission', 1, '2025-09-24 13:37:56', '2025-09-25', 'Upcoming'),
(35, 2, 1, 'Charlotte', 'Dog', 'zodiaxnightcoreteam1145@gmail.com', '10:00 AM', 'General Admission', 1, '2025-09-24 13:43:30', '2025-09-25', 'Upcoming');

--
-- Dumping data for table `replies`
--

INSERT INTO `replies` (`id`, `user_id`, `email`, `booking_id`, `message`, `created_at`) VALUES
(1, 2, '', NULL, 'hello pls reply\r\n\r\n\r\nOn Wed, Sep 17, 2025 at 1:54 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Name:* Christopher Artuz\r\n>\r\n> *Movie:* Avengers: Endgame\r\n>\r\n> *Showtime:* 2PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>\r\n> *You can reply to this email if you have questions.*\r\n>', '2025-09-18 17:50:21'),
(2, 2, '', NULL, '0101010101010010010010011100101\r\n\r\nOn Wed, Sep 17, 2025 at 2:33 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Name:* Christopher jajaja Artuz\r\n>\r\n> *Movie:* Avengers: Endgame\r\n>\r\n> *Showtime:* 2PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>\r\n> *You can reply to this email if you have questions.*\r\n>', '2025-09-18 17:50:22'),
(3, 2, '', NULL, 'Booking confirmed.\r\n\r\nOn Wed, Sep 17, 2025 at 1:19 PM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Name:* Cj Artuz\r\n>\r\n> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>\r\n> *Showtime:* 10:00 AM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 3\r\n>\r\n> *Total Price:* ₱945\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-18 17:50:27'),
(4, 1, '', NULL, '<h2>Booking Confirmed!</h2><p><strong>Booking ID:</strong> 11</p><p><strong>Name:</strong> CJ Bibat</p><p><strong>Movie:</strong> Demon Slayer - Kimetsu no Yaiba - Infinity Castle</p><p><strong>Showtime:</strong> 7:00 PM</p><p><strong>Seat:</strong> General Admission</p><p><strong>Quantity:</strong> 1</p><p><strong>Total Price:</strong> ₱315</p><p>Thank you for booking with PELIKULA!</p>', '2025-09-18 17:50:28'),
(5, 2, '', NULL, 'test reply 1\r\n\r\nOn Thu, Sep 18, 2025 at 5:28 PM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 18\r\n>\r\n> *Name:* Christopher Artuz\r\n>\r\n> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>\r\n> *Showtime:* 7:00 PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 1\r\n>\r\n> *Total Price:* ₱315\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-18 17:50:29'),
(6, 2, '', NULL, 'Hello, test reply\r\n\r\nOn Thu, Sep 18, 2025 at 5:51 PM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 19\r\n>\r\n> *Name:* Christopher Jr Artuz\r\n>\r\n> *Movie:* The Conjuring: Last Rites\r\n>\r\n> *Showtime:* 8:00 PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 3\r\n>\r\n> *Total Price:* ₱945\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-19 10:31:09'),
(7, 2, '', NULL, 'test of minimal replies, thanks\r\n\r\nOn Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 23\r\n>\r\n> *Name:* Cj Artuz\r\n>\r\n> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>\r\n> *Showtime:* 1:00 PM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 1\r\n>\r\n> *Total Price:* ₱315\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-19 10:31:13'),
(8, 2, '', NULL, 'test2 of minimum replies\r\n\r\nOn Fri, Sep 19, 2025 at 10:00 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> test of minimal replies, thanks\r\n>\r\n> On Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com>\r\n> wrote:\r\n>\r\n>> Booking Confirmed!\r\n>>\r\n>> *Booking ID:* 23\r\n>>\r\n>> *Name:* Cj Artuz\r\n>>\r\n>> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>>\r\n>> *Showtime:* 1:00 PM\r\n>>\r\n>> *Seat:* General Admission\r\n>>\r\n>> *Quantity:* 1\r\n>>\r\n>> *Total Price:* ₱315\r\n>>\r\n>> Thank you for booking with PELIKULA!\r\n>>\r\n>', '2025-09-19 10:31:14'),
(9, 2, '', NULL, 'test3 of getting minimum replies\r\n\r\nOn Fri, Sep 19, 2025 at 10:02 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> test2 of minimum replies\r\n>\r\n> On Fri, Sep 19, 2025 at 10:00 AM Christopher artuz jr <\r\n> artuzjrchristopher@gmail.com> wrote:\r\n>\r\n>> test of minimal replies, thanks\r\n>>\r\n>> On Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com>\r\n>> wrote:\r\n>>\r\n>>> Booking Confirmed!\r\n>>>\r\n>>> *Booking ID:* 23\r\n>>>\r\n>>> *Name:* Cj Artuz\r\n>>>\r\n>>> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>>>\r\n>>> *Showtime:* 1:00 PM\r\n>>>\r\n>>> *Seat:* General Admission\r\n>>>\r\n>>> *Quantity:* 1\r\n>>>\r\n>>> *Total Price:* ₱315\r\n>>>\r\n>>> Thank you for booking with PELIKULA!\r\n>>>\r\n>>', '2025-09-19 10:31:16'),
(10, 2, '', NULL, 'last replies\r\n\r\nOn Fri, Sep 19, 2025 at 10:05 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> test3 of getting minimum replies\r\n>\r\n> On Fri, Sep 19, 2025 at 10:02 AM Christopher artuz jr <\r\n> artuzjrchristopher@gmail.com> wrote:\r\n>\r\n>> test2 of minimum replies\r\n>>\r\n>> On Fri, Sep 19, 2025 at 10:00 AM Christopher artuz jr <\r\n>> artuzjrchristopher@gmail.com> wrote:\r\n>>\r\n>>> test of minimal replies, thanks\r\n>>>\r\n>>> On Fri, Sep 19, 2025 at 10:00 AM PELIKULA <pelikulacinema@gmail.com>\r\n>>> wrote:\r\n>>>\r\n>>>> Booking Confirmed!\r\n>>>>\r\n>>>> *Booking ID:* 23\r\n>>>>\r\n>>>> *Name:* Cj Artuz\r\n>>>>\r\n>>>> *Movie:* Demon Slayer - Kimetsu no Yaiba - Infinity Castle\r\n>>>>\r\n>>>> *Showtime:* 1:00 PM\r\n>>>>\r\n>>>> *Seat:* General Admission\r\n>>>>\r\n>>>> *Quantity:* 1\r\n>>>>\r\n>>>> *Total Price:* ₱315\r\n>>>>\r\n>>>> Thank you for booking with PELIKULA!\r\n>>>>\r\n>>>', '2025-09-19 10:31:18'),
(11, 2, '', NULL, 'reply 123, new\r\n\r\nOn Fri, Sep 19, 2025 at 10:11 AM PELIKULA <pelikulacinema@gmail.com> wrote:\r\n\r\n> Booking Confirmed!\r\n>\r\n> *Booking ID:* 24\r\n>\r\n> *Name:* Christopher Artuz\r\n>\r\n> *Movie:* The Conjuring: Last Rites\r\n>\r\n> *Showtime:* 11:00 AM\r\n>\r\n> *Seat:* General Admission\r\n>\r\n> *Quantity:* 1\r\n>\r\n> *Total Price:* ₱315\r\n>\r\n> Thank you for booking with PELIKULA!\r\n>', '2025-09-19 10:31:19'),
(12, 2, '', NULL, 'replies plss test if working\r\n\r\nOn Fri, Sep 19, 2025 at 10:12 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> reply 123, new\r\n>\r\n> On Fri, Sep 19, 2025 at 10:11 AM PELIKULA <pelikulacinema@gmail.com>\r\n> wrote:\r\n>\r\n>> Booking Confirmed!\r\n>>\r\n>> *Booking ID:* 24\r\n>>\r\n>> *Name:* Christopher Artuz\r\n>>\r\n>> *Movie:* The Conjuring: Last Rites\r\n>>\r\n>> *Showtime:* 11:00 AM\r\n>>\r\n>> *Seat:* General Admission\r\n>>\r\n>> *Quantity:* 1\r\n>>\r\n>> *Total Price:* ₱315\r\n>>\r\n>> Thank you for booking with PELIKULA!\r\n>>\r\n>', '2025-09-19 10:31:20'),
(13, 2, '', NULL, 'replies of minimum test 1\r\n\r\nOn Fri, Sep 19, 2025 at 10:15 AM Christopher artuz jr <\r\nartuzjrchristopher@gmail.com> wrote:\r\n\r\n> replies plss test if working\r\n>\r\n> On Fri, Sep 19, 2025 at 10:12 AM Christopher artuz jr <\r\n> artuzjrchristopher@gmail.com> wrote:\r\n>\r\n>> reply 123, new\r\n>>\r\n>> On Fri, Sep 19, 2025 at 10:11 AM PELIKULA <pelikulacinema@gmail.com>\r\n>> wrote:\r\n>>\r\n>>> Booking Confirmed!\r\n>>>\r\n>>> *Booking ID:* 24\r\n>>>\r\n>>> *Name:* Christopher Artuz\r\n>>>\r\n>>> *Movie:* The Conjuring: Last Rites\r\n>>>\r\n>>> *Showtime:* 11:00 AM\r\n>>>\r\n>>> *Seat:* General Admission\r\n>>>\r\n>>> *Quantity:* 1\r\n>>>\r\n>>> *Total Price:* ₱315\r\n>>>\r\n>>> Thank you for booking with PELIKULA!\r\n>>>\r\n>>', '2025-09-19 10:31:22'),
(14, 2, '', NULL, 'replies of minimum test 2\n\nOn Fri, Sep 19, 2025 at 10:18 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:38:04'),
(15, 2, '', NULL, 'REPLIES TEST 1', '2025-09-19 10:38:05'),
(16, 2, '', NULL, 'REPLIES TEST 2\n\nOn Fri, Sep 19, 2025 at 10:36 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:39:13'),
(17, 2, '', NULL, 'REPLIES TEST 3\n\nOn Fri, Sep 19, 2025 at 10:38 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:48:12'),
(18, 2, '', NULL, 'REPLIES TEST 4\n\nOn Fri, Sep 19, 2025 at 10:47 AM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-19 10:49:33'),
(19, 3, '', NULL, 'Awesome Movie!', '2025-09-19 11:12:47'),
(20, 3, '', NULL, 'Can\'t wait for this movie!', '2025-09-19 20:21:49'),
(21, 2, '', 29, 'CANCEL THIS, THANK YOU', '2025-09-22 16:59:34'),
(22, 2, '', 30, 'Cancel this too please, thank you', '2025-09-22 17:04:14'),
(23, 2, '', 31, 'I\'m excited for this movie, this better be worth it', '2025-09-22 17:14:09'),
(24, 2, '', 31, 'This is my second reply for the movie the conjuring: the last rites\n\n\nOn Mon, Sep 22, 2025 at 5:13 PM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-22 17:25:11'),
(25, 2, '', 31, 'This is my third reply for the movie the conjuring: the last rites\n\n\nOn Mon, Sep 22, 2025 at 5:22 PM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-22 17:26:15'),
(26, 2, '', 31, 'This is my fourth reply for the movie the conjuring: the last rites\n\nOn Mon, Sep 22, 2025 at 5:25 PM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-22 17:27:53'),
(27, 2, '', 31, 'This is my last reply for the movie the conjuring: the last rites\n\n\nOn Mon, Sep 22, 2025 at 5:27 PM Christopher artuz jr <\nartuzjrchristopher@gmail.com> wrote:', '2025-09-22 17:32:19'),
(28, 2, '', 33, 'reply in 8:00PM september 24, 2025', '2025-09-24 20:01:17'),
(29, NULL, 'pelikulatix@gmail.com', 29, 'Last test in receiving the repy\n\nOn Wed, Sep 24, 2025 at 9:30 PM pelikulacinema <pelikulatix@gmail.com>\nwrote:', '2025-09-24 21:34:10'),
(30, NULL, 'storageforgallery15gb.2@gmail.com', 34, 'Booking Received, thank you\n- Thor Tyler', '2025-09-24 21:39:19'),
(31, NULL, 'zodiaxnightcoreteam1145@gmail.com', 35, 'Booking confirmed, Thank you\n- Charlotte Dog', '2025-09-24 21:45:21');

--
-- Dumping data for table `tbl_movies`
--

INSERT INTO `tbl_movies` (`id`, `title`, `description`, `duration`, `showtimes`, `price`, `created_at`) VALUES
(1, 'Demon Slayer - Kimetsu no Yaiba - Infinity Castle', 'Tanjiro and the Demon Slayer Corps prepare for the final battle inside the Infinity Castle.', '2 hours 35 minutes', '10:00 AM,1:00 PM,4:00 PM,7:00 PM', 315.00, '2025-09-17 03:56:19'),
(2, 'The Conjuring: Last Rites', 'The Warrens face their most terrifying case yet in the chilling conclusion to the saga.', '2 hours 17 minutes', '11:00 AM,2:00 PM,5:00 PM,8:00 PM', 315.00, '2025-09-17 03:56:19');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `is_verified`, `verification_token`, `created_at`, `is_admin`) VALUES
(1, 'pelikulacinema@gmail.com', NULL, 1, '54d27746a45880053da11a16d7dd52c9', '2025-09-17 04:15:01', 1),
(2, 'artuzjrchristopher@gmail.com', NULL, 1, '24a992797e13bffac986cf98f801f6e5', '2025-09-17 04:21:49', 0),
(3, 'c.artuz143@gmail.com', NULL, 1, '2069ad9aaeece5a7471ca87b997dcec3', '2025-09-19 03:08:20', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
