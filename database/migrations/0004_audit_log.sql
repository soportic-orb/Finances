-- Fase 3 — Registre d'auditoria.
--
-- Accions rellevants de la llar (login, gestió de membres, 2FA, settings,
-- i més endavant vincles i sincronitzacions bancàries).

CREATE TABLE IF NOT EXISTS audit_log (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id BIGINT UNSIGNED NULL,
    user_id      BIGINT UNSIGNED NULL,
    action       VARCHAR(64)     NOT NULL,
    entity       VARCHAR(64)     NULL,
    entity_id    BIGINT UNSIGNED NULL,
    ip           VARCHAR(45)     NULL,
    meta         VARCHAR(255)    NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_household (household_id),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
