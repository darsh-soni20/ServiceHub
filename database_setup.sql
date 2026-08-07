USE servicemaster;

-- Admin credentials (re-runnable with INSERT IGNORE)
INSERT IGNORE INTO admin (adminid, username, password) VALUES
(1, 'admin@servicehub.com', 'Admin@123');

-- Service categories
INSERT IGNORE INTO services (serviceid, name, description) VALUES
(1, 'Home Cleaning', 'Professional home cleaning services including deep cleaning, regular cleaning, and move-in/move-out cleaning.'),
(2, 'Plumbing', 'Pipe repair, installation, leak fixing, drain cleaning, and all plumbing maintenance services.'),
(3, 'Electrical', 'Wiring, repairs, installations, switchboard fixing, and electrical maintenance.'),
(4, 'Painting', 'Interior and exterior painting, wall texturing, waterproofing, and color consultation.'),
(5, 'AC Repair', 'AC installation, repair, gas refilling, servicing, and annual maintenance contracts.');

-- Sample customers
INSERT IGNORE INTO users (userid, name, email, phone, address, city, pincode, password) VALUES
(1, 'Rahul Kumar', 'rahul@example.com', '9111122222', 'Flat 101, Sunshine Apartments', 'Mumbai', '400001', 'Customer@123'),
(2, 'Priya Sharma', 'priya@example.com', '9222233333', '25 Rose Garden Colony', 'Delhi', '110001', 'Customer@123');

-- Sample providers
INSERT IGNORE INTO providers (providerid, name, email, phone, category, experience, address, city, pincode, document, status, password) VALUES
(1, 'Ravi Kumar', 'ravi.kumar@email.com', '9876543210', 'Home Cleaning', 5, '123 Main Street', 'Mumbai', '400001', 'doc_ravi.pdf', 'Active', 'Provider@123'),
(2, 'Suresh Sharma', 'sharma.elec@email.com', '8765432109', 'Electrical', 8, 'Andheri West', 'Mumbai', '400053', 'doc_suresh.pdf', 'Active', 'Provider@123'),
(3, 'Anil Singh', 'anil.plumb@email.com', '7654321098', 'Plumbing', 6, 'Dadar East', 'Mumbai', '400014', 'doc_anil.pdf', 'Active', 'Provider@123');

-- Sample bookings
INSERT IGNORE INTO booking (bookingid, userid, providerid, serviceid, date, time, status, description, paymentmode, amount) VALUES
(1, 1, 1, 1, '2026-07-13', '10:00:00', 'confirmed', 'Regular deep cleaning needed', 'online', 1500.00),
(2, 2, 3, 2, '2026-07-12', '14:30:00', 'pending', 'Kitchen sink leak repair', 'cash', 800.00),
(3, 1, 2, 3, '2026-07-18', '11:00:00', 'confirmed', 'Fan installation', 'online', 600.00);

-- Table structure for sub_services
CREATE TABLE IF NOT EXISTS `sub_services` (
  `sub_serviceid` int(11) NOT NULL AUTO_INCREMENT,
  `serviceid` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `badge` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`sub_serviceid`),
  KEY `serviceid` (`serviceid`),
  CONSTRAINT `fk_subservices_service` FOREIGN KEY (`serviceid`) REFERENCES `services` (`serviceid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Sample sub_services
INSERT IGNORE INTO sub_services (subservice_id, serviceid, name, description, price) VALUES
(1, 1, 'Basic Home Cleaning', 'Essential dusting, sweeping, mopping for 1-2 BHK', 499.00),
(2, 1, 'Standard Deep Cleaning', 'Deep sanitization, kitchen & bathroom scrub', 999.00),
(3, 1, 'Premium Full Home Cleaning', 'Complete deep cleaning with sofa & carpet shampooing', 1999.00),
(4, 2, 'Basic Leakage Repair', 'Quick fix for tap, pipe, or sink leaks', 299.00),
(5, 2, 'Standard Plumbing Fitting', 'Installation of taps, showers, washbasins', 599.00),
(6, 2, 'Premium Pipe Overhaul', 'Full bathroom/kitchen pipeline replacement & unblocking', 1499.00),
(7, 3, 'Basic Switch & Socket Repair', 'Fixing switches, fuses, small wiring faults', 199.00),
(8, 3, 'Standard Appliance Wiring', 'Fan, light fixture, or heavy socket installation', 499.00),
(9, 3, 'Premium DB Board Setup', 'Complete distribution board & MCB panel setup', 1299.00),
(10, 5, 'Basic AC Jet Service', 'Filter cleaning & high-pressure water jet wash', 399.00),
(11, 5, 'Standard Gas Refilling', 'Full Freon/R32 gas refill & leak checking', 1499.00),
(12, 5, 'Premium AC Installation', 'Uninstallation & complete re-installation with piping', 2499.00);

-- Sample reviews
INSERT IGNORE INTO reviews (reviewid, userid, providerid, rating, reviews) VALUES
(1, 1, 1, 5, 'Excellent service! The team was very professional and thorough.'),
(2, 2, 3, 4, 'Good plumbing work, arrived on time.');

