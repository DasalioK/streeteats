-- =====================================================================
-- StreetEats - database met testdata
-- Hoort bij: TE2 (MySQL/MariaDB met primary keys en foreign keys)
--            Technisch ontwerp hoofdstuk 7 (ERD)
--
-- UITLEG:
-- Dit bestand maakt alle tabellen aan en vult ze met testdata.
-- Je kunt het importeren in phpMyAdmin (tabblad "Importeren").
-- Let op: bestaande tabellen worden eerst verwijderd.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS streeteats CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE streeteats;

-- Eerst oude tabellen weg (in omgekeerde volgorde vanwege de foreign keys)
DROP TABLE IF EXISTS menu_items, stops, routes, opening_hours, locations, trucks, users;

-- ---------------------------------------------------------------------
-- users: planners en beheerders (bezoekers hebben geen account)
-- Het wachtwoord staat er NOOIT leesbaar in, alleen als hash (TE4).
-- actief = 0 betekent: account is geblokkeerd door de beheerder.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    naam          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('planner', 'beheerder') NOT NULL DEFAULT 'planner',
    actief        TINYINT(1) NOT NULL DEFAULT 1
);

-- trucks: de foodtrucks
CREATE TABLE trucks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    naam         VARCHAR(100) NOT NULL,
    kitchen_type VARCHAR(50)  NOT NULL,   -- soort eten, bijv. Pizza
    description  TEXT
);

-- locations: de plekken waar een truck kan staan, met vergunning
CREATE TABLE locations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    naam            VARCHAR(100) NOT NULL,
    address         VARCHAR(150) NOT NULL,
    city            VARCHAR(100) NOT NULL,  -- plaats, nodig om op plaats te zoeken (FE1)
    permit_number   VARCHAR(50)  NOT NULL,
    permit_end_date DATE         NOT NULL,
    INDEX (city)   -- ontwerp H11: index op plaats, zodat zoeken snel blijft
);

