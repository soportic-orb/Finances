-- Fase 10 — Capa d'IA (API de Claude).
--
-- api_credentials: claus xifrades en repòs (AES-256-GCM amb APP_KEY).
-- ai_jobs: registre de cada crida (tokens, estat, resum del que s'ha enviat).
-- ai_insights: anàlisis mensuals desades.

CREATE TABLE IF NOT EXISTS api_credentials (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id     BIGINT UNSIGNED NOT NULL,
    provider         VARCHAR(32)     NOT NULL,
    secret_encrypted TEXT            NOT NULL,
    meta             TEXT            NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_apicred (household_id, provider),
    CONSTRAINT fk_apicred_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_jobs (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id    BIGINT UNSIGNED NOT NULL,
    type            VARCHAR(32)     NOT NULL,
    model           VARCHAR(64)     NOT NULL,
    tokens_in       INT UNSIGNED    NOT NULL DEFAULT 0,
    tokens_out      INT UNSIGNED    NOT NULL DEFAULT 0,
    status          VARCHAR(16)     NOT NULL DEFAULT 'ok',
    payload_summary VARCHAR(500)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_aijobs_household (household_id),
    KEY idx_aijobs_created (created_at),
    CONSTRAINT fk_aijobs_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_insights (
    id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id         BIGINT UNSIGNED NOT NULL,
    period               VARCHAR(7)      NOT NULL,
    summary              TEXT            NULL,
    recommendations_json TEXT            NULL,
    created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_aiinsights_household (household_id),
    CONSTRAINT fk_aiinsights_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
