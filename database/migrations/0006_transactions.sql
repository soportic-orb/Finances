-- Fase 4 — Moviments (transaccions).
--
-- amount: efecte amb signe sobre el compte (ingrés +, despesa −).
-- Traspassos: dues files amb el mateix transfer_group_id (sortida − i entrada +).
-- Deduplicació (Fase 5+): external_ref (Enable Banking) o dedup_hash.

CREATE TABLE IF NOT EXISTS transactions (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id      BIGINT UNSIGNED NOT NULL,
    account_id        BIGINT UNSIGNED NOT NULL,
    category_id       BIGINT UNSIGNED NULL,
    type              ENUM('income','expense','transfer') NOT NULL DEFAULT 'expense',
    amount            DECIMAL(14,2)   NOT NULL,
    currency          CHAR(3)         NOT NULL DEFAULT 'EUR',
    occurred_on       DATE            NOT NULL,
    value_date        DATE            NULL,
    description       VARCHAR(255)    NULL,
    merchant          VARCHAR(191)    NULL,
    counterparty      VARCHAR(191)    NULL,
    notes             TEXT            NULL,
    transfer_group_id CHAR(36)        NULL,
    source            ENUM('manual','enablebanking','import') NOT NULL DEFAULT 'manual',
    external_ref      VARCHAR(191)    NULL,
    dedup_hash        CHAR(64)        NULL,
    ai_categorized    TINYINT(1)      NOT NULL DEFAULT 0,
    created_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tx_household (household_id),
    KEY idx_tx_account (account_id),
    KEY idx_tx_category (category_id),
    KEY idx_tx_occurred (occurred_on),
    KEY idx_tx_transfer (transfer_group_id),
    UNIQUE KEY uq_tx_external (account_id, external_ref),
    CONSTRAINT fk_tx_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE,
    CONSTRAINT fk_tx_account FOREIGN KEY (account_id)
        REFERENCES accounts (id) ON DELETE CASCADE,
    CONSTRAINT fk_tx_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
