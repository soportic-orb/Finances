-- Migració inicial (Fase 1 — Bastida).
--
-- Crea la taula `settings` (clau/valor, per llar i global), que serveix tant
-- de fonament per a la configuració en BD com de migració d'exemple verificable.
-- L'esquema de domini complet (households, users, accounts, transactions…)
-- s'afegirà a les fases corresponents.
--
-- Idempotent: usa IF NOT EXISTS perquè re-executar sigui segur.

CREATE TABLE IF NOT EXISTS settings (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id BIGINT UNSIGNED NULL,
    `key`        VARCHAR(191)    NOT NULL,
    `value`      TEXT            NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_scope (household_id, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
