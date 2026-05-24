-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 06:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
CREATE DATABASE IF NOT EXISTS `natcorp_payroll` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `natcorp_payroll`;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `natcorp_payroll`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login successful', NULL, '::1', '2026-05-13 14:55:32'),
(2, 1, 'Logout', NULL, '::1', '2026-05-13 16:08:16'),
(3, 1, 'Login successful', NULL, '::1', '2026-05-13 16:11:02'),
(4, 1, 'Logout', NULL, '::1', '2026-05-13 16:26:53'),
(5, 1, 'Login successful', NULL, '::1', '2026-05-13 16:27:15'),
(6, 1, 'Logout', NULL, '::1', '2026-05-13 16:27:17'),
(7, 1, 'Login successful', NULL, '::1', '2026-05-13 16:27:20'),
(8, 1, 'Logout', NULL, '::1', '2026-05-13 16:27:25'),
(9, 6, 'Login successful', NULL, '::1', '2026-05-13 16:27:32'),
(10, 6, 'Logout', NULL, '::1', '2026-05-13 16:28:42'),
(11, NULL, 'Login failed for username: apostol.christian', NULL, '::1', '2026-05-13 16:28:47'),
(12, 1, 'Login successful', NULL, '::1', '2026-05-13 16:28:59'),
(13, 1, 'Logout', NULL, '::1', '2026-05-13 16:29:01'),
(14, 1, 'Login successful', NULL, '::1', '2026-05-13 16:29:41'),
(15, 1, 'Logout', NULL, '::1', '2026-05-13 16:29:45'),
(16, NULL, 'Login failed for username: apostol.christian', NULL, '::1', '2026-05-13 16:29:49'),
(17, 1, 'Login successful', NULL, '::1', '2026-05-13 16:29:54'),
(18, 1, 'Logout', NULL, '::1', '2026-05-13 16:30:02'),
(19, NULL, 'Login failed for username: CHRISTIAN APOSTOL', NULL, '::1', '2026-05-13 16:30:07'),
(20, 1, 'Login successful', NULL, '::1', '2026-05-13 16:30:15'),
(21, 1, 'Logout', NULL, '::1', '2026-05-13 16:30:27'),
(22, 6, 'Login successful', NULL, '::1', '2026-05-13 16:30:31'),
(23, 6, 'Logout', NULL, '::1', '2026-05-13 16:30:42'),
(24, 6, 'Login successful', NULL, '::1', '2026-05-13 16:30:48'),
(25, 6, 'Logout', NULL, '::1', '2026-05-13 16:30:50'),
(26, 6, 'Login successful', NULL, '::1', '2026-05-13 16:31:01'),
(27, 6, 'Logout', NULL, '::1', '2026-05-13 16:31:02'),
(28, 6, 'Login successful', NULL, '::1', '2026-05-13 16:31:10'),
(29, 6, 'Logout', NULL, '::1', '2026-05-13 16:31:12'),
(30, 6, 'Login successful', NULL, '::1', '2026-05-13 16:31:38'),
(31, 6, 'Logout', NULL, '::1', '2026-05-13 16:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `client_company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `employee_id`, `first_name`, `last_name`, `email`, `department`, `position`, `client_company`, `phone`, `dob`, `date_of_joining`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'T10014', 'RODELLA', 'CORDIAL', 'rodella@example.com', 'PRODUCTION', 'ASSY ASSOCIATE', 'SPWS', '9171234567', '1990-05-10', '2023-01-31', 'active', '2026-05-13 15:03:19', '2026-05-13 15:03:19'),
(2, 3, 'T10041', 'RUBIELYN', 'NORCIO', 'rodella@example.com', 'PRODUCTION', 'ASSY ASSOCIATE', 'SPWS', '9171234567', '1990-05-11', '2023-07-02', 'active', '2026-05-13 15:03:19', '2026-05-13 15:03:19'),
(10, 11, 'T10473', 'EMELY', 'GALLENERO', 'rodella@example.com', 'PRODUCTION', 'ASSY ASSOCIATE', 'SPWS', '9171234567', '1990-05-19', '2023-02-27', 'active', '2026-05-13 15:03:20', '2026-05-13 15:03:20');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_adjustments`
--

CREATE TABLE `payroll_adjustments` (
  `id` int(11) NOT NULL,
  `payroll_summary_id` int(11) NOT NULL,
  `late_undertime` decimal(12,2) DEFAULT 0.00,
  `assy_incentive` decimal(12,2) DEFAULT 0.00,
  `perfect_attendance` decimal(12,2) DEFAULT 0.00,
  `qa_incentive` decimal(12,2) DEFAULT 0.00,
  `special_process_allowance` decimal(12,2) DEFAULT 0.00,
  `superprocess` decimal(12,2) DEFAULT 0.00,
  `wcd_kaizen` decimal(12,2) DEFAULT 0.00,
  `mt_incentive` decimal(12,2) DEFAULT 0.00,
  `skt_incentive` decimal(12,2) DEFAULT 0.00,
  `contribution_refund` decimal(12,2) DEFAULT 0.00,
  `salary_complaint` decimal(12,2) DEFAULT 0.00,
  `hai_v` decimal(12,2) DEFAULT 0.00,
  `total_adjustment` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--

CREATE TABLE `payroll_deductions` (
  `id` int(11) NOT NULL,
  `payroll_summary_id` int(11) NOT NULL,
  `sss_sl` decimal(12,2) DEFAULT 0.00,
  `sss_cl` decimal(12,2) DEFAULT 0.00,
  `hdmf_mpl` decimal(12,2) DEFAULT 0.00,
  `hdmf_cl` decimal(12,2) DEFAULT 0.00,
  `hmo` decimal(12,2) DEFAULT 0.00,
  `uniform_upon_deployment` decimal(12,2) DEFAULT 0.00,
  `uniform_atd` decimal(12,2) DEFAULT 0.00,
  `housing` decimal(12,2) DEFAULT 0.00,
  `medifund_loan` decimal(12,2) DEFAULT 0.00,
  `negats_payroll` decimal(12,2) DEFAULT 0.00,
  `canteen_chit` decimal(12,2) DEFAULT 0.00,
  `shoes` decimal(12,2) DEFAULT 0.00,
  `id_deduction` decimal(12,2) DEFAULT 0.00,
  `cash_advance` decimal(12,2) DEFAULT 0.00,
  `hmo_availment` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_earnings`
--

CREATE TABLE `payroll_earnings` (
  `id` int(11) NOT NULL,
  `payroll_summary_id` int(11) NOT NULL,
  `reg_days_hrs` decimal(10,2) DEFAULT 0.00,
  `reg_days_amt` decimal(12,2) DEFAULT 0.00,
  `lh_unworked_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_unworked_amt` decimal(12,2) DEFAULT 0.00,
  `rot_hrs` decimal(10,2) DEFAULT 0.00,
  `rot_amt` decimal(12,2) DEFAULT 0.00,
  `nd_hrs` decimal(10,2) DEFAULT 0.00,
  `nd_amt` decimal(12,2) DEFAULT 0.00,
  `rd_hrs` decimal(10,2) DEFAULT 0.00,
  `rd_amt` decimal(12,2) DEFAULT 0.00,
  `rd_exc_hrs` decimal(10,2) DEFAULT 0.00,
  `rd_exc_amt` decimal(12,2) DEFAULT 0.00,
  `rd_nd_hrs` decimal(10,2) DEFAULT 0.00,
  `rd_nd_amt` decimal(12,2) DEFAULT 0.00,
  `rd_ndot_hrs` decimal(10,2) DEFAULT 0.00,
  `rd_ndot_amt` decimal(12,2) DEFAULT 0.00,
  `lh_rd_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_rd_amt` decimal(12,2) DEFAULT 0.00,
  `lh_rd_exc_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_rd_exc_amt` decimal(12,2) DEFAULT 0.00,
  `lh_rd_nd_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_rd_nd_amt` decimal(12,2) DEFAULT 0.00,
  `lh_rd_ndot_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_rd_ndot_amt` decimal(12,2) DEFAULT 0.00,
  `lh_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_amt` decimal(12,2) DEFAULT 0.00,
  `lh_exc_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_exc_amt` decimal(12,2) DEFAULT 0.00,
  `lh_nd_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_nd_amt` decimal(12,2) DEFAULT 0.00,
  `lh_ndot_hrs` decimal(10,2) DEFAULT 0.00,
  `lh_ndot_amt` decimal(12,2) DEFAULT 0.00,
  `shd_hrs` decimal(10,2) DEFAULT 0.00,
  `shd_amt` decimal(12,2) DEFAULT 0.00,
  `shd_ot_hrs` decimal(10,2) DEFAULT 0.00,
  `shd_ot_amt` decimal(12,2) DEFAULT 0.00,
  `shd_nd_hrs` decimal(10,2) DEFAULT 0.00,
  `shd_nd_amt` decimal(12,2) DEFAULT 0.00,
  `shd_rd_hrs` decimal(10,2) DEFAULT 0.00,
  `shd_rd_amt` decimal(12,2) DEFAULT 0.00,
  `shd_rd_ot_hrs` decimal(10,2) DEFAULT 0.00,
  `shd_rd_ot_amt` decimal(12,2) DEFAULT 0.00,
  `shd_rd_nd_hrs` decimal(10,2) DEFAULT 0.00,
  `shd_rd_nd_amt` decimal(12,2) DEFAULT 0.00,
  `cnw_hrs` decimal(10,2) DEFAULT 0.00,
  `cnw_amt` decimal(12,2) DEFAULT 0.00,
  `cnw_ot_hrs` decimal(10,2) DEFAULT 0.00,
  `cnw_ot_amt` decimal(12,2) DEFAULT 0.00,
  `cnd_nd_hrs` decimal(10,2) DEFAULT 0.00,
  `cnd_nd_amt` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_netpay`
--

CREATE TABLE `payroll_netpay` (
  `id` int(11) NOT NULL,
  `payroll_summary_id` int(11) NOT NULL,
  `employee_no` varchar(50) DEFAULT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `gross_amount` decimal(12,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_summary`
--

CREATE TABLE `payroll_summary` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `day` int(11) NOT NULL DEFAULT 1,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_earnings` decimal(12,2) DEFAULT 0.00,
  `total_adjustments` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslip_history`
--

CREATE TABLE `payslip_history` (
  `id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `downloaded_at` timestamp NULL DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `role` enum('admin','employee') DEFAULT 'employee',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `remember_token`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@natcorp.com', 'admin123', NULL, 'admin', 'active', '2026-05-13 14:46:47', '2026-05-13 16:29:01'),
(2, 'rodella.cordial', 'rodella@example.com', '$2y$10$.OqMkA1z.lOQ7tJXcILYGen/aFxmPMrpbQZL9nL1tMEG/RzNiqDwi', NULL, 'employee', 'active', '2026-05-13 15:03:19', '2026-05-13 15:03:19'),
(3, 'rubielyn.norcio', 'rodella@example.com', '$2y$10$6SabFFyZoxkH1dI87iWppewVOlCH7HPP45zSLniHfQc3PQqYNgGj.', NULL, 'employee', 'active', '2026-05-13 15:03:19', '2026-05-13 15:03:19'),
(4, 'joselyn.deguzman', 'rodella@example.com', '$2y$10$33pw5aT3ywGI/PgI9nbUa.XCDT0GDV.Vnus2YIplv4ARhIGhKFxbq', NULL, 'employee', 'active', '2026-05-13 15:03:19', '2026-05-13 15:03:19'),
(5, 'justin.devera', 'rodella@example.com', '$2y$10$vXzOUpfY7sDFqGup4ZNUw.iHMG6LwABwe0dvKCzbY7pGaUePz/pYu', NULL, 'employee', 'active', '2026-05-13 15:03:19', '2026-05-13 15:03:19'),
(6, 'leo.sangil', 'rodella@example.com', '$2y$10$45cjUZIHsEwyoYGXqRPw5.i.xlXKN.uTX9iObfJFwV3TRN5yGc3I2', NULL, 'employee', 'active', '2026-05-13 15:03:20', '2026-05-13 16:31:50'),
(7, 'christian.apostol', 'rodella@example.com', '$2y$10$7YaV.lidnQu.biY421IkHuq9fvhrkgSc5u97t0S9vSZZaajxDsIdG', NULL, 'employee', 'active', '2026-05-13 15:03:20', '2026-05-13 15:03:20'),
(8, 'john.robertgalang', 'rodella@example.com', '$2y$10$xdrKYGkdXFnuG3MLdPvAmeU0RCMLrcIV28VCejdLOIxYzVFkA9vGa', NULL, 'employee', 'active', '2026-05-13 15:03:20', '2026-05-13 15:03:20'),
(9, 'justine.mallari', 'rodella@example.com', '$2y$10$IgaOS2UkCxZSOAIMWfSQkuDGpRv3hEJkghCWRKp1Q4UaemOG/4zm6', NULL, 'employee', 'active', '2026-05-13 15:03:20', '2026-05-13 15:03:20'),
(10, 'john.rodeldeleon', 'rodella@example.com', '$2y$10$A22tb0rC9wihH.yts84APu1o8y0/oV/j/dRzwhXJdos0EXWMbPVfq', NULL, 'employee', 'active', '2026-05-13 15:03:20', '2026-05-13 15:03:20'),
(11, 'emely.gallenero', 'rodella@example.com', '$2y$10$yWTIFn/O41aY9Cop69H6F.RV1cjm1VG82ZD25adjSRPofKB4UDp5.', NULL, 'employee', 'active', '2026-05-13 15:03:20', '2026-05-13 15:03:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payroll_adjustments`
--
ALTER TABLE `payroll_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_summary_id` (`payroll_summary_id`);

--
-- Indexes for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_summary_id` (`payroll_summary_id`);

--
-- Indexes for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_summary_id` (`payroll_summary_id`);

--
-- Indexes for table `payroll_netpay`
--
ALTER TABLE `payroll_netpay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_summary_id` (`payroll_summary_id`);

--
-- Indexes for table `payroll_summary`
--
ALTER TABLE `payroll_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payroll` (`employee_id`,`day`,`month`,`year`);

--
-- Indexes for table `payslip_history`
--
ALTER TABLE `payslip_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_id` (`payroll_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payroll_adjustments`
--
ALTER TABLE `payroll_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_netpay`
--
ALTER TABLE `payroll_netpay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_summary`
--
ALTER TABLE `payroll_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslip_history`
--
ALTER TABLE `payslip_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_adjustments`
--
ALTER TABLE `payroll_adjustments`
  ADD CONSTRAINT `payroll_adjustments_ibfk_1` FOREIGN KEY (`payroll_summary_id`) REFERENCES `payroll_summary` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD CONSTRAINT `payroll_deductions_ibfk_1` FOREIGN KEY (`payroll_summary_id`) REFERENCES `payroll_summary` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  ADD CONSTRAINT `payroll_earnings_ibfk_1` FOREIGN KEY (`payroll_summary_id`) REFERENCES `payroll_summary` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_netpay`
--
ALTER TABLE `payroll_netpay`
  ADD CONSTRAINT `payroll_netpay_ibfk_1` FOREIGN KEY (`payroll_summary_id`) REFERENCES `payroll_summary` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_summary`
--
ALTER TABLE `payroll_summary`
  ADD CONSTRAINT `payroll_summary_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslip_history`
--
ALTER TABLE `payslip_history`
  ADD CONSTRAINT `payslip_history_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll_summary` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslip_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
