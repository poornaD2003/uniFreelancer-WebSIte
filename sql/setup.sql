CREATE DATABASE IF NOT EXISTS unilance_db;
USE unilance_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'client', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gigs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(255) NOT NULL,
    status ENUM('approve', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_profiles (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE,
    university_name   VARCHAR(200) NOT NULL,
    faculty           VARCHAR(150) NOT NULL,
    department        VARCHAR(150) NOT NULL,
    club_affiliations TEXT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS client_profiles (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE,
    business_name VARCHAR(200) NOT NULL,
    business_address VARCHAR(255),
    business_type VARCHAR(100) NOT NULL,
    business_phone VARCHAR(20) NOT NULL,
    business_website VARCHAR(255),
    business_image VARCHAR(255) NOT NULL DEFAULT 'default.png',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders(
    orderId int AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    student_id INT NOT NULL,
    gig_id INT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (gig_id) REFERENCES gigs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payment(
    paymentId INT AUTO_INCREMENT PRIMARY KEY,
    orderId INT NOT NULL,
    client_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orderId) REFERENCES orders(orderId) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
