-- Fase 7 — Plantilles d'importació CSV per banc.
--
-- Desa el mapatge de columnes (JSON) per reutilitzar-lo en futures importacions.

CREATE TABLE IF NOT EXISTS import_templates (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id BIGINT UNSIGNED NOT NULL,
    name         VARCHAR(128)    NOT NULL,
    config_json  TEXT            NOT NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_imptpl_household (household_id),
    CONSTRAINT fk_imptpl_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
