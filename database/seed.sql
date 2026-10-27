-- Demo data. Every demo account uses the password: password
-- Change or delete these accounts before using GovWeb for anything real.
USE `government_registration`;

INSERT INTO civilians (id, person_code, first_name, last_name, gender, dob, email, phone, address, occupation) VALUES
(1, '800101-48213', 'John',   'Doe',      'male',   '1980-01-01', 'john.doe@example.com',     '+371 2000 1001', '123 Main St',      'Engineer'),
(2, '900202-11764', 'Jane',   'Smith',    'female', '1990-02-02', 'jane.smith@example.com',   '+371 2000 1002', '456 Elm St',       'Doctor'),
(3, '850715-90321', 'Marta',  'Ozola',    'female', '1985-07-15', 'marta.ozola@example.com',  '+371 2000 1003', '12 Brivibas iela', 'Teacher'),
(4, '920430-55018', 'Andris', 'Kalnins',  'male',   '1992-04-30', 'andris.k@example.com',     '+371 2000 1004', '8 Elizabetes iela','Electrician'),
(5, '011120-30487', 'Alex',   'Rivera',   'other',  '2001-11-20', 'alex.rivera@example.com',  '+371 2000 1005', '77 Harbor Rd',     'Student');

INSERT INTO users (username, password_hash, full_name, role, civilian_id) VALUES
('admin',     '$2y$10$tJuHx0z2z9vxRGdiI4xqVOqZNmh.iXDRfkwYFk8.L1Txz6.1/diTe', 'Administrator',        'admin',     NULL),
('registrar', '$2y$10$tJuHx0z2z9vxRGdiI4xqVOqZNmh.iXDRfkwYFk8.L1Txz6.1/diTe', 'Ilze Berzina',         'registrar', NULL),
('officer',   '$2y$10$tJuHx0z2z9vxRGdiI4xqVOqZNmh.iXDRfkwYFk8.L1Txz6.1/diTe', 'Officer Peteris Liepa','officer',   NULL),
('judge',     '$2y$10$tJuHx0z2z9vxRGdiI4xqVOqZNmh.iXDRfkwYFk8.L1Txz6.1/diTe', 'Judge Anna Vilka',     'judge',     NULL),
('john',      '$2y$10$tJuHx0z2z9vxRGdiI4xqVOqZNmh.iXDRfkwYFk8.L1Txz6.1/diTe', 'John Doe',             'civilian',  1);

INSERT INTO licenses (civilian_id, license_type, license_number, issued_on, expires_on, status) VALUES
(1, 'car',        'CAR-482913', '2022-01-01', '2032-01-01', 'active'),
(2, 'motorcycle', 'MOT-118204', '2015-06-01', '2025-06-01', 'active'),
(3, 'car',        'CAR-730155', '2019-03-12', '2029-03-12', 'suspended'),
(4, 'truck',      'TRK-290844', '2020-09-01', '2030-09-01', 'active');

INSERT INTO businesses (owner_id, name, business_type, address, registered_on) VALUES
(1, 'Tech Innovations', 'Technology', '789 Tech Blvd',   '2019-07-01'),
(2, 'Health Solutions', 'Healthcare', '123 Health St',   '2020-09-01'),
(4, 'Kalnins Electric', 'Trades',     '8 Elizabetes iela','2021-02-14');

INSERT INTO criminal_records (civilian_id, offense, description, offense_date, sentence, officer_id) VALUES
(1, 'Theft',           'Shoplifting at a downtown store.', '2022-01-05', 'Fine, 300 EUR', 3),
(4, 'Speeding',        'Recorded at 92 km/h in a 50 zone.', '2024-05-18', 'Fine, 120 EUR', 3);

INSERT INTO warrants (code, civilian_id, reason, description, status, requested_by, decided_by) VALUES
('W-26-7Q4K9P', 1, 'Failure to appear in court', 'Did not attend hearing for case CF-2022-0001.', 'issued', 3, 4),
('W-26-M2X8RT', 5, 'Suspected burglary', 'Witness statements place the person at 77 Harbor Rd on the night of the burglary.', 'pending', 3, NULL);

