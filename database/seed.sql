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
