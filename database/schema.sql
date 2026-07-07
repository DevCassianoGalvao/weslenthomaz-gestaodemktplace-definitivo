-- Schema do Sistema de Gestão de Marketplaces
-- Fonte: PRD-sistema-gestao-marketplaces, seção 4 (Modelo de dados)
-- Charset utf8mb4 / InnoDB em todas as tabelas (suporte a FK e emojis/acentos)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS clients (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) UNIQUE NOT NULL,
  logo_url VARCHAR(255) NULL,
  brand_color VARCHAR(7) NULL,
  status ENUM('active','paused','archived') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','operator','client') NOT NULL,
  client_id INT NULL, -- preenchido só quando role = 'client'
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS marketplaces (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(60) UNIQUE NOT NULL,
  color VARCHAR(7) NULL,
  icon VARCHAR(60) NULL,
  is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_marketplaces (
  client_id INT NOT NULL,
  marketplace_id INT NOT NULL,
  PRIMARY KEY (client_id, marketplace_id),
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS periods (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  label VARCHAR(60) NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reference_month CHAR(7) NOT NULL, -- 'YYYY-MM'
  created_by INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entries (
  id INT PRIMARY KEY AUTO_INCREMENT,
  period_id INT NOT NULL,
  marketplace_id INT NOT NULL,
  value_cents BIGINT NOT NULL DEFAULT 0,
  orders_count INT NOT NULL DEFAULT 0, -- nº de pedidos (base do ticket médio)
  notes VARCHAR(255) NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_period_marketplace (period_id, marketplace_id),
  FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE CASCADE,
  FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entry_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  entry_id INT NULL, -- pode ser NULL se o lançamento original foi apagado
  period_id INT NOT NULL,
  client_id INT NOT NULL,
  marketplace_id INT NOT NULL,
  action ENUM('create','update','delete') NOT NULL,
  old_value_cents BIGINT NULL,
  new_value_cents BIGINT NULL,
  old_orders_count INT NULL,
  new_orders_count INT NULL,
  changed_by INT NOT NULL,
  changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id),
  FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
