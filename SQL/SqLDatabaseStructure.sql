SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


DROP TABLE IF EXISTS `businesses`;
CREATE TABLE IF NOT EXISTS `businesses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `business_type` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `registration_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_business_owner_id` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `businesses` (`id`, `name`, `owner_id`, `business_type`, `address`, `registration_date`) VALUES
(1, 'Tech Innovations', 1, 'Technology', '789 Tech Blvd', '2019-07-01'),
(2, 'Health Solutions', 2, 'Healthcare', '123 Health St', '2020-09-01');

DROP TABLE IF EXISTS `civilians`;
CREATE TABLE IF NOT EXISTS `civilians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `occupation` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `person_code` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `person_code` (`person_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `civilians` (`id`, `username`, `password`, `first_name`, `last_name`, `gender`, `email`, `phone`, `address`, `occupation`, `dob`, `person_code`) VALUES
(1, 'john_doe', 'hashed_password1', 'John', 'Doe', 'male', 'john.doe@example.com', '1234567890', '123 Main St', 'Engineer', '1980-01-01', '1980-1234'),
(2, 'jane_smith', 'hashed_password2', 'Jane', 'Smith', 'female', 'jane.smith@example.com', '0987654321', '456 Elm St', 'Doctor', '1990-02-02', '1990-5678'),

DROP TABLE IF EXISTS `court_charges`;
CREATE TABLE IF NOT EXISTS `court_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` varchar(50) NOT NULL,
  `charge` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `court_documents`;
CREATE TABLE IF NOT EXISTS `court_documents` (
  `document_id` varchar(50) NOT NULL,
  `court_name` varchar(255) NOT NULL,
  `person_name` varchar(255) NOT NULL,
  `court_date` date NOT NULL,
  `judge_name` varchar(255) NOT NULL,
  `case_number` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `court_files`;
CREATE TABLE IF NOT EXISTS `court_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_number` varchar(50) NOT NULL,
  `case_title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `filing_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_number` (`file_number`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `court_files` (`id`, `file_number`, `case_title`, `description`, `filing_date`) VALUES
(1, 'CF123456', 'State vs John Doe', 'Theft case against John Doe', '2022-01-10');

DROP TABLE IF EXISTS `criminal_records`;
CREATE TABLE IF NOT EXISTS `criminal_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `crime_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `crime_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `dispatch_notifications`;
CREATE TABLE IF NOT EXISTS `dispatch_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_text` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `dispatch_notifications` (`id`, `notification_text`, `date`) VALUES
(1, 'All units respond to a robbery in progress at 123 Main St', '2024-06-23 23:15:15');

DROP TABLE IF EXISTS `government_operations`;
CREATE TABLE IF NOT EXISTS `government_operations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operation_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `government_operations` (`id`, `operation_type`, `description`, `date`) VALUES
(1, 'Public Health', 'COVID-19 vaccination drive', '2021-01-15'),
(2, 'Infrastructure', 'Road construction project', '2021-09-01');

DROP TABLE IF EXISTS `licenses`;
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `civilian_id` int(11) NOT NULL,
  `license_type` enum('car','bike','scooter') NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `expiration_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_number` (`license_number`),
  KEY `idx_license_civilian_id` (`civilian_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `licenses` (`id`, `civilian_id`, `license_type`, `license_number`, `issue_date`, `expiration_date`, `expiry_date`) VALUES
(1, 1, 'car', 'CAR123456', '2022-01-01', '2026-01-01', NULL),
(2, 2, 'bike', 'BIKE654321', '2021-06-01', '2025-06-01', NULL);

DROP TABLE IF EXISTS `operations`;
CREATE TABLE IF NOT EXISTS `operations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operation_name` varchar(255) NOT NULL,
  `operation_type` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `police_records`;
CREATE TABLE IF NOT EXISTS `police_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `officer_id` int(11) NOT NULL,
  `record_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_officer_id` (`officer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `police_records` (`id`, `officer_id`, `record_type`, `description`, `date`) VALUES
(1, 1, 'Patrol', 'Patrolled downtown area', '2022-05-10'),
(2, 2, 'Investigation', 'Investigated a robbery', '2022-06-15');

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','moderator','officer','user') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$5fxugaLoQQh0zse1JWorTeebiBR4.nyE4bgHXHgOHO4UGZzkz/zn.', 'admin');

DROP TABLE IF EXISTS `warrants`;
CREATE TABLE IF NOT EXISTS `warrants` (
  `warrant_id` varchar(20) NOT NULL,
  `civilian_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `status` enum('issued','pending','cancelled') NOT NULL DEFAULT 'issued',
  `warrant_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`warrant_id`),
  KEY `civilian_id` (`civilian_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `civilians` (`id`);

ALTER TABLE `court_charges`
  ADD CONSTRAINT `court_charges_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `court_documents` (`document_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `licenses`
  ADD CONSTRAINT `licenses_ibfk_1` FOREIGN KEY (`civilian_id`) REFERENCES `civilians` (`id`);

ALTER TABLE `police_records`
  ADD CONSTRAINT `police_records_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `civilians` (`id`);

ALTER TABLE `warrants`
  ADD CONSTRAINT `warrants_ibfk_1` FOREIGN KEY (`civilian_id`) REFERENCES `civilians` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
