-- EPR Liability Desk — schema.sql
-- The ONLY tables in this system (ARCHITECTURE.md §4). Run once in Hostinger phpMyAdmin.

CREATE TABLE IF NOT EXISTS epr_leads (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  source        ENUM('estimator','contact') NOT NULL,
  name          VARCHAR(120) NOT NULL DEFAULT '',
  email         VARCHAR(190) NOT NULL,
  company       VARCHAR(160) NOT NULL DEFAULT '',
  phone         VARCHAR(40)  NOT NULL DEFAULT '',
  est_tonnes    DECIMAL(10,2) NULL,
  est_liability DECIMAL(12,2) NULL,
  message       TEXT NULL,
  INDEX idx_email (email),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS epr_intakes (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  product        ENUM('snapshot','forecast') NOT NULL,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  company        VARCHAR(160) NOT NULL,
  phone          VARCHAR(40)  NOT NULL DEFAULT '',
  turnover_band  VARCHAR(40)  NOT NULL DEFAULT '',
  tonnage_notes  TEXT NULL,
  materials_json TEXT NULL,
  main_concern   VARCHAR(255) NOT NULL DEFAULT '',
  INDEX idx_email (email),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
