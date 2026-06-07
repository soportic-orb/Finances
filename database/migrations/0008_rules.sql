-- Fase 6 — Regles de categorització determinista.
--
-- S'apliquen en ingesta i sota demanda. Ordenades per priority (asc) i id.

CREATE TABLE IF NOT EXISTS rules (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id    BIGINT UNSIGNED NOT NULL,
    match_type      ENUM('conte','regex','exacte') NOT NULL DEFAULT 'conte',
    pattern         VARCHAR(255)    NOT NULL,
    field           ENUM('description','merchant','counterparty','amount') NOT NULL DEFAULT 'description',
    set_category_id BIGINT UNSIGNED NOT NULL,
    priority        INT             NOT NULL DEFAULT 100,
    enabled         TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rules_household (household_id),
    CONSTRAINT fk_rules_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE,
    CONSTRAINT fk_rules_category FOREIGN KEY (set_category_id)
        REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
