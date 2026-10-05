-- =========================================================
-- YatraPath - Initial Seed Data for Nepal Tourism
-- =========================================================

USE `yatra_db`;

-- 1. Seed Users (Passwords: Admin@123, Traveler@123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@yatra.com', '$2y$12$D.reOJ762gGyMHVpy3kYX.qQ969yFOsfOIcbZPIBWZtUdQlJJILBK', 'admin', 'active'),
(2, 'Aarav Sharma', 'traveler@yatra.com', '$2y$12$oPK1pT9g5/Msd3dg8ykl6esBOfbXvDDX9httAauIHd5ektwdp.jU.', 'traveler', 'active'),
(3, 'Maya Adhikari', 'maya@yatra.com', '$2y$12$oPK1pT9g5/Msd3dg8ykl6esBOfbXvDDX9httAauIHd5ektwdp.jU.', 'traveler', 'active');

-- 2. Seed Destinations (with latitude, longitude, and realistic costs)
INSERT INTO `destinations` (`id`, `name`, `region`, `description`, `avg_cost_per_day`, `suitable_seasons`, `latitude`, `longitude`, `image_url`, `avg_rating`, `total_reviews`) VALUES
(1, 'Kathmandu Valley', 'Hilly', 'The cultural and historical capital of Nepal, renowned for UNESCO World Heritage Sites, lively bazaars, and medieval Newari architecture.', 3500.00, 'Spring,Autumn,Winter', 27.7172453, 85.3239605, 'uploads/destinations/kathmandu.jpg', 4.80, 24),
(2, 'Pokhara', 'Hilly', 'Tranquil lakes with stunning panoramic views of the Annapurna range, famous for adventure sports, caves, and scenic waterfalls.', 4200.00, 'Spring,Autumn,Winter', 28.2095831, 83.9855670, 'uploads/destinations/pokhara.jpg', 4.90, 42),
(3, 'Chitwan National Park', 'Terai', 'Dense tropical jungle sanctuary home to the endangered one-horned rhinoceros, Royal Bengal tigers, and traditional Tharu villages.', 4000.00, 'Autumn,Winter,Spring', 27.5291244, 84.4533036, 'uploads/destinations/chitwan.jpg', 4.75, 18),
(4, 'Mustang (Muktinath & Jomsom)', 'Himalayan', 'Rain-shadow arid trans-Himalayan plateau with ancient caves, sacred Muktinath temple, apple orchards, and Tibetan culture.', 6000.00, 'Spring,Summer,Autumn', 28.7840134, 83.7431201, 'uploads/destinations/mustang.jpg', 4.85, 15),
(5, 'Lumbini', 'Terai', 'The sacred birthplace of Lord Buddha, featuring monastic zones, the Maya Devi Temple, and ancient Ashoka Pillar.', 2800.00, 'Autumn,Winter,Spring', 27.4840228, 83.2760334, 'uploads/destinations/lumbini.jpg', 4.70, 12);

-- 3. Seed Activities (Categorized, slotted, and costed in NPR)
INSERT INTO `activities` (`destination_id`, `name`, `category`, `duration_hours`, `cost_npr`, `preferred_slot`, `suitable_seasons`) VALUES
-- Kathmandu Activities
(1, 'Pashupatinath Temple Morning Rituals', 'cultural', 2.00, 1000.00, 'Morning', 'Spring,Summer,Autumn,Winter'),
(1, 'Boudhanath Stupa Circumambulation', 'cultural', 1.50, 400.00, 'Afternoon', 'Spring,Summer,Autumn,Winter'),
(1, 'Thamel Street Food & Spices Tour', 'food', 2.00, 1200.00, 'Evening', 'Spring,Summer,Autumn,Winter'),
(1, 'Swayambhunath (Monkey Temple) Sunset View', 'photography', 2.00, 500.00, 'Evening', 'Spring,Autumn,Winter'),
(1, 'Bhaktapur Durbar Square Heritage Walk', 'cultural', 3.00, 1500.00, 'Morning', 'Spring,Autumn,Winter'),
(1, 'Shivapuri National Park Day Hike', 'adventure', 4.50, 800.00, 'Morning', 'Spring,Autumn'),
(1, 'Traditional Newari Khaja Thali Experience', 'food', 1.50, 750.00, 'Afternoon', 'Spring,Summer,Autumn,Winter'),
(1, 'Sound Healing & Yoga Meditation Session', 'wellness', 1.50, 1500.00, 'Evening', 'Spring,Summer,Autumn,Winter'),

-- Pokhara Activities
(2, 'Sarangkot Sunrise & Annapurna Panorama', 'photography', 2.50, 1200.00, 'Morning', 'Spring,Autumn,Winter'),
(2, 'Tandem Paragliding over Phewa Lake', 'adventure', 2.00, 7500.00, 'Morning', 'Spring,Autumn,Winter'),
(2, 'Phewa Lake Wooden Boat Ride to Tal Barahi', 'nature', 2.00, 800.00, 'Afternoon', 'Spring,Summer,Autumn,Winter'),
(2, 'World Peace Pagoda Hike & Sunset', 'wellness', 2.50, 500.00, 'Evening', 'Spring,Autumn,Winter'),
(2, 'Gupteshwor Mahadev Cave & Davis Falls', 'cultural', 2.00, 350.00, 'Afternoon', 'Spring,Summer,Autumn,Winter'),
(2, 'Lakeside Café Acoustic Evening & Local Trout', 'food', 2.00, 1400.00, 'Evening', 'Spring,Autumn,Winter'),

-- Chitwan Activities
(3, 'Early Morning Bird Watching in Subtropical Forest', 'nature', 2.50, 600.00, 'Morning', 'Autumn,Winter,Spring'),
(3, 'Jeep Safari for Rhino & Wildlife Spotting', 'adventure', 4.00, 3500.00, 'Morning', 'Autumn,Winter,Spring'),
(3, 'Peaceful Rapti River Dugout Canoe Ride', 'nature', 1.50, 800.00, 'Afternoon', 'Autumn,Winter,Spring'),
(3, 'Tharu Cultural Dance & Folk Performance', 'cultural', 2.00, 400.00, 'Evening', 'Autumn,Winter,Spring'),
(3, 'Sunset at Elephant Breeding Center & River Bank', 'photography', 1.50, 300.00, 'Evening', 'Autumn,Winter,Spring'),

-- Mustang Activities
(4, 'Muktinath Sacred 108 Water Spouts Visit', 'cultural', 2.50, 500.00, 'Morning', 'Spring,Summer,Autumn'),
(4, 'Marpha White-Washed Village & Apple Orchard Walk', 'cultural', 2.00, 300.00, 'Afternoon', 'Spring,Summer,Autumn'),
(4, 'Kaligandaki Gorge Windy Valley Photography', 'photography', 2.00, 0.00, 'Afternoon', 'Spring,Summer,Autumn'),
(4, 'Tasting Authentic Thakali Thali Dinner', 'food', 1.50, 850.00, 'Evening', 'Spring,Summer,Autumn'),

-- Lumbini Activities
(5, 'Maya Devi Temple & Sacred Bodhi Tree Meditation', 'wellness', 2.50, 500.00, 'Morning', 'Autumn,Winter,Spring'),
(5, 'Monastic Zone E-Bicycle Exploration', 'cultural', 3.00, 600.00, 'Afternoon', 'Autumn,Winter,Spring'),
(5, 'World Peace Flame Evening Reflection', 'wellness', 1.50, 0.00, 'Evening', 'Autumn,Winter,Spring');

-- 4. Seed Sample Reviews
INSERT INTO `reviews` (`user_id`, `destination_id`, `rating`, `comment`, `trip_month_year`) VALUES
(2, 2, 5, 'Pokhara was absolutely magical. The Sarangkot sunrise over the Annapurnas is an unforgettable memory.', 'October 2025'),
(3, 1, 5, 'Kathmandu has such rich living history. The food tour in Thamel was the highlight of our trip!', 'November 2025'),
(2, 3, 4, 'Chitwan was a thrilling experience. We spotted two rhinos on our morning safari!', 'December 2025');