INSERT INTO dispatches (message, location, priority, status, created_by) VALUES
('Robbery in progress, all nearby units respond.', '123 Main St', 'urgent', 'open', 3),
('Noise complaint from neighbours.', '12 Brivibas iela', 'low', 'closed', 3);

INSERT INTO operations (agency, name, operation_type, status, start_date, end_date, description) VALUES
('government', 'Vaccination drive',        'Public health',  'completed', '2021-01-15', '2021-06-30', 'Citywide vaccination drive.'),
('government', 'Harbor Road resurfacing',  'Infrastructure', 'active',    '2026-09-01', NULL,         'Road construction on Harbor Rd.'),
('police',     'Night patrol: Old Town',   'Patrol',         'active',    '2026-10-01', NULL,         'Extra patrols on weekend nights.'),
('police',     'Operation Clear Harbor',   'Investigation',  'planned',   '2026-11-10', NULL,         'Follow-up on harbor burglaries.');

INSERT INTO court_files (id, file_number, case_title, description, filed_on, status) VALUES
(1, 'CF-2022-0001', 'State vs John Doe', 'Theft case against John Doe.', '2022-01-10', 'open');

INSERT INTO court_documents (id, document_code, court_file_id, civilian_id, court_name, judge_name, case_number, court_date, description, issued_by) VALUES
(1, 'D-22-3HT8WQ', 1, 1, 'Riga City Court', 'Judge Anna Vilka', 'CF-2022-0001', '2022-02-01', 'Summons to appear for hearing.', 4);

INSERT INTO court_charges (document_id, charge) VALUES
(1, 'Theft under 500 EUR');

INSERT INTO vehicles (plate, vin, owner_id, make, model, year, color, status, registered_on, inspection_until) VALUES
('JD-1980', 'WVWZZZ1JZXW000001', 1, 'Volkswagen', 'Golf',    2018, 'Grey',  'registered', '2018-05-02', '2027-05-01'),
('HS-2020', 'YV1ZZZ8BZ00000002', 2, 'Volvo',      'XC60',    2020, 'White', 'registered', '2020-09-15', '2026-09-14'),
('MO-1985', 'VF1ZZZ5RZ00000003', 3, 'Renault',    'Clio',    2016, 'Red',   'stolen',     '2016-03-20', '2027-03-19'),
('AK-0430', 'WDBZZZ90600000004', 4, 'Mercedes-Benz', 'Sprinter', 2021, 'White', 'registered', '2021-02-14', '2027-02-13');

INSERT INTO fines (code, civilian_id, vehicle_id, amount_cents, reason, issued_by, issued_on, due_on, status, paid_on) VALUES
('F-26-K7M2QD', 4, 4, 12000, 'Speeding, 92 km/h in a 50 zone', 3, '2026-09-20', '2026-10-20', 'unpaid', NULL),
('F-26-P3W8NX', 1, 1, 4000,  'Parking in a no-parking zone',   3, '2026-10-10', '2026-11-09', 'unpaid', NULL),
('F-22-B9T4LC', 1, NULL, 30000, 'Theft under 500 EUR (court fine)', 4, '2022-02-01', '2022-03-01', 'paid', '2022-02-20');

INSERT INTO applications (code, civilian_id, type, details, status) VALUES
('A-26-R5H2JV', 1, 'license', '{"license_type":"motorcycle","note":"Passed the motorcycle exam on 12 October."}', 'submitted');

