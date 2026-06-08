-- Fase 2 — Nucli d'usuaris i llar.
--
-- `households` (la llar familiar) i `users` (propietari/membres). Tot el domini
-- penjarà de household_id. Idempotent amb IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS households (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(191)    NOT NULL,
    base_currency CHAR(3)         NOT NULL DEFAULT 'EUR',
    timezone      VARCHAR(64)     NOT NULL DEFAULT 'Europe/Madrid',
    locale        VARCHAR(5)      NOT NULL DEFAULT 'ca',
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id  BIGINT UNSIGNED NOT NULL,
    email         VARCHAR(191)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    name          VARCHAR(191)    NOT NULL,
    role          ENUM('owner','member') NOT NULL DEFAULT 'member',
    totp_secret   VARCHAR(64)     NULL,
    failed_logins SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until  DATETIME        NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_household (household_id),
    CONSTRAINT fk_users_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
