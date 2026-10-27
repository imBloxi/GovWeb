-- GovWeb database schema (MySQL 8 / MariaDB 10.6+)
-- Import this first, then seed.sql for demo data.

CREATE DATABASE IF NOT EXISTS `government_registration`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `government_registration`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log, court_charges, court_documents, court_files,
  operations, dispatches, warrants, criminal_records, businesses, licenses,
  users, civilians;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE civilians (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  person_code  VARCHAR(20)  NOT NULL UNIQUE,
  first_name   VARCHAR(60)  NOT NULL,
  last_name    VARCHAR(60)  NOT NULL,
  gender       ENUM('male','female','other') NOT NULL,
  dob          DATE         NOT NULL,
  email        VARCHAR(120) NULL UNIQUE,
  phone        VARCHAR(30)  NULL,
  address      VARCHAR(255) NULL,
  occupation   VARCHAR(80)  NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_civilians_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username       VARCHAR(50)  NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  full_name      VARCHAR(120) NOT NULL,
  role           ENUM('admin','registrar','officer','judge','civilian') NOT NULL,
  civilian_id    INT UNSIGNED NULL,
  failed_logins  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until   DATETIME NULL,
  last_login_at  DATETIME NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_civilian FOREIGN KEY (civilian_id) REFERENCES civilians(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE licenses (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  civilian_id     INT UNSIGNED NOT NULL,
  license_type    ENUM('car','motorcycle','scooter','truck') NOT NULL,
  license_number  VARCHAR(30) NOT NULL UNIQUE,
  issued_on       DATE NOT NULL,
  expires_on      DATE NOT NULL,
  status          ENUM('active','suspended','revoked') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_licenses_civilian FOREIGN KEY (civilian_id) REFERENCES civilians(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE businesses (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id       INT UNSIGNED NOT NULL,
  name           VARCHAR(120) NOT NULL,
  business_type  VARCHAR(80)  NOT NULL,
  address        VARCHAR(255) NOT NULL,
  registered_on  DATE NOT NULL,
  status         ENUM('active','closed') NOT NULL DEFAULT 'active',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_businesses_owner FOREIGN KEY (owner_id) REFERENCES civilians(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE criminal_records (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  civilian_id   INT UNSIGNED NOT NULL,
  offense       VARCHAR(120) NOT NULL,
  description   TEXT NULL,
  offense_date  DATE NOT NULL,
  sentence      VARCHAR(255) NULL,
  officer_id    INT UNSIGNED NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_records_civilian FOREIGN KEY (civilian_id) REFERENCES civilians(id) ON DELETE CASCADE,
  CONSTRAINT fk_records_officer  FOREIGN KEY (officer_id)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Officers request warrants (pending), judges issue or cancel them,
-- officers mark issued warrants as served.
CREATE TABLE warrants (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(20) NOT NULL UNIQUE,
  civilian_id  INT UNSIGNED NOT NULL,
  reason       VARCHAR(160) NOT NULL,
  description  TEXT NULL,
  status       ENUM('pending','issued','served','cancelled') NOT NULL DEFAULT 'pending',
  requested_by INT UNSIGNED NULL,
  decided_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_warrants_status (status),
  CONSTRAINT fk_warrants_civilian  FOREIGN KEY (civilian_id)  REFERENCES civilians(id) ON DELETE CASCADE,
  CONSTRAINT fk_warrants_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_warrants_decider   FOREIGN KEY (decided_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dispatches (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message     TEXT NOT NULL,
  location    VARCHAR(160) NULL,
  priority    ENUM('low','normal','urgent') NOT NULL DEFAULT 'normal',
  status      ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dispatches_status (status),
  CONSTRAINT fk_dispatches_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE operations (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  agency          ENUM('police','government') NOT NULL,
  name            VARCHAR(160) NOT NULL,
  operation_type  VARCHAR(80)  NOT NULL,
  status          ENUM('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
  start_date      DATE NOT NULL,
  end_date        DATE NULL,
  description     TEXT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_operations_agency (agency, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE court_files (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  file_number  VARCHAR(30)  NOT NULL UNIQUE,
  case_title   VARCHAR(200) NOT NULL,
  description  TEXT NULL,
  filed_on     DATE NOT NULL,
  status       ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE court_documents (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  document_code  VARCHAR(20)  NOT NULL UNIQUE,
  court_file_id  INT UNSIGNED NULL,
  civilian_id    INT UNSIGNED NOT NULL,
  court_name     VARCHAR(160) NOT NULL,
  judge_name     VARCHAR(120) NOT NULL,
  case_number    VARCHAR(40)  NOT NULL,
  court_date     DATE NOT NULL,
  description    TEXT NULL,
  issued_by      INT UNSIGNED NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_documents_file     FOREIGN KEY (court_file_id) REFERENCES court_files(id) ON DELETE SET NULL,
  CONSTRAINT fk_documents_civilian FOREIGN KEY (civilian_id)   REFERENCES civilians(id) ON DELETE CASCADE,
  CONSTRAINT fk_documents_user     FOREIGN KEY (issued_by)     REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE court_charges (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  document_id  INT UNSIGNED NOT NULL,
  charge       VARCHAR(255) NOT NULL,
  CONSTRAINT fk_charges_document FOREIGN KEY (document_id) REFERENCES court_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  action      VARCHAR(40)  NOT NULL,
  entity      VARCHAR(40)  NOT NULL,
  entity_id   INT UNSIGNED NULL,
  summary     VARCHAR(255) NOT NULL,
  ip          VARCHAR(45)  NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
