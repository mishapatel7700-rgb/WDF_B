CREATE DATABASE IF NOT EXISTS charusat_portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE charusat_portal;

-- If you already have a users table, back it up first and compare its columns
-- before running this file. CREATE TABLE IF NOT EXISTS will not alter old tables.
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL UNIQUE,
  mobile VARCHAR(10) NOT NULL,
  password VARCHAR(255) NOT NULL,
  course VARCHAR(30) NOT NULL,
  year TINYINT UNSIGNED NOT NULL,
  gender ENUM('Male','Female','Other') NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  remember_token_hash CHAR(64) DEFAULT NULL,
  remember_token_expires DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  roll_no VARCHAR(30) NOT NULL UNIQUE,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(254) NOT NULL,
  mobile VARCHAR(10) DEFAULT NULL,
  course VARCHAR(30) NOT NULL,
  year TINYINT UNSIGNED NOT NULL,
  gender ENUM('Male','Female','Other') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- IMPORTANT:
-- New public registrations receive role='student'. To grant admin access,
-- first register your own account, then run this manually in phpMyAdmin:
-- UPDATE users SET role='admin' WHERE email='your-admin-email@example.com';


CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  event_date DATETIME NOT NULL,
  location VARCHAR(180) NOT NULL,
  poster_path VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_events_date (event_date),
  CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
