# OnlyFix – Wireframe vázrajzok

## Mi ez a mappa?

Az OnlyFix autószerviz-kezelő alkalmazás wireframe vázrajzai, amelyek a felhasználói felület tervezésének dokumentálására készültek. A wireframe-ek az alkalmazás tényleges oldalai alapján lettek megírva, tükrözve a valós mezőneveket, gombokat, táblázat oszlopokat és az egyes szerepkörök (admin, felhasználó, szerelő) szerinti eltérő navigációt és funkciókat.

## Eszköz

- **WireMD** v0.1.5 – Markdown-alapú wireframe generátor (`npx wiremd`)
- **Puppeteer** – HTML → PNG konverzió
- **Stílus:** sketch (Balsamiq-féle kézrajzolt megjelenés)

## Készítés dátuma

2026-03-30 – A `feat-wireframe` branch részeként készült a fejlesztési dokumentáció kiegészítéseként.

## Fájlok listája

| Forrásfájl | PNG export | Oldal | Szerepkör |
|------------|------------|-------|-----------|
| `src/01-login.md` | `export/01-login.png` | Bejelentkezés | Mindenki |
| `src/02-register.md` | `export/02-register.png` | Regisztráció | Mindenki |
| `src/03-admin-dashboard.md` | `export/03-admin-dashboard.png` | Admin kezelőfelület | Admin |
| `src/04-user-dashboard.md` | `export/04-user-dashboard.png` | Felhasználói kezelőfelület | Felhasználó |
| `src/05-mechanic-dashboard.md` | `export/05-mechanic-dashboard.png` | Szerelő kezelőfelület | Szerelő |
| `src/06-ticket-create.md` | `export/06-ticket-create.png` | Jegy létrehozása | Felhasználó |
| `src/07-ticket-work.md` | `export/07-ticket-work.png` | Jegyen való munka | Szerelő |

## Hogyan lehet módosítani?

1. Szerkeszd a kívánt `.md` fájlt a `src/` mappában a WireMD szintaxis szerint
2. Futtasd az alábbi export parancsot az összes PNG újragenerálásához

## Export parancs

```bash
# 1. HTML generálás WireMD-vel
for file in wireframes/src/*.md; do
    filename=$(basename "$file" .md)
    npx wiremd "$file" -o "wireframes/export/${filename}.html" --style sketch
done

# 2. HTML → PNG konverzió (Puppeteer szükséges)
# Telepítés: npm install puppeteer (egy temp könyvtárban, ha globálisan nem elérhető)
node html2png.js

# 3. Köztes HTML fájlok törlése
rm wireframes/export/*.html
```

## WireMD szintaxis gyorsreferencia

```markdown
# Cím                           – Fő címsor
## Alcím {.grid-3}              – 3 oszlopos rács
::: card ... :::                – Kártya konténer
::: sidebar ... :::             – Oldalsáv
[[ Link1 | Link2 ]]{.nav}      – Navigációs sáv
[Gomb]*                         – Elsődleges gomb
[szöveg___________]             – Szöveg beviteli mező
[*****************]             – Jelszó mező
[Text...]{rows:5}               – Többsoros beviteli mező
- [x] Jelölőnégyzet             – Bejelölt jelölőnégyzet
(•) Kiválasztva                  – Rádió gomb
| Oszlop1 | Oszlop2 |           – Táblázat
`Badge szöveg`                   – Jelvény/címke
```
