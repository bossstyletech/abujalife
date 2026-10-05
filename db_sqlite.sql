-- Abuja Life - SQLite Schema & Initial Seed Data

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    category TEXT NOT NULL,
    daily_salary REAL NOT NULL,
    required_intelligence INTEGER DEFAULT 10,
    required_education TEXT DEFAULT 'SSCE',
    energy_cost INTEGER DEFAULT 20,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS education_courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    institution TEXT NOT NULL,
    cost REAL NOT NULL,
    intelligence_gain INTEGER NOT NULL,
    energy_cost INTEGER DEFAULT 15,
    qualification TEXT NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS properties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    district TEXT NOT NULL,
    type TEXT NOT NULL,
    price REAL NOT NULL,
    daily_rent_yield REAL NOT NULL,
    happiness_bonus INTEGER DEFAULT 10,
    prestige_points INTEGER DEFAULT 5,
    description TEXT
);

CREATE TABLE IF NOT EXISTS vehicles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    brand TEXT NOT NULL,
    price REAL NOT NULL,
    daily_upkeep REAL DEFAULT 500.0,
    happiness_bonus INTEGER DEFAULT 5,
    cred_bonus INTEGER DEFAULT 5,
    description TEXT
);

CREATE TABLE IF NOT EXISTS characters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    full_name TEXT NOT NULL,
    gender TEXT DEFAULT 'Male',
    avatar TEXT DEFAULT 'default_avatar.png',
    age INTEGER DEFAULT 18,
    days_lived INTEGER DEFAULT 1,
    health INTEGER DEFAULT 100,
    happiness INTEGER DEFAULT 100,
    energy INTEGER DEFAULT 100,
    max_energy INTEGER DEFAULT 100,
    intelligence INTEGER DEFAULT 20,
    karma INTEGER DEFAULT 50,
    street_cred INTEGER DEFAULT 10,
    cash REAL DEFAULT 35000.0,
    bank REAL DEFAULT 10000.0,
    loan_balance REAL DEFAULT 0.0,
    district TEXT DEFAULT 'Kubwa',
    current_job_id INTEGER NULL,
    education_level TEXT DEFAULT 'SSCE',
    primary_vehicle_id INTEGER NULL,
    primary_property_id INTEGER NULL,
    jail_days INTEGER DEFAULT 0,
    is_alive INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (current_job_id) REFERENCES jobs (id) ON DELETE SET NULL,
    FOREIGN KEY (primary_vehicle_id) REFERENCES vehicles (id) ON DELETE SET NULL,
    FOREIGN KEY (primary_property_id) REFERENCES properties (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS character_properties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    character_id INTEGER NOT NULL,
    property_id INTEGER NOT NULL,
    is_rented_out INTEGER DEFAULT 0,
    purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters (id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS character_vehicles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    character_id INTEGER NOT NULL,
    vehicle_id INTEGER NOT NULL,
    purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters (id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    character_id INTEGER NOT NULL,
    action_type TEXT NOT NULL,
    message TEXT NOT NULL,
    cash_change REAL DEFAULT 0.0,
    energy_change INTEGER DEFAULT 0,
    happiness_change INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS random_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    option_a_label TEXT NOT NULL,
    option_a_cash REAL DEFAULT 0.0,
    option_a_health INTEGER DEFAULT 0,
    option_a_happiness INTEGER DEFAULT 0,
    option_a_cred INTEGER DEFAULT 0,
    option_a_msg TEXT NOT NULL,
    option_b_label TEXT NOT NULL,
    option_b_cash REAL DEFAULT 0.0,
    option_b_health INTEGER DEFAULT 0,
    option_b_happiness INTEGER DEFAULT 0,
    option_b_cred INTEGER DEFAULT 0,
    option_b_msg TEXT NOT NULL
);

-- SEED DATA
INSERT OR IGNORE INTO jobs (id, title, category, daily_salary, required_intelligence, required_education, energy_cost, description) VALUES
(1, 'Keke Napep Rider', 'Informal', 4500.00, 5, 'SSCE', 25, 'Hustle daily commuting passengers around Kubwa and Dutse junctions.'),
(2, 'Banex Plaza Phone Technician', 'Trade', 9500.00, 15, 'SSCE', 20, 'Screen repairs, unlocking iPhones, and flashing Androids in Wuse 2.'),
(3, 'Uber/Bolt Driver', 'Transportation', 14000.00, 20, 'SSCE', 25, 'Drive riders between Airport Road, Maitama, and Central Business District.'),
(4, 'Federal Ministry Clerical Officer', 'Civil Service', 18000.00, 35, 'OND', 15, 'Steady civil service job at the Federal Secretariat, Shehu Shagari Way.'),
(5, 'Junior Software Developer', 'Technology', 35000.00, 50, 'BSc', 25, 'Build web APIs and mobile apps for tech startups in Jabi and Wuse 2.'),
(6, 'Commercial Bank Relationship Manager', 'Corporate', 55000.00, 60, 'BSc', 25, 'Manage VIP HNIs and deposit mobilisation in Central Business District.'),
(7, 'Senior Tech Lead / Remote Consultant', 'Technology', 110000.00, 80, 'BSc', 30, 'Earn foreign tech contracts working from your laptop at Wuse 2 cafés.'),
(8, 'Special Assistant to Minister', 'Politics', 220000.00, 70, 'MSc', 20, 'Powerful political appointment in Aso Rock corridor with juicy allowances.'),
(9, 'Managing Director (Oil & Gas Infrastructure)', 'Executive', 500000.00, 90, 'MSc', 25, 'Supervise multi-billion Naira government and private energy contracts in Maitama.');

INSERT OR IGNORE INTO education_courses (id, name, institution, cost, intelligence_gain, energy_cost, qualification, description) VALUES
(1, 'Ordinary National Diploma (OND)', 'Dorben Polytechnic, Bwari', 65000.00, 15, 20, 'OND', 'Foundation tertiary qualification in Business Administration or Computer Science.'),
(2, 'Bachelor of Science (BSc Degree)', 'University of Abuja (UniAbuja)', 180000.00, 25, 25, 'BSc', 'Full undergraduate university degree from Gwagwalada main campus.'),
(3, 'Fullstack Web & AI Engineering Bootcamp', 'Abuja Tech Hub, Jabi', 120000.00, 30, 20, 'TechCert', 'Learn modern programming, databases, cloud architecture, and AI tooling.'),
(4, 'Private University Executive BSc', 'Baze University, Jabi', 850000.00, 35, 20, 'BSc', 'Top-tier private university education with modern facilities and elite networking.'),
(5, 'Master of Science / MBA', 'Nile University of Nigeria', 1200000.00, 25, 25, 'MSc', 'Postgraduate degree to open boardroom doors and high federal appointments.');

INSERT OR IGNORE INTO properties (id, name, district, type, price, daily_rent_yield, happiness_bonus, prestige_points, description) VALUES
(1, 'Self-Contain Room', 'Lugbe', 'Apartment', 450000.00, 1500.00, 10, 5, 'Cozy single room along Airport Road with prepaid meter and water tank.'),
(2, '2-Bedroom Flat', 'Kubwa', 'Flat', 1200000.00, 4200.00, 18, 12, 'Spacious flat near Kubwa train station with dedicated parking.'),
(3, '3-Bedroom Apartment', 'Gwarinpa Estate', 'Apartment', 3500000.00, 11000.00, 28, 25, 'Modern flat in West Africa’s largest planned housing estate, close to 3rd Avenue.'),
(4, '4-Bedroom Semi-Detached Duplex', 'Wuse 2', 'Duplex', 15000000.00, 45000.00, 40, 55, 'Executive residence in the vibrant commercial and culinary heart of Abuja.'),
(5, 'Luxury 6-Bedroom Smart Mansion', 'Maitama', 'Mansion', 65000000.00, 180000.00, 60, 95, 'High-security diplomatic haven with swimming pool, bulletproof glass, and golf view.'),
(6, 'Ultra-Luxury Presidential Villa Estate', 'Asokoro', 'Villa', 180000000.00, 500000.00, 80, 150, 'Hilltop palatial compound overlooking Aso Rock with private helipad and CCTV security.');

INSERT OR IGNORE INTO vehicles (id, name, brand, price, daily_upkeep, happiness_bonus, cred_bonus, description) VALUES
(1, 'Bajaj Boxer Motorcycle', 'Bajaj', 250000.00, 800.00, 8, 5, 'Quick navigation through Dutse Alhaji and Mararaba traffic gridlock.'),
(2, 'Toyota Corolla ("Big Daddy")', 'Toyota', 2800000.00, 2500.00, 20, 18, 'The undisputed king of Abuja reliability. Cheap spare parts, high fuel economy.'),
(3, 'Honda Accord ("Evil Spirit")', 'Honda', 3500000.00, 3200.00, 22, 22, 'Sleek Nigerian executive saloon with smooth AC for cruising through CBD.'),
(4, 'Lexus RX 350', 'Lexus', 12500000.00, 7500.00, 38, 45, 'The unofficial Abuja youth elite and tech superstar SUV. High road respect.'),
(5, 'Mercedes-Benz C300 4MATIC', 'Mercedes-Benz', 22000000.00, 12000.00, 50, 60, 'Burble exhaust, ambient interior lighting, and instant valet parking in Wuse 2 clubs.'),
(6, 'Range Rover Autobiography V8', 'Land Rover', 55000000.00, 28000.00, 70, 90, 'Imposing British luxury SUV that commands military salutes at security barriers.'),
(7, 'Convoy with Toyota Land Cruiser & Escort Siren', 'Armored Fleet', 130000000.00, 65000.00, 90, 140, 'Twin V8 Land Cruisers with flashing strobe lights and siren escorts clearing Airport Road.');

INSERT OR IGNORE INTO random_events (id, title, description, option_a_label, option_a_cash, option_a_health, option_a_happiness, option_a_cred, option_a_msg, option_b_label, option_b_cash, option_b_health, option_b_happiness, option_b_cred, option_b_msg) VALUES
(1, 'Police Checkpoint at Airport Road', 'You are stopped by VIO and Police officers waving flashlights: "Oga park well! Anything for the boys?"', 'Give them ₦3,000 for "pure water"', -3000.00, 0, 5, 2, 'The officers hailed you: "Chairman! Safe trip o!" and cleared your passage immediately.', 'Argue and demand to see your offence', 0.00, -10, -15, 5, 'They delayed you for 2 hours checking car fire extinguisher and triangle. Your blood pressure spiked!'),
(2, 'Wuse 2 Crypto Arbitrage Tip', 'Your tech bro friend at an Aminu Kano Crescent lounge claims he found an insider triangular arbitrage on USDT.', 'Risk ₦50,000 to trade the dip', 85000.00, 0, 20, 10, 'Green candles! The trade executed smoothly and you bagged ₦85,000 clean profit.', 'Play it safe and ignore the tip', 0.00, 0, 0, 0, 'You kept your funds safe in your account. The coin later experienced volatility anyway.'),
(3, 'Urgent 2k Request from Extended Family', 'Your favorite cousin from the village sends a WhatsApp voice note: "Egbon, billing don hold me for school, abeg send urgent 10k."', 'Send ₦10,000 with love', -10000.00, 0, 15, 12, 'Your cousin called your mother and gave you glowing blessings. Good karma unlocked!', 'Tell them "Account dry right now"', 0.00, 0, -5, -5, 'You preserved your cash, but your cousin left you on read for 2 weeks.'),
(4, 'Abuja High Society Owambe Invitation', 'A prominent Senator is hosting a daughter wedding reception at the International Conference Centre (ICC).', 'Spray ₦40,000 crisp new notes on the dance floor', -40000.00, 0, 30, 35, 'The praise singers hyped your name on microphone: "Olowo Abuja!" Your street cred exploded!', 'Sit quietly and just enjoy small chops & jollof', 0.00, 0, 10, 5, 'You enjoyed delicious party jollof rice, assorted meat, and chilled chapman without spending a dime.'),
(5, 'NEPA / DisCo Power Surge', 'Sudden power grid restoration with extreme voltage surge while you were away from home.', 'Install heavy duty surge protector and inverter (₦25,000)', -25000.00, 0, 10, 5, 'Your electronics stayed intact and you now have uninterrupted power supply.', 'Manage it and hope for the best', -15000.00, 0, -20, 0, 'A small spark burnt your television adaptor! You had to spend ₦15,000 on repairs.');
