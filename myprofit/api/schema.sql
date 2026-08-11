-- MyProfit v2 — schema.sql. The ONLY tables (ARCHITECTURE.md §2). Run once in phpMyAdmin.
CREATE TABLE IF NOT EXISTS myprofit_leads (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  source        ENUM('estimator','contact') NOT NULL,
  name          VARCHAR(120) NOT NULL DEFAULT '',
  email         VARCHAR(190) NOT NULL,
  company       VARCHAR(160) NOT NULL DEFAULT '',
  phone         VARCHAR(40)  NOT NULL DEFAULT '',
  est_revenue   DECIMAL(12,2) NULL,
  est_margin_pp DECIMAL(6,2)  NULL,
  message       TEXT NULL,
  INDEX idx_email (email), INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS myprofit_intakes (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  product        ENUM('triage','diagnostic') NOT NULL,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  company        VARCHAR(160) NOT NULL,
  phone          VARCHAR(40)  NOT NULL DEFAULT '',
  venue_type     VARCHAR(60)  NOT NULL DEFAULT '',
  figures_notes  TEXT NULL,
  main_concern   VARCHAR(255) NOT NULL DEFAULT '',
  INDEX idx_email (email), INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
