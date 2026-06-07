-- Fase 5 — Enable Banking (PSD2).
--
-- Persistència del flux d'autorització i sincronització. Alguns camps de la
-- resposta de /sessions només es mostren un cop: s'han de desar immediatament.

CREATE TABLE IF NOT EXISTS eb_authorizations (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id        BIGINT UNSIGNED NOT NULL,
    initiated_by_user_id BIGINT UNSIGNED NULL,
    aspsp_name          VARCHAR(128)    NOT NULL,
    aspsp_country       CHAR(2)         NOT NULL DEFAULT 'ES',
    psu_type            VARCHAR(16)     NOT NULL DEFAULT 'personal',
    state               CHAR(36)        NOT NULL,
    authorization_id    VARCHAR(191)    NULL,
    status              VARCHAR(32)      NOT NULL DEFAULT 'pending',
    valid_until         DATETIME        NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_eb_auth_state (state),
    KEY idx_eb_auth_household (household_id),
    CONSTRAINT fk_eb_auth_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eb_sessions (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    authorization_id BIGINT UNSIGNED NOT NULL,
    session_id       VARCHAR(191)    NOT NULL,
    status           VARCHAR(32)     NOT NULL DEFAULT 'AUTHORIZED',
    valid_until      DATETIME        NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_eb_sessions_auth (authorization_id),
    CONSTRAINT fk_eb_sessions_auth FOREIGN KEY (authorization_id)
        REFERENCES eb_authorizations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eb_account_links (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id            BIGINT UNSIGNED NOT NULL,
    session_id            BIGINT UNSIGNED NOT NULL,
    eb_account_uid        VARCHAR(191)    NOT NULL,
    iban_hash             CHAR(64)        NULL,
    last_synced_at        DATETIME        NULL,
    last_continuation_key VARCHAR(255)    NULL,
    created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_eb_link_account (account_id),
    KEY idx_eb_link_session (session_id),
    CONSTRAINT fk_eb_link_account FOREIGN KEY (account_id)
        REFERENCES accounts (id) ON DELETE CASCADE,
    CONSTRAINT fk_eb_link_session FOREIGN KEY (session_id)
        REFERENCES eb_sessions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eb_sync_log (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    eb_account_link_id BIGINT UNSIGNED NOT NULL,
    started_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at       DATETIME        NULL,
    transactions_new  INT UNSIGNED    NOT NULL DEFAULT 0,
    transactions_dup  INT UNSIGNED    NOT NULL DEFAULT 0,
    status            VARCHAR(32)     NOT NULL DEFAULT 'running',
    error             VARCHAR(500)    NULL,
    PRIMARY KEY (id),
    KEY idx_eb_synclog_link (eb_account_link_id),
    CONSTRAINT fk_eb_synclog_link FOREIGN KEY (eb_account_link_id)
        REFERENCES eb_account_links (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
