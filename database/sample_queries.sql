-- Useful queries for the application

-- Get available properties in Nairobi with prices
SELECT 
    p.title,
    p.type,
    p.location,
    p.price_per_day,
    u.full_name as landlord_name,
    AVG(r.rating) as average_rating,
    COUNT(DISTINCT r.id) as review_count
FROM properties p
JOIN users u ON p.landlord_id = u.id
LEFT JOIN reviews r ON p.id = r.property_id
WHERE p.is_available = TRUE 
AND p.location LIKE '%Nairobi%'
GROUP BY p.id, p.title, p.type, p.location, p.price_per_day, u.full_name
ORDER BY p.price_per_day ASC;

-- Get landlord dashboard statistics
SELECT 
    u.full_name,
    COUNT(p.id) as total_properties,
    SUM(p.views_count) as total_views,
    COUNT(b.id) as total_bookings,
    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN b.status IN ('approved', 'completed') THEN b.total_amount ELSE 0 END) as total_revenue
FROM users u
LEFT JOIN properties p ON u.id = p.landlord_id
LEFT JOIN bookings b ON p.id = b.property_id
WHERE u.id = 2  -- Replace with actual landlord ID
GROUP BY u.full_name;

-- Get tenant booking history with property details
SELECT 
    b.start_date,
    b.end_date,
    b.total_amount,
    b.status,
    p.title,
    p.type,
    p.location,
    u.full_name as landlord_name,
    u.phone as landlord_phone
FROM bookings b
JOIN properties p ON b.property_id = p.id
JOIN users u ON p.landlord_id = u.id
WHERE b.tenant_id = 5  -- Replace with actual tenant ID
ORDER BY b.created_at DESC;

-- Get property availability for specific dates
SELECT 
    p.title,
    p.location,
    p.price_per_day,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM bookings b 
            WHERE b.property_id = p.id 
            AND b.status IN ('approved', 'pending')
            AND ('2024-02-15' BETWEEN b.start_date AND b.end_date
                 OR '2024-02-20' BETWEEN b.start_date AND b.end_date
                 OR (b.start_date BETWEEN '2024-02-15' AND '2024-02-20')
                 OR (b.end_date BETWEEN '2024-02-15' AND '2024-02-20'))
        ) THEN 'Not Available'
        ELSE 'Available'
    END as availability
FROM properties p
WHERE p.is_available = TRUE
AND p.location LIKE '%Nairobi%';

-- Get popular locations in Kenya
SELECT 
    city,
    area,
    COUNT(p.id) as property_count,
    AVG(p.price_per_day) as average_price
FROM kenyan_locations kl
LEFT JOIN properties p ON CONCAT(kl.city, ', ', kl.area) = p.location
WHERE kl.is_popular = TRUE
GROUP BY kl.city, kl.area
ORDER BY property_count DESC;

-- Get monthly revenue report for landlord
SELECT 
    YEAR(created_at) as year,
    MONTH(created_at) as month,
    COUNT(*) as booking_count,
    SUM(total_amount) as revenue,
    AVG(total_amount) as average_booking_value
FROM bookings
WHERE property_id IN (SELECT id FROM properties WHERE landlord_id = 2)
AND status IN ('completed', 'approved')
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY year DESC, month DESC;