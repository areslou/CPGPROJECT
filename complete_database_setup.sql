-- ==========================================
-- COMPLETE DATABASE SETUP FOR LSS SYSTEM
-- ==========================================

-- 1. RESET DATABASES
DROP DATABASE IF EXISTS DB1;
DROP DATABASE IF EXISTS DB2;

CREATE DATABASE DB1;
CREATE DATABASE DB2;

-- 2. SETUP DB2 (Auxiliary)
USE DB2;
CREATE TABLE IF NOT EXISTS Table1 (
    SampleID INT PRIMARY KEY,
    SampleData VARCHAR(50)
) ENGINE=InnoDB;

-- 3. SETUP DB1 (Main System)
USE DB1;

-- TABLE: StudentDetails
-- Acts as both the Profile and Login table for students
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
    
    -- Profile Picture
    ProfilePicture VARCHAR(255) NULL,
    ImageUploadDate TIMESTAMP NULL,
    ImageFileSize INT NULL,
    ImageMimeType VARCHAR(50) NULL,
    
    -- GPA Submission
    Term1GPA DECIMAL(3,2) NULL,
    GPAScreenshot VARCHAR(255) NULL,
    GPASubmissionDate TIMESTAMP NULL,
    
    -- Requirements Submission
    RequirementsFile VARCHAR(255) NULL,
    RequirementsSubmissionDate TIMESTAMP NULL,
    
    -- Password
    password_hash VARCHAR(255) NULL,
    
    INDEX idx_email (Email),
    INDEX idx_status (Status)
) ENGINE=InnoDB;