-- A year of history with dates relative to today, so the statistics charts have data.
INSERT INTO civilians (id, person_code, first_name, last_name, gender, dob, email, phone, address, occupation, created_at) VALUES
(6, '801102-80239', 'Madara', 'Liepina', 'female', '1980-11-02', NULL, '+371 22579240', '47 Brivibas iela', 'Retired', NOW() - INTERVAL 37 DAY),
(7, '800108-82963', 'Niklavs', 'Lacis', 'male', '1980-01-08', NULL, '+371 23234302', '38 Daugavgrivas iela', 'Driver', NOW() - INTERVAL 23 DAY),
(8, '901203-17812', 'Madara', 'Kaleja', 'female', '1990-12-03', NULL, '+371 24455413', '64 Daugavgrivas iela', 'Electrician', NOW() - INTERVAL 288 DAY),
(9, '870706-29920', 'Peteris', 'Kalejs', 'male', '1987-07-06', NULL, '+371 29203439', '54 Brivibas iela', 'Designer', NOW() - INTERVAL 175 DAY),
(10, '600516-97051', 'Zane', 'Kaleja', 'female', '1960-05-16', NULL, '+371 22090518', '8 Maskavas iela', 'Designer', NOW() - INTERVAL 356 DAY),
(11, '800728-20561', 'Uldis', 'Krumins', 'male', '1980-07-28', NULL, '+371 23791163', '58 Daugavgrivas iela', 'Retired', NOW() - INTERVAL 254 DAY),
(12, '710501-64912', 'Dace', 'Liepina', 'female', '1971-05-01', NULL, '+371 29968948', '48 Kr. Barona iela', 'Driver', NOW() - INTERVAL 74 DAY),
(13, '761002-10030', 'Martins', 'Kalejs', 'male', '1976-10-02', NULL, '+371 23537804', '69 Valdemara iela', 'Electrician', NOW() - INTERVAL 52 DAY),
(14, '780323-13544', 'Kristine', 'Pukitia', 'female', '1978-03-23', NULL, '+371 29860206', '39 Valdemara iela', 'Farmer', NOW() - INTERVAL 278 DAY),
(15, '670916-13798', 'Uldis', 'Krumins', 'male', '1967-09-16', NULL, '+371 21468706', '102 Maskavas iela', 'Student', NOW() - INTERVAL 182 DAY),
(16, '851112-21112', 'Dace', 'Berzina', 'female', '1985-11-12', NULL, '+371 23011649', '117 Daugavgrivas iela', 'Farmer', NOW() - INTERVAL 329 DAY),
(17, '650305-29811', 'Peteris', 'Rudzitis', 'male', '1965-03-05', NULL, '+371 28807342', '104 Terbatas iela', 'Mechanic', NOW() - INTERVAL 14 DAY),
(18, '670401-37889', 'Ilze', 'Eglitia', 'female', '1967-04-01', NULL, '+371 25915164', '65 Lacplesa iela', 'Mechanic', NOW() - INTERVAL 128 DAY),
(19, '751117-82802', 'Roberts', 'Berzins', 'male', '1975-11-17', NULL, '+371 29094788', '101 Valdemara iela', 'Retired', NOW() - INTERVAL 271 DAY),
(20, '751017-77130', 'Elina', 'Zarina', 'female', '1975-10-17', NULL, '+371 24345430', '89 Maskavas iela', 'Student', NOW() - INTERVAL 310 DAY),
(21, '670805-25941', 'Raivis', 'Pukitis', 'male', '1967-08-05', NULL, '+371 27582781', '57 Kr. Barona iela', 'Nurse', NOW() - INTERVAL 213 DAY),
(22, '961112-43175', 'Ilze', 'Rudzitia', 'female', '1996-11-12', NULL, '+371 23302750', '60 Lacplesa iela', 'Farmer', NOW() - INTERVAL 73 DAY),
(23, '730903-39957', 'Roberts', 'Lacis', 'male', '1973-09-03', NULL, '+371 22757909', '11 Maskavas iela', 'Accountant', NOW() - INTERVAL 57 DAY),
(24, '690209-69477', 'Elina', 'Lacia', 'female', '1969-02-09', NULL, '+371 21193715', '44 Daugavgrivas iela', 'Accountant', NOW() - INTERVAL 62 DAY),
(25, '720626-42826', 'Gatis', 'Liepins', 'male', '1972-06-26', NULL, '+371 21619907', '2 Brivibas iela', 'Farmer', NOW() - INTERVAL 9 DAY),
(26, '820302-97192', 'Agnese', 'Vitola', 'female', '1982-03-02', NULL, '+371 27390135', '112 Maskavas iela', 'Mechanic', NOW() - INTERVAL 43 DAY),
(27, '710205-86913', 'Janis', 'Kalejs', 'male', '1971-02-05', NULL, '+371 21699055', '51 Brivibas iela', 'Accountant', NOW() - INTERVAL 204 DAY),
(28, '571217-66261', 'Evija', 'Liepina', 'female', '1957-12-17', NULL, '+371 29481571', '18 Brivibas iela', 'Designer', NOW() - INTERVAL 321 DAY),
(29, '830902-12469', 'Peteris', 'Eglitis', 'male', '1983-09-02', NULL, '+371 29916148', '88 Lacplesa iela', 'Student', NOW() - INTERVAL 321 DAY),
(30, '010408-70337', 'Laura', 'Krumina', 'female', '2001-04-08', NULL, '+371 29287085', '109 Daugavgrivas iela', 'Nurse', NOW() - INTERVAL 332 DAY),
(31, '990422-48123', 'Gatis', 'Kalejs', 'male', '1999-04-22', NULL, '+371 29666030', '37 Gertrudes iela', 'Student', NOW() - INTERVAL 250 DAY),
(32, '931117-24768', 'Madara', 'Liepina', 'female', '1993-11-17', NULL, '+371 27126846', '30 Gertrudes iela', 'Student', NOW() - INTERVAL 143 DAY),
(33, '790604-10228', 'Karlis', 'Balodis', 'male', '1979-06-04', NULL, '+371 26445004', '97 Kr. Barona iela', 'Chef', NOW() - INTERVAL 169 DAY),
(34, '670626-13802', 'Inese', 'Balodia', 'female', '1967-06-26', NULL, '+371 27711585', '117 Lacplesa iela', 'Farmer', NOW() - INTERVAL 219 DAY),
(35, '801108-73331', 'Gatis', 'Vitols', 'male', '1980-11-08', NULL, '+371 27616393', '16 Terbatas iela', 'Designer', NOW() - INTERVAL 154 DAY);

