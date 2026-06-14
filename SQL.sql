-- ===============================
-- CREATE DATABASE
-- ===============================
CREATE DATABASE IF NOT EXISTS namias_db;
USE namias_db;

-- ===============================
-- USERS
-- Passwords are stored HASHED (bcrypt, 60 chars) -> use VARCHAR(255).
-- A default admin (admin / admin123) is created automatically by the
-- app on first run if this table is empty, so no plaintext seed here.
-- ===============================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('ADMIN','USER') DEFAULT 'USER'
);

-- ===============================
-- CATEGORIES
-- ===============================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

-- ===============================
-- DEPARTMENTS
-- ===============================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

-- ===============================
-- PLACES
-- ===============================
CREATE TABLE places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

-- ===============================
-- ASSETS
-- ===============================
CREATE TABLE assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    brand VARCHAR(150),
    category VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    department VARCHAR(150) NOT NULL,
    place VARCHAR(150) NOT NULL,
    warranty_start DATE NULL,
    warranty_end DATE NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================
-- IP ALLOCATIONS
-- ===============================
CREATE TABLE ip_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    cidr VARCHAR(20) NOT NULL,
    vlan_id INT NOT NULL,
    vlan_name VARCHAR(100) NOT NULL,
    purpose VARCHAR(150),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (ip_address),
    UNIQUE (vlan_id)
);

-- ===============================
-- WISHLIST
-- ===============================
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    brand VARCHAR(100),
    quantity INT NOT NULL DEFAULT 1,
    desired_department VARCHAR(100) NOT NULL,
    details TEXT,
    status ENUM('Pending','Approved','Ordered','Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
