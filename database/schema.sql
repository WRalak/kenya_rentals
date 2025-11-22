-- Create database
CREATE DATABASE IF NOT EXISTS kenya_rentals;
USE kenya_rentals;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('tenant', 'landlord') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Properties table
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    landlord_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('office', 'commercial', 'residential', 'garden', 'park', 'storage', 'event_space') NOT NULL,
    location VARCHAR(255) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    price_per_day DECIMAL(10, 2) NOT NULL,
    size_sqft INT,
    capacity INT,
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    amenities JSON,
    images JSON,
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests TEXT,
    landlord_notes TEXT,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    landlord_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    landlord_reply TEXT,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_booking_review (booking_id)
);

-- Favorites table
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (tenant_id, property_id)
);

-- Kenyan locations table
CREATE TABLE kenyan_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    area VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_popular BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample Kenyan locations
INSERT INTO kenyan_locations (city, area, is_popular) VALUES
('Nairobi', 'Westlands', TRUE),
('Nairobi', 'Kilimani', TRUE),
('Nairobi', 'Karen', TRUE),
('Nairobi', 'Lavington', TRUE),
('Nairobi', 'Upper Hill', TRUE),
('Nairobi', 'CBD', TRUE),
('Nairobi', 'Parklands', TRUE),
('Nairobi', 'South B', FALSE),
('Nairobi', 'South C', FALSE),
('Mombasa', 'Nyali', TRUE),
('Mombasa', 'Bamburi', TRUE),
('Mombasa', 'Mtwapa', TRUE),
('Mombasa', 'CBD', TRUE),
('Kisumu', 'Milimani', TRUE),
('Kisumu', 'CBD', TRUE),
('Nakuru', 'CBD', TRUE),
('Nakuru', 'Milimani', TRUE);

-- Insert sample admin user (password: password)
INSERT INTO users (username, email, password, user_type, full_name, phone) VALUES
('admin', 'admin@kenyarentals.co.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'landlord', 'System Administrator', '+254700000000');

-- Insert sample landlords
INSERT INTO users (username, email, password, user_type, full_name, phone) VALUES
('johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'landlord', 'John Doe', '+254711000001'),
('sarahk', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'landlord', 'Sarah Kimani', '+254711000002');

-- Insert sample tenants
INSERT INTO users (username, email, password, user_type, full_name, phone) VALUES
('alicew', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'Alice Wanjiku', '+254722000001'),
('bobk', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant', 'Bob Kamau', '+254722000002');

-- Insert sample properties
INSERT INTO properties (landlord_id, title, description, type, location, price_per_day, size_sqft, capacity, amenities) VALUES
(2, 'Modern Office Space in Westlands', 'A fully furnished modern office space in the heart of Westlands. Perfect for startups and established businesses.', 'office', 'Nairobi, Westlands', 8500, 1200, 15, '["WiFi", "Air Conditioning", "Meeting Rooms", "Parking", "Security"]'),
(2, 'Co-working Space in Kilimani', 'Flexible co-working space with hot desks and meeting rooms. Great for freelancers and remote workers.', 'office', 'Nairobi, Kilimani', 2500, 800, 20, '["WiFi", "Air Conditioning", "Meeting Rooms", "Coffee"]'),
(3, 'Beautiful Garden for Events', 'Spacious garden perfect for weddings, parties, and corporate events.', 'garden', 'Nairobi, Karen', 15000, 5000, 200, '["Outdoor Space", "Sound System", "Lighting", "Parking"]'),
(3, 'Commercial Shop Space', 'Ground floor commercial space suitable for retail business. High foot traffic area.', 'commercial', 'Nairobi, CBD', 5000, 800, 0, '["Street Front", "Security", "Storage"]');

-- Insert sample bookings
INSERT INTO bookings (tenant_id, property_id, start_date, end_date, total_days, total_amount, status) VALUES
(4, 1, '2024-02-01', '2024-02-05', 4, 34000, 'completed'),
(5, 3, '2024-02-10', '2024-02-10', 1, 15000, 'approved');

-- Create indexes for better performance
CREATE INDEX idx_properties_location ON properties(location);
CREATE INDEX idx_properties_type ON properties(type);
CREATE INDEX idx_properties_price ON properties(price_per_day);
CREATE INDEX idx_bookings_tenant ON bookings(tenant_id);
CREATE INDEX idx_bookings_property ON bookings(property_id);
CREATE INDEX idx_users_type ON users(user_type);