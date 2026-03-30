-- ==========================================================================
-- UWA License Server – Database Schema
-- ==========================================================================
-- WordPress table prefix is assumed to be "wp_". Replace with your actual
-- prefix if different. The plugin creates these tables automatically via
-- dbDelta() on activation; this file is provided for reference and manual
-- inspection only.
--
-- Tables:
--   wp_uls_licenses     – One row per purchase code (customer license)
--   wp_uls_activations  – One row per domain activation
--   wp_uls_logs         – API request / response audit log
-- ==========================================================================


-- --------------------------------------------------------------------------
-- Table: wp_uls_licenses
-- One row per CodeCanyon purchase code. The purchase_code is the UUID that
-- the customer receives at checkout and enters into the plugin on their site.
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wp_uls_licenses` (
  `id`              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT     COMMENT 'Internal primary key',
  `purchase_code`   VARCHAR(255)        NOT NULL                    COMMENT 'Envato purchase code UUID (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)',
  `buyer_name`      VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'Envato buyer username or full name',
  `buyer_email`     VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'Buyer email address (may be empty if not returned by Envato API)',
  `product_id`      VARCHAR(50)         NOT NULL DEFAULT ''         COMMENT 'CodeCanyon item ID (numeric string)',
  `license_type`    ENUM('regular','extended') NOT NULL DEFAULT 'regular'
                                                                    COMMENT 'Regular = 1 domain, Extended = unlimited domains',
  `max_domains`     INT(11)             NOT NULL DEFAULT 1          COMMENT 'Maximum number of simultaneously active domain activations',
  `status`          ENUM('active','inactive','expired','banned') NOT NULL DEFAULT 'active'
                                                                    COMMENT 'Current license status. Banned licenses are blocked from activation.',
  `support_until`   DATE                NULL DEFAULT NULL           COMMENT 'Envato support entitlement expiry date (Y-m-d)',
  `purchase_date`   DATE                NULL DEFAULT NULL           COMMENT 'Original Envato purchase date (Y-m-d)',
  `envato_verified` TINYINT(1)          NOT NULL DEFAULT 0          COMMENT '1 = license was verified with the Envato API at creation time',
  `notes`           TEXT                NULL DEFAULT NULL           COMMENT 'Internal admin notes about this license',
  `created_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                                    COMMENT 'Record creation timestamp (UTC)',
  `updated_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                                                                    COMMENT 'Record last-update timestamp (UTC)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_code` (`purchase_code`),
  KEY `status`      (`status`),
  KEY `buyer_email` (`buyer_email`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='License records – one row per CodeCanyon purchase code';


-- --------------------------------------------------------------------------
-- Table: wp_uls_activations
-- One row per activation event. A license can have multiple activation
-- rows over its lifetime (active and deactivated). The plugin checks the
-- count of rows with status = 'active' against max_domains.
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wp_uls_activations` (
  `id`                 BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT  COMMENT 'Internal primary key',
  `license_id`         BIGINT(20) UNSIGNED NOT NULL                 COMMENT 'Foreign key → wp_uls_licenses.id',
  `domain`             VARCHAR(500)        NOT NULL DEFAULT ''      COMMENT 'Sanitised domain (no protocol, no www, no trailing slash)',
  `site_url`           VARCHAR(500)        NOT NULL DEFAULT ''      COMMENT 'Full site URL as reported by the client plugin',
  `activation_date`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                                    COMMENT 'When the activation was created (UTC)',
  `deactivation_date`  DATETIME            NULL DEFAULT NULL        COMMENT 'When the activation was revoked (UTC); NULL if still active',
  `ip_address`         VARCHAR(45)         NOT NULL DEFAULT ''      COMMENT 'Client IP at time of activation (IPv4 or IPv6)',
  `wp_version`         VARCHAR(20)         NOT NULL DEFAULT ''      COMMENT 'WordPress version reported by the client at activation time',
  `plugin_version`     VARCHAR(20)         NOT NULL DEFAULT ''      COMMENT 'UWA plugin version reported by the client at activation time',
  `status`             ENUM('active','inactive') NOT NULL DEFAULT 'active'
                                                                    COMMENT 'active = currently counts toward max_domains; inactive = deactivated',
  PRIMARY KEY (`id`),
  KEY `license_id` (`license_id`),
  KEY `domain`     (`domain`(191)),
  KEY `status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Domain activation records – one row per activation event';


-- --------------------------------------------------------------------------
-- Table: wp_uls_logs
-- Append-only audit log. Every API request (activate, deactivate, validate)
-- writes a row here with the sanitised request payload and server response.
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wp_uls_logs` (
  `id`             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT  COMMENT 'Internal primary key',
  `license_id`     BIGINT(20) UNSIGNED NULL DEFAULT NULL        COMMENT 'Related license (NULL if license was not found)',
  `action`         VARCHAR(50)         NOT NULL DEFAULT ''      COMMENT 'Action type: activate | deactivate | validate | activate_failed | etc.',
  `domain`         VARCHAR(500)        NOT NULL DEFAULT ''      COMMENT 'Domain involved in the request',
  `ip_address`     VARCHAR(45)         NOT NULL DEFAULT ''      COMMENT 'Client IP address',
  `request_data`   LONGTEXT            NULL DEFAULT NULL        COMMENT 'JSON-encoded sanitised request parameters',
  `response_data`  LONGTEXT            NULL DEFAULT NULL        COMMENT 'JSON-encoded server response payload',
  `created_at`     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                                COMMENT 'Log entry timestamp (UTC)',
  PRIMARY KEY (`id`),
  KEY `license_id` (`license_id`),
  KEY `action`     (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='API request / response audit log';


-- --------------------------------------------------------------------------
-- Sample data (for testing – safe to delete in production)
-- --------------------------------------------------------------------------

-- A sample "test" license. The all-zeros UUID will never be a real Envato
-- purchase code, so it is safe to use for manual QA and integration tests.

INSERT INTO `wp_uls_licenses`
  (`purchase_code`,                        `buyer_name`,  `buyer_email`,            `product_id`, `license_type`, `max_domains`, `status`, `support_until`, `purchase_date`, `envato_verified`, `notes`,                       `created_at`,          `updated_at`)
VALUES
  ('00000000-0000-0000-0000-000000000000', 'Test Buyer',  'test@example.com',       '12345678',   'regular',      1,             'active', '2026-12-31',    '2024-01-01',    0,                 'Sample license for QA testing.', NOW(),                NOW());

-- Corresponding sample activation (domain: localhost).
-- This will only insert if the licenses row above was inserted.

INSERT INTO `wp_uls_activations`
  (`license_id`, `domain`,    `site_url`,               `activation_date`, `ip_address`, `wp_version`, `plugin_version`, `status`)
SELECT
  id, 'localhost', 'http://localhost', NOW(),            '127.0.0.1',  '6.5',        '1.0.0',          'active'
FROM `wp_uls_licenses`
WHERE `purchase_code` = '00000000-0000-0000-0000-000000000000'
LIMIT 1;

-- Log entry for the sample activation.

INSERT INTO `wp_uls_logs`
  (`license_id`, `action`,   `domain`,    `ip_address`, `request_data`,                                                                          `response_data`,                      `created_at`)
SELECT
  id,            'activate', 'localhost', '127.0.0.1',
  '{"purchase_code":"00000000-0000-0000-0000-000000000000","domain":"localhost"}',
  '{"success":true,"status":"activated","message":"License activated successfully."}',
  NOW()
FROM `wp_uls_licenses`
WHERE `purchase_code` = '00000000-0000-0000-0000-000000000000'
LIMIT 1;