INSERT INTO licenses (civilian_id, license_type, license_number, issued_on, expires_on, status) VALUES
(6, 'car', 'CAR-538485', CURDATE() - INTERVAL 222 DAY, CURDATE() - INTERVAL 222 DAY + INTERVAL 10 YEAR, 'active'),
(7, 'truck', 'TRK-687472', CURDATE() - INTERVAL 157 DAY, CURDATE() - INTERVAL 157 DAY + INTERVAL 10 YEAR, 'active'),
(8, 'motorcycle', 'MOT-414328', CURDATE() - INTERVAL 185 DAY, CURDATE() - INTERVAL 185 DAY + INTERVAL 10 YEAR, 'active'),
(9, 'truck', 'TRK-927425', CURDATE() - INTERVAL 293 DAY, CURDATE() - INTERVAL 293 DAY + INTERVAL 10 YEAR, 'active'),
(10, 'scooter', 'SCO-398420', CURDATE() - INTERVAL 228 DAY, CURDATE() - INTERVAL 228 DAY + INTERVAL 10 YEAR, 'active'),
(11, 'car', 'CAR-676947', CURDATE() - INTERVAL 220 DAY, CURDATE() - INTERVAL 220 DAY + INTERVAL 10 YEAR, 'active'),
(12, 'truck', 'TRK-786782', CURDATE() - INTERVAL 316 DAY, CURDATE() - INTERVAL 316 DAY + INTERVAL 10 YEAR, 'active'),
(13, 'car', 'CAR-743898', CURDATE() - INTERVAL 106 DAY, CURDATE() - INTERVAL 106 DAY + INTERVAL 10 YEAR, 'active'),
(15, 'scooter', 'SCO-461004', CURDATE() - INTERVAL 309 DAY, CURDATE() - INTERVAL 309 DAY + INTERVAL 10 YEAR, 'active'),
(16, 'motorcycle', 'MOT-555003', CURDATE() - INTERVAL 91 DAY, CURDATE() - INTERVAL 91 DAY + INTERVAL 10 YEAR, 'active'),
(18, 'truck', 'TRK-974716', CURDATE() - INTERVAL 214 DAY, CURDATE() - INTERVAL 214 DAY + INTERVAL 10 YEAR, 'active'),
(19, 'car', 'CAR-144248', CURDATE() - INTERVAL 141 DAY, CURDATE() - INTERVAL 141 DAY + INTERVAL 10 YEAR, 'active'),
(20, 'motorcycle', 'MOT-359685', CURDATE() - INTERVAL 259 DAY, CURDATE() - INTERVAL 259 DAY + INTERVAL 10 YEAR, 'active'),
(21, 'motorcycle', 'MOT-323021', CURDATE() - INTERVAL 37 DAY, CURDATE() - INTERVAL 37 DAY + INTERVAL 10 YEAR, 'active'),
(23, 'car', 'CAR-892489', CURDATE() - INTERVAL 138 DAY, CURDATE() - INTERVAL 138 DAY + INTERVAL 10 YEAR, 'active'),
(24, 'car', 'CAR-844003', CURDATE() - INTERVAL 269 DAY, CURDATE() - INTERVAL 269 DAY + INTERVAL 10 YEAR, 'active'),
(25, 'car', 'CAR-597822', CURDATE() - INTERVAL 263 DAY, CURDATE() - INTERVAL 263 DAY + INTERVAL 10 YEAR, 'active'),
(26, 'car', 'CAR-581771', CURDATE() - INTERVAL 23 DAY, CURDATE() - INTERVAL 23 DAY + INTERVAL 10 YEAR, 'active'),
(27, 'car', 'CAR-714028', CURDATE() - INTERVAL 43 DAY, CURDATE() - INTERVAL 43 DAY + INTERVAL 10 YEAR, 'active'),
(28, 'scooter', 'SCO-827005', CURDATE() - INTERVAL 349 DAY, CURDATE() - INTERVAL 349 DAY + INTERVAL 10 YEAR, 'active'),
(29, 'motorcycle', 'MOT-884613', CURDATE() - INTERVAL 35 DAY, CURDATE() - INTERVAL 35 DAY + INTERVAL 10 YEAR, 'active'),
(30, 'scooter', 'SCO-904226', CURDATE() - INTERVAL 147 DAY, CURDATE() - INTERVAL 147 DAY + INTERVAL 10 YEAR, 'active'),
(31, 'car', 'CAR-308928', CURDATE() - INTERVAL 281 DAY, CURDATE() - INTERVAL 281 DAY + INTERVAL 10 YEAR, 'active'),
(32, 'car', 'CAR-615580', CURDATE() - INTERVAL 1 DAY, CURDATE() - INTERVAL 1 DAY + INTERVAL 10 YEAR, 'active'),
(33, 'car', 'CAR-875849', CURDATE() - INTERVAL 6 DAY, CURDATE() - INTERVAL 6 DAY + INTERVAL 10 YEAR, 'active'),
(34, 'scooter', 'SCO-572761', CURDATE() - INTERVAL 210 DAY, CURDATE() - INTERVAL 210 DAY + INTERVAL 10 YEAR, 'active'),
(35, 'car', 'CAR-951261', CURDATE() - INTERVAL 256 DAY, CURDATE() - INTERVAL 256 DAY + INTERVAL 10 YEAR, 'active');

