-- Performance optimization queries

-- Analyze tables for optimal performance
ANALYZE TABLE users, properties, bookings, reviews, favorites, payments;

-- Create additional indexes for frequently queried fields
CREATE INDEX idx_properties_landlord ON properties(landlord_id);
CREATE INDEX idx_bookings_dates_status ON bookings(start_date, end_date, status);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- Create full-text search index for property search
ALTER TABLE properties ADD FULLTEXT(title, description, location);

-- Create stored procedure for calculating booking totals
DELIMITER //
CREATE PROCEDURE CalculateBookingTotal(
    IN property_id INT,
    IN start_date DATE,
    IN end_date DATE,
    OUT total_days INT,
    OUT total_amount DECIMAL(10,2)
)
BEGIN
    DECLARE daily_price DECIMAL(10,2);
    DECLARE days_count INT;
    
    -- Get daily price
    SELECT price_per_day INTO daily_price FROM properties WHERE id = property_id;
    
    -- Calculate days
    SET days_count = DATEDIFF(end_date, start_date);
    IF days_count < 1 THEN SET days_count = 1; END IF;
    
    -- Calculate total
    SET total_days = days_count;
    SET total_amount = daily_price * days_count;
END//
DELIMITER ;

-- Create trigger to update property views count
DELIMITER //
CREATE TRIGGER after_property_view_insert 
AFTER INSERT ON property_views
FOR EACH ROW
BEGIN
    UPDATE properties 
    SET views_count = views_count + 1 
    WHERE id = NEW.property_id;
END//
DELIMITER ;

-- Create trigger to calculate booking days and amount
DELIMITER //
CREATE TRIGGER before_booking_insert 
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
    DECLARE daily_price DECIMAL(10,2);
    DECLARE days_count INT;
    
    -- Get daily price
    SELECT price_per_day INTO daily_price FROM properties WHERE id = NEW.property_id;
    
    -- Calculate days
    SET days_count = DATEDIFF(NEW.end_date, NEW.start_date);
    IF days_count < 1 THEN SET days_count = 1; END IF;
    
    -- Set calculated values
    SET NEW.total_days = days_count;
    SET NEW.total_amount = daily_price * days_count;
END//
DELIMITER ;