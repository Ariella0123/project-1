CREATE DATABASE IF NOT EXISTS parcel_delivery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parcel_delivery;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'rider') NOT NULL DEFAULT 'rider',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE riders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    phone VARCHAR(40) DEFAULT NULL,
    vehicle_info VARCHAR(160) DEFAULT NULL,
    plate_number VARCHAR(60) DEFAULT NULL,
    current_status ENUM('online', 'offline') NOT NULL DEFAULT 'offline',
    latitude DECIMAL(10, 7) DEFAULT NULL,
    longitude DECIMAL(10, 7) DEFAULT NULL,
    last_location_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_riders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE parcels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tracking_number VARCHAR(40) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(40) DEFAULT NULL,
    customer_address TEXT NOT NULL,
    customer_latitude DECIMAL(10, 7) DEFAULT NULL,
    customer_longitude DECIMAL(10, 7) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('Pending', 'Out for Delivery', 'Delivered', 'Failed Delivery') NOT NULL DEFAULT 'Pending',
    assigned_rider_id INT UNSIGNED DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    delivered_at DATETIME DEFAULT NULL,
    failed_reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_parcels_rider FOREIGN KEY (assigned_rider_id) REFERENCES riders(id) ON DELETE SET NULL,
    CONSTRAINT fk_parcels_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE parcel_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT UNSIGNED NOT NULL,
    rider_id INT UNSIGNED DEFAULT NULL,
    status VARCHAR(40) NOT NULL,
    remarks TEXT DEFAULT NULL,
    location_latitude DECIMAL(10, 7) DEFAULT NULL,
    location_longitude DECIMAL(10, 7) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_parcel FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_rider FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rider_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rider_id INT UNSIGNED NOT NULL,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    accuracy DECIMAL(10, 2) DEFAULT NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'browser',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rider_locations_rider_time (rider_id, created_at),
    CONSTRAINT fk_locations_rider FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE delivery_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT UNSIGNED NOT NULL,
    rider_id INT UNSIGNED NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_photos_parcel FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE,
    CONSTRAINT fk_photos_rider FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(120) NOT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_user_time (user_id, created_at),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (name, email, password_hash, role, status) VALUES
('System Admin', 'admin@example.com', '$2y$12$yFsHZzjEzir3mEtXYpNi7.pzzMdb/.r2day1P6W6NPz1.zZ9mZ19C', 'admin', 'active'),
('Demo Rider', 'rider@example.com', '$2y$12$pQzUV3rD671Tb1BkAdptRO5.ITa/9IaBNKpG0ZKBSzWu.QFzrMLAW', 'rider', 'active');

INSERT INTO riders (user_id, phone, vehicle_info, plate_number, current_status) VALUES
(2, '012-3456789', 'Motorbike', 'WXY 1234', 'offline');
