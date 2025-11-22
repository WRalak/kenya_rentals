-- Useful views for reporting and analytics

-- Property bookings summary view
CREATE VIEW property_bookings_summary AS
SELECT 
    p.id as property_id,
    p.title,
    p.location,
    p.type,
    p.price_per_day,
    u.full_name as landlord_name,
    COUNT(b.id) as total_bookings,
    SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_bookings,
    AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating END) as average_rating,
    SUM(CASE WHEN b.status IN ('completed', 'approved') THEN b.total_amount ELSE 0 END) as total_revenue
FROM properties p
LEFT JOIN users u ON p.landlord_id = u.id
LEFT JOIN bookings b ON p.id = b.property_id
LEFT JOIN reviews r ON p.id = r.property_id
GROUP BY p.id, p.title, p.location, p.type, p.price_per_day, u.full_name;

-- Monthly revenue view for landlords
CREATE VIEW monthly_revenue AS
SELECT 
    p.landlord_id,
    u.full_name as landlord_name,
    YEAR(b.created_at) as year,
    MONTH(b.created_at) as month,
    COUNT(b.id) as booking_count,
    SUM(b.total_amount) as total_revenue,
    AVG(b.total_amount) as average_booking_value
FROM bookings b
JOIN properties p ON b.property_id = p.id
JOIN users u ON p.landlord_id = u.id
WHERE b.status IN ('completed', 'approved')
GROUP BY p.landlord_id, u.full_name, YEAR(b.created_at), MONTH(b.created_at);

-- Tenant booking history view
CREATE VIEW tenant_booking_history AS
SELECT 
    t.id as tenant_id,
    t.full_name as tenant_name,
    t.email as tenant_email,
    b.id as booking_id,
    p.title as property_title,
    p.type as property_type,
    p.location,
    b.start_date,
    b.end_date,
    b.total_amount,
    b.status,
    r.rating,
    r.comment as review
FROM users t
JOIN bookings b ON t.id = b.tenant_id
JOIN properties p ON b.property_id = p.id
LEFT JOIN reviews r ON b.id = r.booking_id
WHERE t.user_type = 'tenant';

-- Property availability calendar view
CREATE VIEW property_availability AS
SELECT 
    p.id as property_id,
    p.title,
    p.location,
    p.price_per_day,
    d.date,
    CASE 
        WHEN b.id IS NOT NULL AND b.status IN ('approved', 'pending') THEN 'booked'
        ELSE 'available'
    END as availability_status
FROM properties p
CROSS JOIN (
    SELECT CURDATE() + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
    FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c
) d
LEFT JOIN bookings b ON p.id = b.property_id 
    AND d.date BETWEEN b.start_date AND b.end_date 
    AND b.status IN ('approved', 'pending')
WHERE d.date BETWEEN CURDATE() AND CURDATE() + INTERVAL 90 DAY;