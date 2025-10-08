CREATE DATABASE IF NOT EXISTS smilebright
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smilebright;

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50),
  last_name  VARCHAR(50),
  email      VARCHAR(255),
  phone      VARCHAR(20),
  `date`     DATE,
  `time`     VARCHAR(10),
  clinic     VARCHAR(50),
  service    VARCHAR(80),
  message    TEXT,
  consent    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
