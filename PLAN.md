# PLAN.md

Pla de desenvolupament per fases. **Una fase a la vegada; aturada per validar al final de cadascuna.**
Marca l'estat: ⬜ pendent · 🔄 en curs · ✅ validada.

> Abans de la Fase 1: presentar `PLAN.md` i `CLAUDE.md` per validar. Cap línia de codi fins llavors.

---

## Fase 1 — Bastida ✅
**Objectiu:** esquelet tècnic funcional.
- Estructura MVC (router, controllers, models, views), capa `Support` (DB/PDO, Csrf, Crypto, Auth, Validator).
- Sistema de migracions versionades + `VERSION` (semver).
- Layout base Tailwind + Alpine, assets locals.
- Càrrega de `config.php` i `APP_KEY`.

**Acceptació:** una ruta de prova respon; una migració d'exemple s'aplica i es registra; assets carreguen sense CDN.

---

## Fase 2 — Instal·lador web ✅
**Objectiu:** instal·lació guiada i aïllada.
- Passos: requisits → BD + prova → migracions/seeds → llar + propietari → dades bàsiques (moneda, zona, idioma=ca) → generació `config.php`/`APP_KEY` → finalització.
- Seeds de categories per defecte en català.
- Bloqueig/eliminació de `/install` en acabar.

**Acceptació:** instal·lació completa des de zero en VPS i en hosting compartit; `/install` queda inaccessible després.

---

## Fase 3 — Auth + llar multi-usuari + panell ✅
**Objectiu:** accés segur i gestió de la llar.
- Login, sessions segures, límit d'intents; **2FA TOTP opcional**.
- Rols owner/member; alta/gestió de membres per l'owner.
- Panell base + `settings` + `audit_log`.

**Acceptació:** owner crea un membre; membre entra i veu només la seva llar; accions queden a l'audit log; 2FA activable.

---

## Fase 4 — Comptes + transaccions ✅
**Objectiu:** nucli financer manual.
- CRUD de comptes (tipus, divisa, propietari/membre, arxivat), saldo recalculat.
- CRUD de transaccions, traspassos (`transfer_group_id`), filtres (dates/categoria/compte/membre/text/import), notes/etiquetes.
- Vista de patrimoni net de la llar.

**Acceptació:** alta de comptes i moviments quadra saldos; traspàs entre comptes és coherent; filtres funcionen.

---

## Fase 5 — Integració Enable Banking ✅
**Objectiu:** connexió bancària en viu (nucli).
- `EnableBankingService`: JWT RS256 (kid=application_id), `.pem` a `/config/keys` 0600, entorn configurable, cache de token.
- `GET /aspsps?country=ES`; flux `POST /auth` → callback (`code`/`state`) → `POST /sessions`; persistència immediata (`eb_*`).
- Enllaç de comptes EB ↔ locals; sincronització de saldos i transaccions amb paginació `continuation_key`; **dedup per `external_ref`**.
- Gestió de consentiment/expiració (~90 dies) + avisos; maneig de `422 PSU_HEADER_NOT_PROVIDED` i `429` (backoff).
- Cron `bin/eb-sync.php`.

**Acceptació:** vinculo un banc real (o sandbox), s'importen comptes i moviments sense duplicats, i una segona sincronització només afegeix els nous; consentiment proper a caducar genera avís.

---

## Fase 6 — Categories + regles ✅
**Objectiu:** classificació determinista.
- Categories jeràrquiques editables; `rules` (conté/regex/exacte) per descripció/comerç/contrapart/import, amb prioritat.
- Aplicació de regles en ingesta i sota demanda; recategorització massiva.

**Acceptació:** una regla nova recategoritza els moviments coincidents (existents i futurs).

---

## Fase 7 — Importació de fitxers ✅
**Objectiu:** històric i bancs no coberts.
- Parser **Norma 43 (Quadern 43 AEB/CSB)** prioritari.
- **CSV** amb mapatge de columnes configurable, desat com a plantilla per banc; previsualització + dedup.
- (Opcional) OFX/QIF.

**Acceptació:** importo un Norma 43 i un CSV; deduplica contra moviments ja existents d'Enable Banking.

---

## Fase 8 — Pressupostos + objectius + recurrents ✅
**Objectiu:** eines de control i estalvi.
- Pressupostos per categoria/període amb alertes (80%/100%) i rollover.
- Objectius d'estalvi amb progrés i quota mensual estimada.
- Detecció de recurrents/subscripcions + alerta d'inactives.

**Acceptació:** un pressupost mostra progrés i avisa en superar llindar; es detecta una subscripció mensual recurrent.

---

## Fase 9 — Dashboard + informes ✅
**Objectiu:** visió i exportació. *(Gràfics SVG autoallotjats al servidor.)*
- Resum mensual (ingressos/despeses, taxa d'estalvi, top categories, per membre).
- Gràfics: donut per categoria, evolució mensual, patrimoni net en el temps.
- Exportació CSV/Excel + informe PDF mensual.

**Acceptació:** el dashboard reflecteix dades reals; exporta CSV i PDF correctes.

---

## Fase 10 — Capa d'IA ✅
**Objectiu:** anàlisi i assistència amb Claude.
- `AiService` (clau xifrada, model per tasca, reintents, `ai_jobs`).
- Categorització IA (JSON estricte) com a complement de regles; anàlisi mensual a `ai_insights`; recomanacions d'estalvi; detecció d'anomalies; **xat en llenguatge natural**.
- **Minimització de dades** + opt-in per funció + `payload_summary`.

**Acceptació:** suggeriments de categoria confirmables; anàlisi mensual amb recomanacions; el xat respon una pregunta agregada; cap dada sensible (IBAN/nom) surt cap a l'API.

---

## Fase 11 — Actualitzacions OTA via Git ✅
**Objectiu:** autoactualització segura (com SysRevAI).
- `UpdateService` + `bin/update.php`: manteniment → backup BD i `/config` (inclou keys) → `git fetch`/`reset` → composer → migracions → assets → **rollback automàtic**.
- Botó al panell + cron.

**Acceptació:** una actualització simulada s'aplica i puja `VERSION`; una fallada provoca rollback i deixa la instància intacta.

---

## Fase 12 — Migració de servidor 🔄
**Objectiu:** portabilitat. *(Implementada; pendent de validació.)*
- Export BD + fitxers (inclou `/config/keys`) en **ZIP xifrat** amb manifest + checksums; import des de l'instal·lador.

**Acceptació:** export en un servidor i import net en un altre reprodueix la instància (comptes, vincles EB, settings).

---

## Fase 13 — Polit ⬜
**Objectiu:** acabats.
- i18n complet (ca per defecte + es), accessibilitat.
- README intern (privat, sense orientació open-source), avisos de privacitat de la llar.
- Revisió de seguretat i de `.gitignore` (secrets/keys exclosos).

**Acceptació:** UI traduïda, sense textos a pèl; cap secret al repositori.