INSERT INTO vehicles (plate, vin, owner_id, make, model, year, color, status, registered_on, inspection_until) VALUES
('CU-7955', NULL, 6, 'Toyota', 'Corolla', 2008, 'Silver', 'registered', CURDATE() - INTERVAL 1321 DAY, CURDATE() + INTERVAL 519 DAY),
('FH-2341', NULL, 8, 'BMW', '320d', 2021, 'White', 'registered', CURDATE() - INTERVAL 1822 DAY, CURDATE() + INTERVAL 247 DAY),
('PM-7233', NULL, 11, 'Skoda', 'Octavia', 2007, 'Silver', 'registered', CURDATE() - INTERVAL 339 DAY, CURDATE() + INTERVAL 94 DAY),
('JM-6966', NULL, 13, 'Audi', 'A4', 2008, 'White', 'registered', CURDATE() - INTERVAL 984 DAY, CURDATE() + INTERVAL 65 DAY),
('FM-4650', NULL, 14, 'BMW', '320d', 2021, 'Blue', 'registered', CURDATE() - INTERVAL 1333 DAY, CURDATE() + INTERVAL 494 DAY),
('MM-2319', NULL, 15, 'Skoda', 'Octavia', 2012, 'White', 'registered', CURDATE() - INTERVAL 432 DAY, CURDATE() + INTERVAL 44 DAY),
('MR-9466', NULL, 18, 'Audi', 'A4', 2009, 'Grey', 'registered', CURDATE() - INTERVAL 340 DAY, CURDATE() + INTERVAL 453 DAY),
('SF-4665', NULL, 22, 'Skoda', 'Octavia', 2021, 'White', 'registered', CURDATE() - INTERVAL 724 DAY, CURDATE() + INTERVAL 381 DAY),
('PJ-7651', NULL, 23, 'Skoda', 'Octavia', 2021, 'Grey', 'registered', CURDATE() - INTERVAL 1042 DAY, CURDATE() + INTERVAL 489 DAY),
('DF-5290', NULL, 24, 'Toyota', 'Corolla', 2011, 'Blue', 'registered', CURDATE() - INTERVAL 1317 DAY, CURDATE() + INTERVAL 125 DAY),
('RD-8080', NULL, 25, 'Volvo', 'V70', 2022, 'White', 'registered', CURDATE() - INTERVAL 1067 DAY, CURDATE() + INTERVAL 446 DAY),
('JR-1059', NULL, 26, 'Volkswagen', 'Passat', 2015, 'Grey', 'registered', CURDATE() - INTERVAL 692 DAY, CURDATE() + INTERVAL 312 DAY),
('GC-3415', NULL, 30, 'Volkswagen', 'Passat', 2014, 'Grey', 'registered', CURDATE() - INTERVAL 1192 DAY, CURDATE() + INTERVAL 200 DAY),
('CS-1286', NULL, 31, 'Volkswagen', 'Passat', 2007, 'Grey', 'registered', CURDATE() - INTERVAL 950 DAY, CURDATE() + INTERVAL 409 DAY),
('MC-7437', NULL, 33, 'Audi', 'A4', 2007, 'Blue', 'registered', CURDATE() - INTERVAL 1925 DAY, CURDATE() + INTERVAL 543 DAY),
('HR-6453', NULL, 35, 'Ford', 'Focus', 2018, 'Silver', 'registered', CURDATE() - INTERVAL 1151 DAY, CURDATE() + INTERVAL 400 DAY);

