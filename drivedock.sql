CREATE DATABASE IF NOT EXISTS drivedock;
USE drivedock;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Files table
CREATE TABLE IF NOT EXISTS files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  path VARCHAR(500) NOT NULL DEFAULT '',       
  type VARCHAR(50) NOT NULL DEFAULT '',        
  size BIGINT UNSIGNED DEFAULT 0,
  is_directory TINYINT(1) DEFAULT 0,                   
  starred TINYINT(1) DEFAULT 0,                        
  trashed TINYINT(1) DEFAULT 0,                        
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  accessed_at TIMESTAMP NULL DEFAULT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  user_id INT UNSIGNED NOT NULL,
  UNIQUE KEY unique_file (user_id, path, name),
  CONSTRAINT fk_files_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);