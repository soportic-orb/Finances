-- Fase 2 — Categories jeràrquiques per llar.
--
-- Les categories per defecte (en català) les insereix l'instal·lador a partir
-- de /database/seeds/categories_ca.php per a la llar creada.

CREATE TABLE IF NOT EXISTS categories (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_id BIGINT UNSIGNED NOT NULL,
    parent_id    BIGINT UNSIGNED NULL,
    name         VARCHAR(191)    NOT NULL,
    kind         ENUM('ingres','despesa','traspas') NOT NULL DEFAULT 'despesa',
    icon         VARCHAR(32)     NULL,
    color        CHAR(7)         NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_categories_household (household_id),
    KEY idx_categories_parent (parent_id),
    CONSTRAINT fk_categories_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE CASCADE,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id)
        REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