INSERT INTO criminal_records (civilian_id, offense, description, offense_date, sentence, officer_id) VALUES
(7, 'Speeding', NULL, CURDATE() - INTERVAL 292 DAY, NULL, 3),
(8, 'Fraud', NULL, CURDATE() - INTERVAL 147 DAY, NULL, 3),
(9, 'Vandalism', NULL, CURDATE() - INTERVAL 304 DAY, NULL, 3),
(11, 'Theft', NULL, CURDATE() - INTERVAL 6 DAY, NULL, 3),
(12, 'Theft', NULL, CURDATE() - INTERVAL 286 DAY, NULL, 3),
(14, 'Theft', NULL, CURDATE() - INTERVAL 122 DAY, NULL, 3),
(15, 'Fraud', NULL, CURDATE() - INTERVAL 319 DAY, NULL, 3),
(16, 'Assault', NULL, CURDATE() - INTERVAL 237 DAY, NULL, 3),
(24, 'Theft', NULL, CURDATE() - INTERVAL 148 DAY, NULL, 3),
(25, 'Theft', NULL, CURDATE() - INTERVAL 117 DAY, NULL, 3),
(26, 'Drunk driving', NULL, CURDATE() - INTERVAL 111 DAY, NULL, 3),
(28, 'Speeding', NULL, CURDATE() - INTERVAL 68 DAY, NULL, 3),
(30, 'Fraud', NULL, CURDATE() - INTERVAL 31 DAY, NULL, 3),
(34, 'Drunk driving', NULL, CURDATE() - INTERVAL 248 DAY, NULL, 3),
(35, 'Speeding', NULL, CURDATE() - INTERVAL 89 DAY, NULL, 3);

