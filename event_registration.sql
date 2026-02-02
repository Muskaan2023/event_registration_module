-- Event Registration Module Database Schema
-- Run this SQL to create tables manually if needed

-- Event configuration table
CREATE TABLE IF NOT EXISTS `event_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_name` varchar(255) NOT NULL,
  `event_category` varchar(50) NOT NULL,
  `registration_start_date` date NOT NULL,
  `registration_end_date` date NOT NULL,
  `event_date` date NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Event registration table
CREATE TABLE IF NOT EXISTS `event_registration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `event_category` varchar(50) NOT NULL,
  `event_date` date NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_config_id` int DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email_event` (`email`,`event_date`),
  KEY `event_config_id` (`event_config_id`),
  CONSTRAINT `event_registration_ibfk_1` FOREIGN KEY (`event_config_id`) REFERENCES `event_config` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for testing
INSERT INTO `event_config` (`event_name`, `event_category`, `registration_start_date`, `registration_end_date`, `event_date`) VALUES
('Web Development Workshop', 'Online Workshop', '2024-01-01', '2024-12-31', '2024-06-15'),
('AI Hackathon 2024', 'Hackathon', '2024-02-01', '2024-11-30', '2024-07-20'),
('Tech Conference', 'Conference', '2024-03-01', '2024-10-31', '2024-08-25'),
('Data Science Bootcamp', 'One-day Workshop', '2024-01-15', '2024-09-30', '2024-05-10');