-- opening_hours: openingstijden van een plek per dag (1 = maandag ... 7 = zondag)
-- Een plek heeft openingstijden per dag (locations 1 -> N opening_hours)
CREATE TABLE opening_hours (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    day         TINYINT NOT NULL,
    open_time   TIME NOT NULL,
    close_time  TIME NOT NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- routes: de planning van één dag. Nieuwe routes zijn eerst 'concept'.
-- Alleen 'goedgekeurd' is zichtbaar voor bezoekers (FE8).
-- Een planner kan meerdere routes maken (users 1 -> N routes)
CREATE TABLE routes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    route_date DATE NOT NULL,
    status     ENUM('concept', 'goedgekeurd') NOT NULL DEFAULT 'concept',
    created_by INT NOT NULL,
    INDEX (route_date),   -- ontwerp H11: index op datum, zodat zoeken snel blijft
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- stops: welke truck staat op welke plek en hoe laat, binnen een route
-- Een route heeft een of meer stops (routes 1 -> N stops)
-- Een truck en een plek kunnen in meerdere stops staan
CREATE TABLE stops (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    route_id    INT  NOT NULL,
    truck_id    INT  NOT NULL,
    location_id INT  NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    stop_order  INT  NOT NULL,   -- volgorde van de stops: 1, 2, 3 ...
    FOREIGN KEY (route_id)    REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (truck_id)    REFERENCES trucks(id),
    FOREIGN KEY (location_id) REFERENCES locations(id)
);

-- menu_items: de gerechten van een truck op een bepaalde dag
-- available = 0 betekent: uitverkocht (FE3)
CREATE TABLE menu_items (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    truck_id  INT NOT NULL,
    name      VARCHAR(100)  NOT NULL,
    price     DECIMAL(6, 2) NOT NULL,
    available TINYINT(1)    NOT NULL DEFAULT 1,
    menu_date DATE NOT NULL,
    FOREIGN KEY (truck_id) REFERENCES trucks(id) ON DELETE CASCADE
);

-- =====================================================================
-- TESTDATA (geen echte personen)
-- CURDATE() = vandaag. Zo werkt de testdata op elke dag.
--
-- Testaccounts (wachtwoord voor allebei: demo-password)
--   beheerder@example.com  -> rol beheerder
--   planner@example.com    -> rol planner
-- =====================================================================

INSERT INTO users (naam, email, password_hash, role) VALUES
('Demo Beheerder', 'beheerder@example.com', '$2y$10$Ge8AauA5oalHQ/bQLIdWOO8POSaCsgTPaVKnvwWQm9QAoC5/.1ur.', 'beheerder'),
('Demo Planner',   'planner@example.com',   '$2y$10$Ge8AauA5oalHQ/bQLIdWOO8POSaCsgTPaVKnvwWQm9QAoC5/.1ur.', 'planner');

INSERT INTO trucks (naam, kitchen_type, description) VALUES
('Burger Beast', 'Burgers',     'Smashburgers van Hollands rundvlees.'),
('Taco Truck',   'Mexicaans',   'Verse taco''s en burrito''s.'),
('Pizza Wheels', 'Pizza',       'Pizza uit een houtoven.'),
('Green Bites',  'Vegetarisch', 'Plantaardige bowls en wraps.');

-- NDSM heeft een VERLOPEN vergunning (om FE7 te laten zien)
INSERT INTO locations (naam, address, city, permit_number, permit_end_date) VALUES
('Dam',         'Dam 1',              'Amsterdam', 'VG-001', CURDATE() + INTERVAL 1 YEAR),
('Museumplein', 'Museumplein 6',      'Amsterdam', 'VG-002', CURDATE() + INTERVAL 6 MONTH),
('Westerpark',  'Haarlemmerweg 8',    'Amsterdam', 'VG-003', CURDATE() + INTERVAL 7 DAY),
('NDSM',        'NDSM-Plein 28',      'Amsterdam', 'VG-004', CURDATE() - INTERVAL 10 DAY),
('Grote Markt', 'Grote Markt 2',      'Haarlem',   'VG-005', CURDATE() + INTERVAL 3 MONTH);

-- Openingstijden: elke plek is elke dag open (dag 1 t/m 7).
-- "Plek is dicht" (FE6) test je met een tijd buiten de openingstijden,
-- bijv. de Grote Markt om 19:00 (die sluit om 18:00).
-- Een hele dag dicht: verwijder in het scherm Plekken de openingstijd van die dag.
INSERT INTO opening_hours (location_id, day, open_time, close_time) VALUES
(1,1,'10:00','22:00'),(1,2,'10:00','22:00'),(1,3,'10:00','22:00'),(1,4,'10:00','22:00'),(1,5,'10:00','22:00'),(1,6,'10:00','22:00'),(1,7,'10:00','22:00'),
(2,1,'10:00','20:00'),(2,2,'10:00','20:00'),(2,3,'10:00','20:00'),(2,4,'10:00','20:00'),(2,5,'10:00','20:00'),(2,6,'10:00','20:00'),(2,7,'10:00','20:00'),
(3,1,'11:00','21:00'),(3,2,'11:00','21:00'),(3,3,'11:00','21:00'),(3,4,'11:00','21:00'),(3,5,'11:00','21:00'),(3,6,'11:00','21:00'),(3,7,'11:00','21:00'),
(4,1,'12:00','23:00'),(4,2,'12:00','23:00'),(4,3,'12:00','23:00'),(4,4,'12:00','23:00'),(4,5,'12:00','23:00'),(4,6,'12:00','23:00'),(4,7,'12:00','23:00'),
(5,1,'10:00','18:00'),(5,2,'10:00','18:00'),(5,3,'10:00','18:00'),(5,4,'10:00','18:00'),(5,5,'10:00','18:00'),(5,6,'10:00','18:00'),(5,7,'10:00','18:00');

-- Route 1 (vandaag) is goedgekeurd: bezoekers zien hem.
-- Route 2 (vandaag) is concept: bezoekers zien hem NIET.
-- Route 3 (morgen) is concept en gebruikt NDSM (verlopen vergunning).
INSERT INTO routes (route_date, status, created_by) VALUES
(CURDATE(),                    'goedgekeurd', 2),
(CURDATE(),                    'concept',     2),
(CURDATE() + INTERVAL 1 DAY,   'concept',     2);

INSERT INTO stops (route_id, truck_id, location_id, start_time, end_time, stop_order) VALUES
(1, 1, 1, '11:00', '14:00', 1),   -- Burger Beast op de Dam
(1, 1, 2, '15:00', '19:00', 2),   -- daarna Burger Beast op het Museumplein
(1, 3, 5, '11:30', '16:00', 3),   -- Pizza Wheels in Haarlem
(2, 4, 3, '12:00', '18:00', 1),   -- Green Bites (concept, niet openbaar)
(3, 2, 4, '13:00', '20:00', 1);   -- Taco Truck op NDSM (vergunning verlopen)

-- Menu's van vandaag en morgen. available = 0 is uitverkocht.
INSERT INTO menu_items (truck_id, name, price, available, menu_date) VALUES
(1, 'Classic burger',  9.50, 1, CURDATE()),
(1, 'Double cheese',  12.50, 0, CURDATE()),
(1, 'Friet',           3.75, 1, CURDATE()),
(3, 'Margherita',      9.00, 1, CURDATE()),
(3, 'Tartufo',        14.00, 0, CURDATE()),
(2, 'Taco al pastor',  8.50, 1, CURDATE() + INTERVAL 1 DAY),
(4, 'Buddha bowl',    10.50, 1, CURDATE());
