-- CRM Stages Database Schema
-- For Joomla + MySQL

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Grant permissions
GRANT ALL PRIVILEGES ON joomla_crm.* TO 'joomla'@'%';
FLUSH PRIVILEGES;

USE joomla_crm;

-- Companies table
CREATE TABLE IF NOT EXISTS crm_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    inn VARCHAR(20) DEFAULT NULL,
    contact_person VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    current_stage VARCHAR(20) NOT NULL DEFAULT 'C0',
    assigned_manager INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stage (current_stage),
    INDEX idx_manager (assigned_manager),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events/History table (Event Sourcing)
CREATE TABLE IF NOT EXISTS crm_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    old_stage VARCHAR(20) DEFAULT NULL,
    new_stage VARCHAR(20) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_type (event_type),
    INDEX idx_created (created_at),
    INDEX idx_company_created (company_id, created_at),
    FOREIGN KEY (company_id) REFERENCES crm_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discovery form data
CREATE TABLE IF NOT EXISTS crm_discovery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL UNIQUE,
    budget VARCHAR(100) DEFAULT NULL,
    timeline VARCHAR(100) DEFAULT NULL,
    decision_maker VARCHAR(255) DEFAULT NULL,
    pain_points TEXT,
    competitors TEXT,
    requirements TEXT,
    filled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES crm_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo scheduling
CREATE TABLE IF NOT EXISTS crm_demos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    demo_link VARCHAR(500) DEFAULT NULL,
    conducted_at DATETIME DEFAULT NULL,
    notes TEXT,
    status VARCHAR(20) DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_status (status),
    FOREIGN KEY (company_id) REFERENCES crm_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE IF NOT EXISTS crm_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'issued',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT NULL,
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    FOREIGN KEY (company_id) REFERENCES crm_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificates
CREATE TABLE IF NOT EXISTS crm_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    certificate_number VARCHAR(100) NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES crm_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Managers/Users table
CREATE TABLE IF NOT EXISTS crm_managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stage transitions log (for audit)
CREATE TABLE IF NOT EXISTS crm_stage_transitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    from_stage VARCHAR(20) NOT NULL,
    to_stage VARCHAR(20) NOT NULL,
    transition_reason TEXT,
    transitioned_by INT DEFAULT NULL,
    transitioned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_from_stage (from_stage),
    INDEX idx_to_stage (to_stage),
    FOREIGN KEY (company_id) REFERENCES crm_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert test data
INSERT INTO crm_managers (username, full_name, email) VALUES
('admin', 'Администратор', 'admin@example.com'),
('manager1', 'Иван Петров', 'ivan@example.com'),
('manager2', 'Мария Сидорова', 'maria@example.com');

INSERT INTO crm_companies (name, inn, contact_person, phone, email, current_stage, assigned_manager) VALUES
('ООО Тест', '1234567890', 'Петр Иванов', '+7 999 123-45-67', 'test@company.ru', 'C0', 1),
('ЗАО Пример', '0987654321', 'Анна Смирнова', '+7 999 765-43-21', 'info@primer.ru', 'C1', 2),
('ИП Демо', '1122334455', 'Сергей Козлов', '+7 999 111-22-33', 'demo@ip.ru', 'W1', 1);

-- Add some events for existing companies
INSERT INTO crm_events (company_id, event_type, event_data, old_stage, new_stage, created_by) VALUES
(2, 'contact_attempt', '{"method": "phone", "result": "answered"}', NULL, NULL, 2),
(2, 'lpr_conversation', '{"comment": "Обсудили потребности", "duration": 15}', 'C0', 'C1', 2),
(3, 'contact_attempt', '{"method": "phone", "result": "answered"}', NULL, NULL, 1),
(3, 'lpr_conversation', '{"comment": "Заинтересованы в продукте", "duration": 20}', 'C0', 'C1', 1),
(3, 'discovery_filled', '{"budget": "500000", "timeline": "Q2 2025"}', 'C1', 'W1', 1);

-- Add discovery data
INSERT INTO crm_discovery (company_id, budget, timeline, decision_maker, pain_points, requirements) VALUES
(3, '500000 руб', 'Q2 2025', 'Сергей Козлов', 'Нет автоматизации процессов', 'Интеграция с 1С');

SELECT 'Database initialized successfully!' as status;
