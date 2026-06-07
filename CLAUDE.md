# CLAUDE.md

Context permanent per a Claude Code. **Llegeix aquest fitxer abans de cada tasca.** Manté'l actualitzat quan canviïn decisions d'arquitectura.

---

## Què és això

Aplicació web **privada** de **finances personals i familiars** (una llar amb diversos membres), autoallotjada. Connexió bancària en viu via **Enable Banking (PSD2)** i capa d'anàlisi amb l'**API de Claude**. Autoinstal·lable i autoactualitzable des de GitHub.

**No** és open-source ni multi-tenant públic: és per a mi i la meva família.

---

## Mètode de treball (obligatori)

- Treballa **fase a fase** segons `PLAN.md`. **Atura't al final de cada fase** i espera la meva validació abans de continuar.
- Abans d'escriure codi de la Fase 1, presenta `PLAN.md` i aquest `CLAUDE.md` per validar.
- Cada fase ha de deixar l'aplicació en estat funcional i verificable.
- Mantén `VERSION` (semver) i el registre de migracions sempre coherents.

---

## Stack i restriccions

- **PHP 8.2+**, **MVC lleuger propi** (router + controllers + models + views). **Sense Laravel/Symfony.**
- **MySQL 8.0+** / MariaDB 10.6+. Accés **només via PDO amb sentències preparades**.
- Front: **Tailwind CSS** + **Alpine.js**, **assets servits localment** (sense CDN obligatori).
- Gràfics: llibreria lleugera autoallotjada (Chart.js o ApexCharts).
- Composer mínim: HTTP client, JWT RS256 (`firebase/php-jwt`), PHPMailer, dompdf.
- Ha de funcionar en **VPS i hosting compartit amb Git** (`git pull`).

---

## Estructura de directoris

```
/public            → document root (index.php, assets)
/app/Controllers /app/Models /app/Services /app/Views /app/Support
/config            → config.php (fora del webroot)
  /keys            → claus privades (.pem Enable Banking), 0600, MAI a Git
/database/migrations /database/seeds
/storage           → logs, cache, exports, backups (fora del webroot)
/install           → assistent web (es bloqueja en acabar)
/bin               → CLI: update.php, migrate.php, eb-sync.php, cron
VERSION  CLAUDE.md  PLAN.md
```

Serveis clau: `AiService`, `EnableBankingService`, `ImportService`, `BudgetService`, `UpdateService`, `BackupService`.

---

## Model de dades — principis

- **Tot** porta `household_id`. On té sentit el responsable, afegeix `owner_user_id` (p. ex. `accounts`).
- Taules nucli: `households`, `users` (role owner/member), `settings`, `api_credentials`, `audit_log`.
- Domini: `accounts`, `categories`, `transactions`, `rules`, `budgets`, `goals`, `recurring`.
- Enable Banking: `eb_authorizations`, `eb_sessions`, `eb_account_links`, `eb_sync_log`.
- IA: `ai_jobs`, `ai_insights`.
- **Deduplicació d'ingesta:** prioritza `transactions.external_ref` (ref. Enable Banking); si no, `dedup_hash` (compte+data+import+descripció normalitzada).

---

## Enable Banking (PSD2)

- Base URL `https://api.enablebanking.com`. **Entorn (sandbox/production) configurable.**
- Credencials: `application_id` (UUID) + clau privada **RSA `.pem`**.
  - El `.pem` va a `/config/keys/<application_id>.pem`, **0600**, fora del webroot, **exclòs de Git**.
  - `application_id`, entorn, `redirect_url` i PSU headers opcionals a `settings`/`api_credentials`.
- **JWT RS256** a cada petició (`Authorization: Bearer <JWT>`), cachejat ≈1 h:
  - Header `{ typ: JWT, alg: RS256, kid: <application_id> }`.
  - Body `{ iss: "enablebanking.com", aud: "api.enablebanking.com", iat, exp: iat+3600 }`.
- Flux: `GET /aspsps?country=ES` → `POST /auth` (redirigeix a la URL de consentiment) → callback amb `code` (valida `state`) → `POST /sessions` (persisteix **immediatament**, alguns camps només es mostren un cop) → `GET /accounts/{uid}/balances` i `/transactions` (paginació per `continuation_key`).
- **Consentiment limitat (~90 dies):** desa `valid_until`, mostra estat, avisa abans de caducar, gestiona re-autorització (SCA). Cron `eb-sync.php`.
- Gestiona `422 PSU_HEADER_NOT_PROVIDED` (afegeix PSU headers configurables) i `429` (backoff). No registris secrets ni dades sensibles als logs.
- **Iniciació de pagaments: fora d'abast** tret que es demani explícitament.

---

## Capa d'IA (Claude)

- Endpoint `POST https://api.anthropic.com/v1/messages`; headers `x-api-key`, `anthropic-version: 2023-06-01`. Tot centralitzat a `AiService` amb reintents, timeout i registre a `ai_jobs`.
- Clau d'API **xifrada en repòs (AES-256-GCM)** amb `APP_KEY`.
- **Model per tasca, configurable** (no codificat a pèl). Per defecte: `claude-haiku-4-5-20251001` (categorització/anomalies), `claude-sonnet-4-6` (anàlisi/recomanacions/xat), `claude-opus-4-8` (anàlisis complexes).
- Sortides estructurades: demana **JSON estricte** al system prompt i parseja amb seguretat.
- **Minimització de dades (obligatori):** mai IBANs/targetes/noms reals cap a l'API; envia agregats categoritzats; **opt-in per funció**; desa `payload_summary` del que s'envia. Les recomanacions són orientatives.

---

## Actualitzacions OTA (com SysRevAI)

`UpdateService` + `bin/update.php`: mode manteniment → **backup de BD i `/config` (inclou `/config/keys`)** → `git fetch` + `git reset --hard origin/<branch>` (o tag) → `composer install --no-dev` → migracions pendents → assets → **rollback automàtic** si falla → treu manteniment. Mai ha de trencar la instància ni esborrar `/config/keys`.

---

## Seguretat (no negociable)

- PDO preparat; escapar tota sortida HTML; **CSRF** a tots els formularis; headers de seguretat (CSP, X-Frame-Options…).
- `password_hash` (argon2/bcrypt), límit d'intents, sessions segures, cookies `HttpOnly`/`Secure`/`SameSite`. **2FA TOTP** opcional.
- Secrets xifrats (AES-256-GCM); `.pem` en fitxer 0600 fora del webroot.
- `.gitignore` exclou `/config`, `/config/keys`, `/storage`, `*.pem`, `*.env`.
- `audit_log` de les accions rellevants (inclosos vincles i sincronitzacions bancàries).
- Backups programables + retenció configurable.

---

## Idioma

- UI **català per defecte** + castellà. Tot via sistema i18n des del principi (no textos a pèl al codi).
