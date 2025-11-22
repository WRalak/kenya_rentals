-- Add images column to properties table if it doesn't exist
ALTER TABLE properties 
ADD COLUMN IF NOT EXISTS images JSON DEFAULT NULL;

-- Update existing properties with placeholder images
UPDATE properties SET images = '["/kenya_rentals/assets/images/properties/office1.jpg"]' WHERE images IS NULL;