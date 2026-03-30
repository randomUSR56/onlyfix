# docker/ – Docker konfiguráció

Docker Compose alapú fejlesztői környezet.

## Szolgáltatások

| Szolgáltatás | Leírás                          |
|--------------|---------------------------------|
| app          | PHP-FPM (Dockerfile.app)        |
| nginx        | Nginx webszerver (nginx/)       |
| mysql        | MySQL 8 adatbázis (mysql/)      |

## Indítás

```bash
# A projekt gyökeréből:
docker compose up -d

# Vagy make-kel:
make up
```

## Logok

A `logs/` mappa tartalmazza a szolgáltatások logjait. A `.gitkeep` fájlok tartják meg a mappa struktúrát, a logfájlok nincsenek verziókezelve.