-- TABLE: users (ADMIN ONLY)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- TABLE: ScheduledEmails (For Email Blast)
CREATE TABLE IF NOT EXISTS ScheduledEmails (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  target_scholarship VARCHAR(100) DEFAULT 'all',
  target_status VARCHAR(50) DEFAULT 'all',
  scheduled_at DATETIME NOT NULL,
  status ENUM('pending', 'processing', 'sent', 'cancelled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- TABLE: EmailLogs (For History)
CREATE TABLE IF NOT EXISTS EmailLogs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  target_group VARCHAR(255),
  status VARCHAR(50),
  recipient_count INT DEFAULT 0,
  scheduled_at DATETIME NULL,
  sent_at DATETIME NULL
) ENGINE=InnoDB;

-- ==========================================
-- 4. INSERT STUDENTS (Passwords initially NULL)
-- ==========================================
INSERT INTO StudentDetails 
(StudentNumber, LastName, FirstName, MiddleName, DegreeProgram, Email, Scholarship, Status, ContactNumber, password_hash) VALUES
(12278440, 'SALVADOR', 'Adrian Mateo', 'F.', 'BS CS-ST', 'adr_sal@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09123456781', NULL),
(12333115, 'DE GUZMAN', 'Liam Roel', 'T.', 'BS BIO-MBB', 'lia_deg@dlsu.edu.ph', 'DOST-SEI Merit Scholarship', 'ACTIVE', '09123456782', NULL),
(12320757, 'FERNANDEZ', 'Rafael Cyrus', 'M.', 'BS CHE', 'raf_fer@dlsu.edu.ph', 'Brother President Scholarship Program', 'ACTIVE', '09123456783', NULL),
(12429422, 'MANALO', 'Jace Damien', 'R.', 'BS CIV', 'jac_man@dlsu.edu.ph', 'Brother President Scholarship Program', 'ACTIVE', '09123456784', NULL),
(12312118, 'CRUZ', 'Mark Elyon', 'S.', 'BSME', 'mar_cru@dlsu.edu.ph', 'Rizal Provincial Government Scholarship', 'ACTIVE', '09123456785', NULL),
(12217379, 'IGNACIO', 'Kyle Benedict', 'A.', 'BS STAT', 'kyl_ign@dlsu.edu.ph', 'Rizal Provincial Government Scholarship', 'ACTIVE', '09123456786', NULL),
(12339067, 'SANTOS', 'Aiden Calix', 'J.', 'BS PSYC', 'aid_san@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09123456787', NULL),
(12311405, 'VALDERAMA', 'Theo Marcus', 'L.', 'BS MKT', 'the_val@dlsu.edu.ph', 'St. La Salle Financial Assistance Grant', 'ACTIVE', '09123456788', NULL),
(12328669, 'LOPEZ', 'Ezekiel Rowan', 'P.', 'BS CIV', 'eze_lop@dlsu.edu.ph', 'Vaugirard Scholarship Program', 'ACTIVE', '09123456789', NULL),
(12333700, 'CARBALLO', 'Noah Isandro', 'G.', 'BS BIO', 'noa_car@dlsu.edu.ph', 'Vaugirard Scholarship Program', 'ACTIVE', '09123456790', NULL),
(12371629, 'ROBLES', 'Jairus Enzo', 'A.', 'BS CpE', 'jai_rob@dlsu.edu.ph', 'Archer Achiever Scholarship', 'ACTIVE', '09123456791', NULL),
(12146110, 'TAN', 'Reiven Cyrus', 'D.', 'BSME', 'rei_tan@dlsu.edu.ph', 'Archer Achiever Scholarship', 'ACTIVE', '09123456792', NULL),
(12346136, 'MARCIAL', 'Harvey Luke', 'R.', 'BS AEI', 'har_mar@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '09123456793', NULL),
(12405345, 'LEGASPI', 'Zion Elric', 'P.', 'BS APC', 'zio_leg@dlsu.edu.ph', 'STAR Scholarship', 'ACTIVE', '09123456794', NULL),
(12208509, 'NIEVA', 'Xander Lev', 'H.', 'BS PSYC', 'xan_nie@dlsu.edu.ph', 'Laguna 500', 'ACTIVE', '09123456795', NULL),
(12519081, 'CLEMENTE', 'Daryl Mason', 'B.', 'BS CIV', 'dar_cle@dlsu.edu.ph', 'Laguna 500', 'ACTIVE', '09123456796', NULL),
(12517755, 'MENDEZ', 'Cairo Zed', 'R.', 'BSCS-ST', 'cai_men@dlsu.edu.ph', 'Br. Andrew Gonzalez Academic Scholarship', 'ACTIVE', '09123456797', NULL),
(12413623, 'GALVEZ', 'Jiro Phoenix', 'L.', 'BS LGL', 'jir_gal@dlsu.edu.ph', 'Br. Andrew Gonzalez Academic Scholarship', 'ACTIVE', '09123456798', NULL),
(12312630, 'YU', 'Nathan Zeph', 'C.', 'BS IE-IT', 'nat_yu@dlsu.edu.ph', 'Aboitiz Scholarship', 'ACTIVE', '09123456799', NULL),
(12512400, 'VASQUEZ', 'Hunter Jax', 'M.', 'BSME', 'hun_vas@dlsu.edu.ph', 'Aboitiz Scholarship', 'ACTIVE', '09123456800', NULL);

-- ==========================================
-- 5. INSERT ADMIN (Password: admin123)
-- ==========================================
INSERT INTO users (email, password, role) VALUES
('admin@lss.org', '$2y$10$IbVgOyyrQ6ekecLdCMwSMuTZqbYqGPohYPcVWMdQuiEzRWCC4eHs.', 'admin');

-- ==========================================
-- 6. IF UPDATING EXISTING DATABASE, USE THIS:
-- ==========================================
-- ALTER TABLE StudentDetails 
-- ADD COLUMN Term1GPA DECIMAL(3,2) NULL,
-- ADD COLUMN GPAScreenshot VARCHAR(255) NULL,
-- ADD COLUMN GPASubmissionDate TIMESTAMP NULL,
-- ADD COLUMN RequirementsFile VARCHAR(255) NULL,
-- ADD COLUMN RequirementsSubmissionDate TIMESTAMP NULL;