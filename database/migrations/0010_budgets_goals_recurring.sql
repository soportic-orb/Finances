-- Fase 8 — Pressupostos, objectius d'estalvi i recurrents/subscripcions.

CREATE TABLE IF NOT EXISTS budgets (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id BIGINT UNSIGNED NOT NULL,
    category_id  BIGINT UNSIGNED NOT NULL,
    period       ENUM('mensual','anual') NOT NULL DEFAULT 'mensual',
    amount       DECIMAL(14,2)   NOT NULL,
    start_on     DATE            NULL,
    rollover     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_budgets_household (household_id),
    CONSTRAINT fk_budgets_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE,
    CONSTRAINT fk_budgets_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goals (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id   BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(191)    NOT NULL,
    target_amount  DECIMAL(14,2)   NOT NULL,
    current_amount DECIMAL(14,2)   NOT NULL DEFAULT 0,
    target_date    DATE            NULL,
    account_id     BIGINT UNSIGNED NULL,
    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_goals_household (household_id),
    CONSTRAINT fk_goals_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE,
    CONSTRAINT fk_goals_account FOREIGN KEY (account_id)
        REFERENCES accounts (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recurring (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id     BIGINT UNSIGNED NOT NULL,
    label            VARCHAR(191)    NOT NULL,
    amount_est       DECIMAL(14,2)   NOT NULL DEFAULT 0,
    cadence          VARCHAR(16)     NOT NULL DEFAULT 'mensual',
    next_expected_on DATE            NULL,
    last_seen_on     DATE            NULL,
    occurrences      INT UNSIGNED    NOT NULL DEFAULT 0,
    is_subscription  TINYINT(1)      NOT NULL DEFAULT 0,
    status           VARCHAR(16)     NOT NULL DEFAULT 'active',
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recurring_household (household_id),
    CONSTRAINT fk_recurring_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
