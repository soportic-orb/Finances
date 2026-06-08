-- Fase 4 — Comptes financers.
--
-- Saldo amb signe: current_balance = opening_balance + SUM(transactions.amount),
-- on amount és l'efecte real sobre el compte (negatiu en despeses/sortides).
-- Les targetes de crèdit tenen saldo negatiu (deute) de forma natural.

CREATE TABLE IF NOT EXISTS accounts (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id    BIGINT UNSIGNED NOT NULL,
    owner_user_id   BIGINT UNSIGNED NULL,
    name            VARCHAR(191)    NOT NULL,
    type            ENUM('corrent','estalvi','efectiu','targeta','inversio') NOT NULL DEFAULT 'corrent',
    currency        CHAR(3)         NOT NULL DEFAULT 'EUR',
    opening_balance DECIMAL(14,2)   NOT NULL DEFAULT 0,
    current_balance DECIMAL(14,2)   NOT NULL DEFAULT 0,
    iban_last4      VARCHAR(4)      NULL,
    source          ENUM('manual','enablebanking') NOT NULL DEFAULT 'manual',
    eb_account_uid  VARCHAR(128)    NULL,
    archived        TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_accounts_household (household_id),
    KEY idx_accounts_owner (owner_user_id),
    CONSTRAINT fk_accounts_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE,
    CONSTRAINT fk_accounts_owner FOREIGN KEY (owner_user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
