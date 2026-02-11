-- ============================================================
-- Delivery App Database Schema
-- MySQL 5.7+ / MariaDB 10.2+
-- ============================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS delivery_app;
USE delivery_app;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'customer', 'driver') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PRODUCTS TABLE
-- ============================================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    image_path VARCHAR(255),
    stock INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ORDERS TABLE
-- ============================================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    driver_id INT,
    total DECIMAL(10, 2) NOT NULL,
    address TEXT,
    status ENUM('pending', 'accepted', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_driver_id (driver_id),
    INDEX idx_status (status),
    INDEX idx_completed (completed_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ORDER IMAGES TABLE (Delivery Proof)
-- ============================================================
CREATE TABLE order_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    type ENUM('delivered', 'receipt', 'location', 'signature', 'other') DEFAULT 'other',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VERIFICATIONS TABLE (ID Verification)
-- ============================================================
CREATE TABLE verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    id_type ENUM('driver_license', 'national_id', 'passport') NOT NULL,
    front_image VARCHAR(255),
    back_image VARCHAR(255),
    selfie VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    admin_note TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERT DEMO ADMIN USER
-- ============================================================
-- Username: admin
-- Password: password123
-- Password hash generated with password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO users (username, password_hash, email, phone, role, status) 
VALUES (
    'admin',
    '$2y$10$vvCOAzL9sPUGKZOB9vkNSOP0EzNONx.XOvfSTcWR2M0KqzePxSLhC',
    'admin@delivery.app',
    '+1234567890',
    'admin',
    'active'
);

-- ============================================================
-- INSERT SAMPLE DATA (Optional)
-- ============================================================

-- Sample Products
INSERT INTO products (name, description, price, category, stock, is_active) VALUES
('Margherita Pizza', 'Classic pizza with tomato, mozzarella, and basil', 8.99, 'Food', 25, 1),
('Caesar Salad', 'Fresh romaine lettuce with parmesan and croutons', 6.99, 'Food', 30, 1),
('Orange Juice', 'Fresh squeezed orange juice', 3.99, 'Beverages', 50, 1),
('Apples (1 kg)', 'Fresh red apples', 4.99, 'Groceries', 100, 1),
('Whole Wheat Bread', 'Organic whole wheat bread loaf', 2.99, 'Groceries', 40, 1),
('Chocolate Cake', 'Rich chocolate layer cake', 5.99, 'Food', 15, 1),
('Mineral Water Bottle', '500ml mineral water bottle', 1.99, 'Beverages', 200, 1),
('Tomato Sauce', 'Italian tomato pasta sauce', 3.49, 'Groceries', 80, 1);

-- Sample Users (Customers)
INSERT INTO users (username, email, phone, role, status) VALUES
('john_customer', 'john@email.com', '+1234567890', 'customer', 'active'),
('jane_customer', 'jane@email.com', '+1234567891', 'customer', 'active'),
('bob_customer', 'bob@email.com', '+1234567892', 'customer', 'active');

-- Sample Drivers
INSERT INTO users (username, email, phone, role, status) VALUES
('driver_mike', 'mike@email.com', '+1234567893', 'driver', 'active'),
('driver_sarah', 'sarah@email.com', '+1234567894', 'driver', 'active'),
('driver_alex', 'alex@email.com', '+1234567895', 'driver', 'active');

-- Sample Orders
INSERT INTO orders (user_id, driver_id, total, address, status, payment_status, payment_method, completed_at) VALUES
(2, 4, 24.97, '123 Main Street, City, State 12345', 'completed', 'completed', 'credit_card', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 5, 18.98, '456 Oak Avenue, City, State 12346', 'completed', 'completed', 'cash', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 6, 12.99, '789 Pine Road, City, State 12347', 'completed', 'completed', 'paypal', NOW()),
(2, 4, 15.99, '321 Elm Street, City, State 12348', 'in_transit', 'pending', 'credit_card', NULL),
(3, 5, 9.98, '654 Maple Drive, City, State 12349', 'accepted', 'pending', 'cash', NULL);

-- Sample Order Items (for completed orders)
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 8.99),
(1, 2, 1, 6.99),
(1, 3, 1, 3.99),
(2, 4, 2, 4.99),
(2, 5, 1, 2.99),
(3, 6, 1, 5.99),
(3, 7, 1, 1.99),
(3, 8, 1, 3.49);

-- Sample Order Images (Delivery Proof)
INSERT INTO order_images (order_id, image_path, type) VALUES
(1, 'uploads/delivery_001.jpg', 'delivered'),
(1, 'uploads/receipt_001.jpg', 'receipt'),
(2, 'uploads/delivery_002.jpg', 'delivered'),
(3, 'uploads/delivery_003.jpg', 'delivered'),
(3, 'uploads/location_003.jpg', 'location');

-- Sample Verifications (Pending)
INSERT INTO verifications (user_id, id_type, front_image, back_image, selfie, status) VALUES
(4, 'driver_license', 'uploads/driver_front_001.jpg', 'uploads/driver_back_001.jpg', 'uploads/selfie_001.jpg', 'pending'),
(5, 'national_id', 'uploads/id_front_002.jpg', 'uploads/id_back_002.jpg', 'uploads/selfie_002.jpg', 'pending'),
(6, 'passport', 'uploads/passport_003.jpg', 'uploads/passport_back_003.jpg', 'uploads/selfie_003.jpg', 'approved');

-- ============================================================
-- VERIFY INSTALLATION
-- ============================================================
-- Run these queries to verify tables were created:
-- SELECT COUNT(*) as user_count FROM users;
-- SELECT COUNT(*) as product_count FROM products;
-- SELECT COUNT(*) as order_count FROM orders;
-- SELECT * FROM users WHERE role = 'admin';

-- ============================================================
-- END OF SCHEMA
-- ============================================================
