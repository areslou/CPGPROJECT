-- ==========================================
-- COMPLETE DATABASE SETUP FOR LSS SYSTEM WITH IMAGE SUPPORT
-- Run this entire file in phpMyAdmin SQL tab
-- ==========================================

-- ==========================================
-- 1. DATABASE & TABLE SETUP
-- ==========================================
DROP DATABASE IF EXISTS DB1;
DROP DATABASE IF EXISTS DB2;

CREATE DATABASE DB1;
CREATE DATABASE DB2;

-- Setup DB2
USE DB2;
CREATE TABLE IF NOT EXISTS Table1 (
    SampleID INT PRIMARY KEY,
    SampleData VARCHAR(50)
) ENGINE=InnoDB;

-- Setup DB1
USE DB1;

-- Enhanced StudentDetails table with better image support
CREATE TABLE IF NOT EXISTS StudentDetails (
    StudentNumber INT PRIMARY KEY,
    LastName      VARCHAR(50),
    FirstName     VARCHAR(50),
    MiddleName    VARCHAR(50),
    DegreeProgram VARCHAR(50),
    Email         VARCHAR(100),
    Scholarship   VARCHAR(150),
    Status        VARCHAR(20),
    ContactNumber VARCHAR(20),
    ProfilePicture VARCHAR(255) NULL,
    ImageUploadDate TIMESTAMP NULL,
    ImageFileSize INT NULL COMMENT 'File size in bytes',
    ImageMimeType VARCHAR(50) NULL COMMENT 'Image MIME type (e.g., image/jpeg, image/png)',
    INDEX idx_email (Email),
    INDEX idx_status (Status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL,
    student_number INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (student_number) REFERENCES StudentDetails(StudentNumber) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_student_number (student_number)
) ENGINE=InnoDB;

-- New table for storing image upload logs/history
CREATE TABLE IF NOT EXISTS ImageUploadLog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    uploaded_by INT NOT NULL COMMENT 'User ID who uploaded',
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    FOREIGN KEY (student_number) REFERENCES StudentDetails(StudentNumber) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student (student_number),
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB;

-- ==========================================
-- 2. INSERT CSV DATA (BATCH 1 of 3)
-- ==========================================
INSERT INTO StudentDetails (StudentNumber, LastName, FirstName, MiddleName, DegreeProgram, Email, Scholarship, Status, ContactNumber) VALUES
(12278440, 'LAÑADA', 'Larry Owence', 'E.', 'BS BIO-MBB', 'larry_lanada@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09672444896'),
(12333115, 'POLANCOS', 'Nathan Timothy', 'C.', 'MGT-BAS', 'nathan_polancos@dlsu.edu.ph', 'Brother President Scholarship Program', 'ACTIVE', '09688740201'),
(12320757, 'VILLASOTO', 'Kurt Luis', 'J.', 'BSE-ENG', 'kurt_villasoto@dlsu.edu.ph ', 'Rizal Provincial Government Scholarship', 'ACTIVE', '09499640472'),
(12429422, 'PADUA', 'Roi Christian', 'S.', 'POM-ENT', 'roi_padua@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0991 611 6552'),
(12312118, 'MILLARES', 'Brendan Lou', 'S.', 'BS CpE', 'brendan_millares@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09763887843'),
(12217379, 'TABANAO', 'Leigh Andrei', 'M.', 'BS CpE', 'leigh_tabanao@dlsu.edu.ph', 'Vaugirard Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '09995113556'),
(12339067, 'NGO', 'Jewel Andrea', 'C.', 'AEF-BSA', 'jewel_ngo@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0977 613 3935'),
(12311405, 'MOLINA', 'Edward', 'R.', 'BS-CIV', 'edward_molina@dlsu.edu.ph', 'Vaugirard Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '09649442129'),
(12328669, 'ECHEVARRIA', 'Chelsea Marie', 'F.', 'BSA', 'chelsea_marie_echevarria@dlsu.edu.ph', 'Archer Achiever Scholarship', 'ACTIVE', '09985709129'),
(12333700, 'MIRANDILLA', 'Samantha Rose Isabel', 'C.', 'BSE-BIO', 'samantha_mirandilla@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09171807575'),
(12371629, 'DELOS SANTOS', 'Marshall Zaint', 'C.', 'BS IE', 'marshall_delossantos@dlsu.edu.ph', '', 'ACTIVE', '09060035834'),
(12146110, 'ATO', 'Edric Luis', 'G.', 'BSMS ME', 'edric_ato@dlsu.edu.ph', 'Vaugirard Scholarship Program', 'ACTIVE', '09189911214'),
(12346136, 'GREGORIO', 'Angelica', 'G.', 'BS CpE', 'angelica_g_gregorio@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI RA 7687 Scholarship', 'ACTIVE', '09916587472'),
(12405345, 'JABER', 'Nabih Tarek', 'A.', 'BS-AEI and BS-APC', 'nabih_tarek_jaber@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '09628939507'),
(12208509, 'NAAG', 'Jamela', 'D.', 'BS BIO-MBB', 'jamela_naag@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09126919979'),
(12519081, 'DELA CRUZ', 'Carl Bien Angel', 'D.', 'BS PSYCH', 'carl_bien_delacruz@dlsu.edu.ph', 'Laguna 500', 'ACTIVE', '09619904172'),
(12517755, 'LUMBA', 'Naraeshcka Nailah', 'M.', 'AB PSM and BS LGL', 'naraeshcka_lumba@dlsu.edu.ph', 'Br. Andrew Gonzalez Academic Scholarship', 'ACTIVE', '0956 375 4103'),
(12413623, 'MARCOS', 'Alain Zuriel', 'Z.', 'BS CS-ST', 'alain_marcos@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '0976 110 9076'),
(12312630, 'ONG', 'Carlos Benedict', 'R.', 'BS ME', 'carlos_benedict_ong@dlsu.edu.ph', 'Archer Achiever Scholarship', '', '09772193010'),
(12512400, 'PALAZO', 'Thyronjay', '', 'BSMS CHE', 'thyronjay_palazo@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI Merit Scholarship', 'ACTIVE', '09477 908 347'),
(12542849, 'REBOLLIDO', 'Miracle Mark', 'R.', 'BS CIV', 'marcky_rebollido@dlsu.edu.ph', 'STAR Scholarship & DOST-SEI Merit Scholarship', 'ACTIVE', '0942 206 0637'),
(12511323, 'SANTIAGO', 'Andrei Marco', 'P.', 'BS ME', 'andrei_santiago@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '0917 178 0831'),
(12314579, 'SO', 'Nathaniel Luis', 'C.', 'BS IE-IT', 'nathaniel_luis_so@dlsu.edu.ph', 'Aboitiz Scholarship', 'ACTIVE', '09760736172'),
(12537187, 'VENTURA', 'Cyan Monte', '', 'BS CIV', 'cyan_ventura@dlsu.edu.ph', 'Laguna 500 & DOST-SEI Merit Scholarship', 'ACTIVE', '09667636998'),
(12447846, 'AÑONUEVO', 'Chrysler', 'R.', 'BSMS ME', 'chrysler_anonuevo@dlsu.edu.ph', 'Archer Achiever Scholarship', 'ACTIVE', '09213501097'),
(12346187, 'HERMOSO', 'Kirby Nikko', 'V.', 'AB DSM', 'kirby_hermoso@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09673626158'),
(12346012, 'APOLINARIO', 'Gian Emmanuel', 'G.', 'AB-POM', 'gian_apolinario@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & PWD Discount', 'ACTIVE', '0956 704 4548'),
(12330639, 'CABANSAG', 'Raymond', '', 'BS MKT', 'raymond_cabansag@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09673762056'),
(12347256, 'ILAGAN', 'Michaela Gestrell Mae', 'S.', 'BS PSYC', 'michaela_ilagan@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09216638897'),
(12322423, 'CONDOR', 'Krisul Isabel', 'L.', 'BS BIOMBB', 'krisul_condor@dlsu.edu.ph', 'DOST-SEI Merit Scholarship & Alvarez Foundation Scholarship', 'ACTIVE', '09953229255'),
(12208266, 'PAÑARES', 'Kathy', 'C.', 'BS BIOMED', 'kathy_panares@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI Merit Scholarship', 'ACTIVE', '09772416756'),
(12424684, 'COLORADO', 'Angelle Marie', 'V.', 'ISE-MGT', 'angelle_colorado@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09516252776'),
(12484288, 'GALLEGO', 'Airah Dave', 'V.', 'BS LGL', 'airah_gallego@dlsu.edu.ph', 'Br. Andrew Gonzalez Academic Scholarship', 'ACTIVE', '09629815723'),
(12312940, 'REYES', 'Geovin Jesus', 'R.', 'BSMS CHE', 'geovin_reyes@dlsu.edu.ph', 'STAR Scholarship & DOST-SEI Merit Scholarship', 'ACTIVE', '09762527530'),
(12588210, 'BERANO', 'R Jay', 'V.', 'BS PSYC', 'r_berano@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09683104547'),
(12501611, 'DE GUZMAN', 'Ryuichi Rosh', 'J.', 'BHUMSRV', 'ryuichi_deguzman@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09055409908'),
(12537802, 'ABRIL', 'Bettina Wyaenet', 'D.', 'BS PSYC', 'bettina_abril@dlsu.edu.ph', 'Laguna 500', 'ACTIVE', '09171554706'),
(12416800, 'EUSEBIO', 'Angela Trixie', 'L.', 'BS CHE', 'angela_eusebio@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09566357969'),
(12338567, 'IGLESIA', 'Frances Dion Jave', 'A.', 'BS BMES', 'frances_iglesia@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09565044594'),
(12110112, 'VILLARIEZ', 'Ralph Matthew', 'A.', 'BS BMES', 'ralph_villariez@dlsu.edu.ph', 'PWD Discount', 'ACTIVE', '09179619407'),
(12540811, 'BUHAY', 'Abihail Faith', 'S.', 'BSED BIO', 'abihail_buhay@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09669581697'),
(12410268, 'DELA CRUZ', 'Jessica Mae', '', 'BSCS-ST', 'jessica_delacruz@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09177120125'),
(12338885, 'LIM', 'Miyaki Jan', '', 'BIO MBB', 'miyaki_lim@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI Merit Scholarship', 'ACTIVE', '09615337446'),
(12204447, 'MANIQUIS', 'Cheska', '', 'AB-ELS', 'cheska_maniquis@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09765130473'),
(12415324, 'POLICARPIO', 'Rozette Dominique', '', 'BSCS-ST', 'rozette_policarpio@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09636161553'),
(12441139, 'RAMIREZ', 'Jann Simone River', '', 'BS-MKT', 'jann_simone_ramirez@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09774499309'),
(12518786, 'BALLESTEROS', 'Louisse Lana', '', 'AB-OSDM', 'louisse_ballesteros@dlsu.edu.ph', 'OWWA Scholarship', 'ACTIVE', '09682554197'),
(12521965, 'CANAY', 'Maria Ysabella', '', 'AB-PLS', 'maria_ysabella_canay@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09202820250'),
(12417564, 'CERDIÑO', 'John Arnel', '', 'BS BIOMED', 'john_arnel_cerdino@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09663964587'),
(12516619, 'ERANDIO', 'Crystal Jean', 'A.', 'BSBIO MBB', 'crystal_erandio@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI Merit Scholarship', 'ACTIVE', '0969 523 7335');

-- ==========================================
-- 3. INSERT CSV DATA (BATCH 2 of 3)
-- ==========================================
INSERT INTO StudentDetails (StudentNumber, LastName, FirstName, MiddleName, DegreeProgram, Email, Scholarship, Status, ContactNumber) VALUES
(12513121, 'TIAMZON', 'Arra Jhane', 'T.', 'BSMS CHE', 'arra_tiamzon@dlsu.edu.ph', 'Vaugirard Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '0906 207 1149'),
(12414131, 'Malayao', 'Kristopher', 'S.', 'BSIT', 'kristopher_malayao@dlsu.edu.ph', 'Br. Andrew Gonzalez Academic Scholarship', 'ACTIVE', '0915 500 0064'),
(12332429, 'Landas', 'Maundy', 'C.', 'BS MGT-BAS', 'maundy_landas@dlsu.edu.ph', 'UT Foundation Inc.', 'ACTIVE', '0966 928 5390'),
(12409073, 'Marzan', 'Mikaela Adeline', 'R.', 'BSE-ENG', 'mikaela_marzan@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0976 415 1376'),
(12478377, 'Raveno', 'Francheska April', 'I.', 'BSE-ENG', 'francheska_raveno@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0970 8514 935'),
(12348546, 'Ugalde', 'Axela Keeza', 'T.', 'BIOSEC', 'axela_ugalde@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI RA 7687 Scholarship', 'ACTIVE', '0967 478 7622'),
(12475106, 'Tafalla', 'Carl Andrei', '', 'BS-CpE', 'carl_tafalla@dlsu.edu.ph', 'Animo Grant & DOST-SEI RA 7687 Scholarship', 'ACTIVE', '0961 418 5853'),
(12240462, 'Valencia', 'Jan Irvine Miguel', 'V.', 'BS-STAT', 'jan_irvine_valencia@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '0918 661 9844'),
(12535613, 'Villagantol', 'Czara Lenore', '', 'BS APC', 'czara_lenore_villagantol@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0970 969 7518 '),
(12479543, 'Lee', 'Ashley Fiona', '', 'BSCS-CSE', 'ashley_lee@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09062511119'),
(12526940, 'Alonzo', 'Alysson Heart', '', 'BSA', 'alysson_alonzo@dlsu.edu.ph', 'Br. Andrew Gonzalez Academic Scholarship', 'ACTIVE', '09662205518'),
(12505307, 'Pangilinan', 'Mateo Luis', '', 'BS CS-CSE', 'mateo_pangilinan@dlsu.edu.ph', 'Archer Achiever Scholarship', 'ON LEAVE', '0915 219 7904'),
(12421057, 'Palattao', 'Eliyah Annika', 'C.', 'BS BIOMED', 'eliyah_palattao@dlsu.edu.ph', '', '', '0908 564 7158'),
(12508950, 'El Bsat', 'Yasmine', '', 'BS MEEMTE', 'yassie_elbsat@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI RA 7687 Scholarship', 'ACTIVE', '09391218095'),
(12483699, 'CRUZ', 'Reyben', '', 'BS-MKT', 'reyben_cruz@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0981 298 1973'),
(12443654, 'REYES', 'Kierstien Faith', 'M.', 'BSA', 'kierstien_faith_reyes@dlsu.edu.ph', 'Vaugirard Scholarship Program', 'ACTIVE', '0915 427 4315'),
(12339423, 'PACLEB', 'Meg Krystian', 'R.', 'AEF-BSA', 'meg_pacleb@dlsu.edu,ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0908 388 0765'),
(12327751, 'CHANG', 'Camille Margaret', 'E.', 'BS APC', 'camille_margaret_chang@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '0956 160 5857'),
(12434183, 'DATU', 'Kimberly', 'N.', 'BSA', 'kimberly_datu@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0991 394 8213'),
(12410624, 'PERIDA', 'Sunnya Evon', 'M.', 'AEF-BSA', 'sunnya_perida@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0921 475 5800'),
(12409510, 'ENCARNACION', 'Ashley Isabelle', 'R.', 'BS CS-CSE', 'ashley_encarnacion@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '0945 854 6352'),
(12417173, 'GUDANI', 'Rhojan Luis', 'I.', 'BS BIOMED', 'rhojan_gudani@dlsu.edu.ph', 'DOST-SEI Merit Scholarship & Lifeline Assistance for Neighbors In-need Scholarship', 'ACTIVE', '0916 486 6550'),
(12406309, 'ALABE', 'Stephanie', 'M.', 'AEF-BSA', 'stephanie_alabe@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0993 342 0315'),
(12527718, 'CHAN', 'Sophia Monique', 'O.', 'BSA', 'sophia_monique_o_chan@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '0917 555 8662'),
(12507318, 'ANG NGO CHING', 'Patrick Kyle', 'W.', 'BS CS-ST', 'patrick_kyle_angngoching@dlsu.edu.ph', 'Archer Achiever Scholarship', 'ACTIVE', '0906 081 3885'),
(12514756, 'EUGENIO', 'Leona Francine', 'S.', 'BS HBIO', 'leona_eugenio@dsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '0933 039 5500'),
(12177687, 'FERNANDEZ', 'Averymae', 'M.', 'BSA', 'avery_fernandez@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0917 844 9468'),
(12528404, 'JIMENEZ', 'Cyean Rei', 'A.', 'BSA', 'cyean_jimenez@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0921 575 5878'),
(12532649, 'M.', 'SAZON. Alyssa Fate Margareth', '', 'BSA', 'alyssa_fate_sazon@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '0945 711 2866'),
(12345059, 'FRANCISCO', 'Precious Elaine', 'M.', 'AEF-MKT', 'precious_francisco@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0975 961 1392'),
(12478180, 'TAPIS', 'Andrea Marie', 'O.', 'BS APC', 'andrea_marie_tapis@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0947 622 9791'),
(12405981, 'FAVILA', 'Lynn Kelly', 'R.', 'AEI-APC', 'lynn_kelly_favila@dlsu.edu.ph ', 'STAR Scholarship', 'ACTIVE', '0923 107 0015 '),
(12315125, 'FRANCISCO', 'Alan Caesar', 'N.', 'BS-CE', 'alan_francisco@dlsu.edu.ph', 'Gokongwei Next Gen Scholarship for Excellence', 'ACTIVE', '0921 738 9349'),
(12223069, 'PASA', 'Ayn Christine', 'V.', 'BS-PSYC', 'ayn_pasa@dlsu.edu.ph', 'DOST-SEI RA 7687 Scholarship', 'ACTIVE', '0950 136 9453'),
(12370924, 'TOLIBAS', 'Princes Romylen', 'O.', 'AEF-BSA', 'princes_tolibas@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0956 270 5017'),
(12412910, 'INOCENCIO', 'Mickael Chazwick', 'C.', 'Bscs-st', 'mickael_inocencio@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09994675852'),
(12479640, 'BAYACAL', 'Raiden Bernard', 'B.', 'BS-APC', 'raiden_bayacal@dlsu.edu.ph', 'Animo Grant', 'ACTIVE', '09953874170'),
(12310123, 'ESCALANTE', 'Diodel Alexis', 'E.', 'BSCE', 'diodel_escalante@dlsu.edu.ph', 'Vaugirard Scholarship Program', 'ACTIVE', '09213137143'),
(12526878, 'BALAIS', 'Emiel Shayleigh', 'V.', 'BS-APC', 'emiel_shayleigh_balais@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09312101055'),
(12522619, 'PABILLAR', 'Ray Emmanuel', 'J.', 'BS-PSYC', 'ray_pabillar@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09189377676'),
(12514144, 'BUENO', 'Isaiah Christien', 'V.', 'BS-CHY', 'isaiah_bueno@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09565529947'),
(12317012, 'SIMBILLO', 'Acey Errol', 'M.', 'BS-ME', 'acey_simbillo@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09610895740'),
(12513458, 'DIACONO', 'Francis Gabriel', 'B.', 'BS-STAT', 'francis_diacono@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09612133832'),
(12520047, 'GOYENA', 'Sabrina Veronica', 'R.', 'BSMS-IE', 'sabrina_veronica_goyena@dlsu.edu.ph', 'STAR Scholarship & DOST-SEI Merit Scholarship', 'ACTIVE', '09171575076'),
(12235385, 'ADORNA', 'Christian', 'M.', 'BS-APC', 'shan_adorna@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '0977 268 3839'),
(12476242, 'SAN JOSE', 'Kyrstie Joyce Ann', 'L.', 'BSITS', 'kyrstie_sanjose@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant & DOST-SEI RA 7687 Scholarship', 'ON LEAVE', '0976 071 5305'),
(12325775, 'VALENZUELA', 'Alexis Caitlin', 'L.', 'BS STAT', 'alexis_valenzuela@dlsu.edu.ph', 'Brother President Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '0915 028 9928'),
(12310514, 'CHUA', 'Joshua Emmanuel', 'M.', 'BS-CE', 'joshua_emmanuel_chua@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '0933 987 7655'),
(12509299, 'DEL RIO', 'Dylan', 'D.', 'BSCE', 'dylan_d_delrio@dlsu.edu.ph', 'Vaugirard Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '0968 788 3057'),
(12516791, 'OCHO', 'Ma. Tricia Jasmin', 'M.', 'BS-HBIO', 'ma_tricia_jasmin_ocho@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '0969 322 4140 ');

-- ==========================================
-- 4. INSERT CSV DATA (BATCH 3 of 3)
-- ==========================================
INSERT INTO StudentDetails (StudentNumber, LastName, FirstName, MiddleName, DegreeProgram, Email, Scholarship, Status, ContactNumber) VALUES
(12536784, 'DELA PAZ', 'Luisa Marie', 'V.', 'BS-APC', 'luisa_marie_v_delapaz@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09613016500'),
(12531286, 'GO', 'Kyza Deniece', 'T.', 'BS-APC', 'kyza_deniece_go@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0995 479 0787 '),
(12230359, 'LARAÑO', 'Dwaine Ira', 'K.', 'BS BIO-MBB', 'dwaine_larano@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09292959933'),
(12515078, 'SOLIVEN', 'Alessia Isabel', 'D.', 'BSE-MTH', 'alessia_soliven@dlsu.edu.ph', 'Br. Andrew Gonzalez Academic Scholarship', 'ACTIVE', '09276887618'),
(12515361, 'LUMBO', 'Eduard Cris', 'A.', 'BS CHYB', 'eduard_lumbo@dlsu.edu.ph', 'DOST-SEI RA 7687 Scholarship', 'ACTIVE', '09773819189'),
(12448508, 'CORTEZ', 'Von Lemoure', 'R.', 'BSMS-CHE', 'von_cortez@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09916752648'),
(12513989, 'SAN AGUSTIN', 'Sebastien Mikhail', 'A.', 'BS-BCHEM', 'sebastien_sanagustin@dlsu.edu.ph', 'Vaugirard Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '09478925906'),
(12505676, 'MIZUNO', 'Hailie Beatrice', 'L.', 'AEF-MKT', 'hailie_mizuno@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09166336540'),
(12528250, 'GUISDAN', 'Pauleen Mariah', 'S.', 'BS-BIOMED', 'pauleen_guisdan@dlsu.edu.ph', 'DOST-SEI Merit Scholarship & St. La Salle Financial Assistance Grant', 'ACTIVE', '09153202319'),
(12271879, 'Visto', 'Herise Janah', 'F.', 'BS-IT', 'herise_visto@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09774572109'),
(12516538, 'LACEDA', 'Dion John Tyler', 'S.', 'BS-CHY', 'dion_laceda@dlsu.edu.ph', 'Vaugirard Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '09665341042'),
(12511900, 'REGENCIA', 'John Angelo', 'R.', 'BSMEEMTE14', 'john_angelo_regencia@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09949769098'),
(12423270, 'ASTRERA', 'Sofia Ysabelle', 'C.', 'BS-PSYC', 'sofia_astrera@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09970866886'),
(12421553, 'MANIQUIS', 'Neil Kyle', 'O.', 'BS PHY-MI', 'neil_maniquis@dlsu.edu.ph', 'STAR Scholarship & DOST-SEI RA 7687 Scholarship', 'ACTIVE', '0922 734 7174'),
(12373419, 'MARASIGAN', 'Gyrard Michael', 'C.', 'BS BIO-MEDS', 'gyrard_marasigan@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '0919 009 6182'),
(12430013, 'NAVAREZ', 'Jona Gabriella', 'S.', 'BS-PSYC', 'jona_navarez@dlsu.edu.ph', 'Brother President Scholarship Program & DOST-SEI Merit Scholarship', 'ACTIVE', '0954 406 4772'),
(12324477, 'OCHO', 'Martin Johan', 'M.', 'BS-STAT', 'martin_johan_ocho@dlsu.edu.ph', 'STAR Scholarship & DOST-SEI Merit Scholarship', 'ACTIVE', '0936 908 5940'),
(12414638, 'ONG', 'Kien Patrick Zharvy', 'A.', 'BS-IT', 'kien_ong@dlsu.edu.ph', 'Animo Grant & DOST-SEI Merit Scholarship', 'ACTIVE', '0927 368 6390'),
(12416819, 'DELA NOCHE', 'Arianne', 'C.', 'BS-HUMBIO', 'arianne_delanoche@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0945 295 7831'),
(12528013, 'GIMAO', 'Cheyen', 'A.', 'BS-PSYC', 'cheyen_gimao@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0997 997 0741'),
(12515477, 'ROMUALDO', 'Jim Boone', 'S.', 'BS-CHY', 'jim_romualdo@dlsu.edu.ph', 'STAR Scholarship & DOST-SEI Merit Scholarship', 'ACTIVE', '0991 339 2497'),
(12447943, 'CAO', 'Eljan', 'R.', 'BSMS CIV', 'eljan_cao@dlsu.edu.ph', 'Animo Grant & DOST-SEI Merit Scholarship', 'ACTIVE', '0926 027 5049'),
(12479047, 'BUENSALIDA', 'Cielo Mae', 'B.', 'BECED', 'cielo_buensalida@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '0993 865 9243');

-- ==========================================
-- 5. INSERT USERS (ADMIN & STUDENT)
-- Passwords are hashed using BCrypt.
-- Default Admin: admin@lss.org / admin123
-- Default Student: larry_lanada@dlsu.edu.ph / student123
-- ==========================================
INSERT INTO users (email, password, role, student_number) VALUES
('admin@lss.org', '$2y$10$IbVgOyyrQ6ekecLdCMwSMuTZqbYqGPohYPcVWMdQuiEzRWCC4eHs.', 'admin', NULL),
('larry_lanada@dlsu.edu.ph', '$2y$10$PyouvDTBnKE8Vpgjio22YutRz5RU1UhDwUl7hQvll/bN3.WoFzXq.', 'student', 12278440);
