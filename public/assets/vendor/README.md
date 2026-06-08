# Llibreries vendoritzades (assets locals, sense CDN)

Aquesta carpeta conté les llibreries de front servides **localment**, tal com
exigeix `CLAUDE.md` (sense CDN obligatori, per funcionar en hosting compartit
sense connexió externa).

## Estat actual (Fase 1)

L'entorn de bastida no té accés de xarxa per descarregar les builds oficials,
així que de moment hi ha **substituts/placeholders**:

- `alpine.min.js` → **shim de desenvolupament** (subconjunt d'Alpine 3:
  `x-data`, `x-text`, `x-show`, `@click`). Cal substituir-lo per la build oficial.

## Què cal afegir abans de producció

Descarrega i col·loca aquí les builds oficials (versions fixades):

```bash
# Alpine.js 3 (substitueix el shim)
curl -fsSL https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js \
  -o public/assets/vendor/alpine.min.js

# Chart.js 4 (per als gràfics de la Fase 9)
curl -fsSL https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js \
  -o public/assets/vendor/chart.umd.min.js
```

A la Fase 13 (polit) s'afegirà el pipeline de build de Tailwind CSS per
generar `public/assets/css/app.css` a partir de les classes utilitzades.
Mentrestant s'usa un full d'estils propi i lleuger (`css/app.css`).

> Cap d'aquests fitxers es carrega des d'un CDN en temps d'execució: sempre
> es serveixen des d'aquest directori local.