INSERT INTO fines (code, civilian_id, vehicle_id, amount_cents, reason, issued_by, issued_on, due_on, status, paid_on) VALUES
('F-26-SBG6KE', 10, NULL, 6000, 'No technical inspection', 3, CURDATE() - INTERVAL 181 DAY, CURDATE() - INTERVAL 181 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 175 DAY),
('F-26-NBGCGR', 12, NULL, 6000, 'No technical inspection', 3, CURDATE() - INTERVAL 201 DAY, CURDATE() - INTERVAL 201 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 178 DAY),
('F-26-S84FTA', 13, NULL, 12000, 'Speeding', 3, CURDATE() - INTERVAL 52 DAY, CURDATE() - INTERVAL 52 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 41 DAY),
('F-26-752DT5', 17, NULL, 10000, 'Running a red light', 3, CURDATE() - INTERVAL 280 DAY, CURDATE() - INTERVAL 280 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 277 DAY),
('F-26-FESZ5D', 18, NULL, 6000, 'No technical inspection', 3, CURDATE() - INTERVAL 93 DAY, CURDATE() - INTERVAL 93 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 86 DAY),
('F-26-RR4ANL', 22, NULL, 8000, 'Speeding', 3, CURDATE() - INTERVAL 187 DAY, CURDATE() - INTERVAL 187 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 167 DAY),
('F-26-JA2C7J', 23, NULL, 8000, 'Speeding', 3, CURDATE() - INTERVAL 352 DAY, CURDATE() - INTERVAL 352 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 347 DAY),
('F-26-B8EAC2', 25, NULL, 30000, 'Driving without a license', 3, CURDATE() - INTERVAL 325 DAY, CURDATE() - INTERVAL 325 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 311 DAY),
('F-26-JT2GHT', 26, NULL, 8000, 'Speeding', 3, CURDATE() - INTERVAL 171 DAY, CURDATE() - INTERVAL 171 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 153 DAY),
('F-26-5SEK5Z', 27, NULL, 30000, 'Driving without a license', 3, CURDATE() - INTERVAL 305 DAY, CURDATE() - INTERVAL 305 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 292 DAY),
('F-26-55SJ7C', 29, NULL, 10000, 'Running a red light', 3, CURDATE() - INTERVAL 33 DAY, CURDATE() - INTERVAL 33 DAY + INTERVAL 30 DAY, 'unpaid', NULL),
('F-26-VCE5TJ', 31, NULL, 12000, 'Speeding', 3, CURDATE() - INTERVAL 38 DAY, CURDATE() - INTERVAL 38 DAY + INTERVAL 30 DAY, 'unpaid', NULL),
('F-26-K2EHJP', 33, NULL, 8000, 'Speeding', 3, CURDATE() - INTERVAL 143 DAY, CURDATE() - INTERVAL 143 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 119 DAY),
('F-26-LKKJ55', 34, NULL, 10000, 'Running a red light', 3, CURDATE() - INTERVAL 65 DAY, CURDATE() - INTERVAL 65 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 49 DAY),
('F-26-7VGA59', 35, NULL, 8000, 'Speeding', 3, CURDATE() - INTERVAL 163 DAY, CURDATE() - INTERVAL 163 DAY + INTERVAL 30 DAY, 'paid', CURDATE() - INTERVAL 152 DAY);
