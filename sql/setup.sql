-- Reservation and Billing System
-- Database Setup Script

CREATE DATABASE IF NOT EXISTS reservation_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE reservation_system;

-- -----------------------------------------------
-- Table: items
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS items (
    item_code        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_description VARCHAR(255)     NOT NULL,
    discounted       TINYINT(1)       NOT NULL DEFAULT 1,
    quantity_by_order INT UNSIGNED    NOT NULL DEFAULT 1,
    price            DECIMAL(15, 2)   NOT NULL DEFAULT 0.00,
    created_at       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Table: reservations
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    reservation_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_number      VARCHAR(100)    NOT NULL,
    expected_payment_date DATE           NOT NULL,
    payment_type         ENUM('CASH','CREDIT') NOT NULL,
    total_items          INT UNSIGNED    NOT NULL DEFAULT 0,
    discount_rate        DECIMAL(5, 2)   NOT NULL DEFAULT 0.00,
    subtotal             DECIMAL(15, 2)  NOT NULL DEFAULT 0.00,
    discount_amount      DECIMAL(15, 2)  NOT NULL DEFAULT 0.00,
    amount_due           DECIMAL(15, 2)  NOT NULL DEFAULT 0.00,
    created_at           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Table: reservation_items
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS reservation_items (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    item_code      INT UNSIGNED NOT NULL,
    quantity       INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price     DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    item_total     DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (item_code)      REFERENCES items(item_code)             ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Sample Data – Items
-- -----------------------------------------------
INSERT INTO items (item_description, discounted, quantity_by_order, price) VALUES
    ('Honda Civic 2009',                   1,  1, 450230.00),
    ('Dining Table',                        1,  5,   1500.00),
    ('Conference Room',                     1,  1,  25000.00),
    ('Dinner Package (50pax)',              1,  1,  35000.00),
    ('Floral Arrangement',                  1, 10,  15000.00),
    ('Wine and Liquor Package (10pax)',     1, 15, 100000.00);
