# Finances — aplicació privada de finances familiars

> **Privat.** Aquest projecte és per a ús personal i familiar (una llar amb
> diversos membres). **No** és open-source ni multi-tenant públic. No el
> distribueixis ni el despleguis com a servei per a tercers.

Aplicació web autoallotjada de finances personals i familiars: registre
d'ingressos/despeses/traspassos, anàlisi, pressupostos i objectius, connexió
bancària en viu via **Enable Banking (PSD2)** i una capa d'anàlisi amb l'**API
de Claude** (opt-in, amb minimització de dades). Autoinstal·lable i
autoactualitzable via Git.

## Stack

- PHP 8.2+ amb MVC lleuger propi (router + controllers + models + views), sense frameworks.
- MySQL 8.0+ / MariaDB 10.6+ (accés només via PDO preparat).
- Front: CSS i JS servits **localment** (sense CDN). Gràfics SVG generats al servidor.
- Composer mínim (JWT, PHPMailer, dompdf); l'app funciona sense `composer install` gràcies a un autoloader propi (dompdf és opcional per al PDF natiu).

## Requisits

PHP 8.2+ amb `pdo_mysql`, `mbstring`, `openssl`, `curl`, `zip`, `json`.
Permisos d'escriptura a `/config`, `/config/keys` i `/storage`.

## Instal·lació

1. Clona el repositori i apunta el document root del servidor a `/public`.
2. Visita `/install/` i segueix l'assistent: requisits → BD → llar + propietari
   → dades bàsiques. Genera `config.php` i `APP_KEY`, executa migracions i seeds.
   - Alternativa: a l'últim pas pots **restaurar des d'un paquet de migració**
     d'un altre servidor.
3. L'instal·lador queda bloquejat en acabar (`/install/.lock`).

CLI útils:

```bash
php bin/migrate.php            # aplica migracions pendents
php bin/eb-sync.php            # sincronitza comptes d'Enable Banking (cron)
php bin/update.php             # actualització OTA (cron); --check / --force
```

## Configuració (panell)

- **Enable Banking** (Banc): `application_id`, entorn, `redirect_url`, PSU
  headers i pujada de la clau privada `.pem` (es desa a `/config/keys`, 0600).
- **IA** (IA → Configuració): clau d'Anthropic (xifrada), model per tasca,
  opt-in per funció i límit de tokens mensual.
- **Branca d'actualització**: opcional a `config.php` (`app.update_branch`).

## Cron recomanat

```cron
*/30 * * * *  php /ruta/bin/eb-sync.php   >> /ruta/storage/logs/cron.log 2>&1
0 4 * * *     php /ruta/bin/update.php    >> /ruta/storage/logs/cron.log 2>&1
```

## Seguretat

- PDO preparat a tot arreu; sortida HTML escapada; CSRF a tots els POST.
- Capçaleres de seguretat (CSP, X-Frame-Options, nosniff…); cookies
  `HttpOnly`/`Secure`/`SameSite`; 2FA TOTP opcional; límit d'intents de login.
- Secrets xifrats en repòs (AES-256-GCM amb `APP_KEY`); `.pem` 0600 fora del webroot.
- `.gitignore` exclou `/config`, `/config/keys`, `/storage`, `*.pem`, `*.env`.
- `audit_log` de les accions rellevants.

## Privadesa

Les dades es queden al teu servidor. La capa d'IA mai rep IBANs, targetes ni
noms reals: només **agregats**, i només si actives la funció (opt-in). Les
recomanacions de la IA són orientatives.

## Còpies i migració

- Backups de BD i `/config` (inclou `/config/keys`) des de **Sistema**.
- Actualitzacions OTA amb mode manteniment i **rollback automàtic**.
- **Migració de servidor**: exporta un paquet `.fin` xifrat (BD + claus +
  APP_KEY, amb checksums) i restaura'l en un altre servidor o des de l'instal·lador.

## Estructura

```
/public            document root (index.php, assets, install/)
/app               Controllers, Models, Services, Views, Support
/config            config.php (fora de Git) + /keys (.pem, fora de Git)
/database          migrations, seeds
/storage           logs, cache, exports, backups (fora de Git)
/install           lògica de l'instal·lador (aïllada)
/bin               CLI: migrate, eb-sync, update
VERSION  CLAUDE.md  PLAN.md
```

Vegeu `CLAUDE.md` (decisions d'arquitectura) i `PLAN.md` (fases i acceptació).
