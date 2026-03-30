# OnlyFix – Szkriptek

## Melyik szkriptet mikor használd?

| Szkript | Platform | Névfeloldás | sudo szükséges | Mikor használd |
|---|---|---|---|---|
| `unix/init-dev.sh` | Unix | Igen | Igen | Első fejlesztői setup |
| `unix/init-dev-local.sh` | Unix | Nem | Nem | Gyors local fejlesztés |
| `unix/release.sh` | Unix | Igen | Igen | Éles kiadás |
| `unix/release-local.sh` | Unix | Nem | Nem | Éles, egyszerű környezet |
| `windows/init-dev.bat` | Windows | Igen | Igen | Első fejlesztői setup |
| `windows/init-dev-local.bat` | Windows | Nem | Nem | Gyors local fejlesztés |
| `windows/release.bat` | Windows | Igen | Igen | Éles kiadás |
| `windows/release-local.bat` | Windows | Nem | Nem | Éles, egyszerű környezet |

## Előfeltételek

- **Docker Desktop** telepítve és futtatva
- Unix-on: `bash` (a legtöbb rendszeren alapértelmezett)
- Windows-on: `cmd.exe` (a .bat fájlok futtatásához)

## Futtatás

### Unix (Linux / macOS)

Tedd futtathatóvá a szkripteket (egyszer kell):

```bash
chmod +x scripts/unix/*.sh
```

Fejlesztői init (névfeloldással, sudo-t kérni fog):

```bash
./scripts/unix/init-dev.sh
```

Fejlesztői init (localhost, nincs sudo):

```bash
./scripts/unix/init-dev-local.sh
```

Kiadói build (névfeloldással):

```bash
./scripts/unix/release.sh
```

Kiadói build (localhost):

```bash
./scripts/unix/release-local.sh
```

### Windows

Fejlesztői init (rendszergazdaként futtasd a cmd.exe-t):

```bat
scripts\windows\init-dev.bat
```

Fejlesztői init (localhost, nincs rendszergazda):

```bat
scripts\windows\init-dev-local.bat
```

Kiadói build (rendszergazdaként):

```bat
scripts\windows\release.bat
```

Kiadói build (localhost):

```bat
scripts\windows\release-local.bat
```

## Segédfüggvények

A `unix/helpers.sh` fájl közös függvényeket tartalmaz, amelyeket minden Unix szkript automatikusan betölt (`source`). Ezek:

| Függvény | Leírás |
|---|---|
| `check_docker()` | Docker telepítés + daemon ellenőrzés |
| `detect_compose()` | `docker-compose` vs `docker compose` detektálás |
| `print_success()` | Zöld ✅ üzenet |
| `print_error()` | Piros ❌ üzenet + kilépés |
| `print_step()` | 🔧 lépés üzenet |

## Névfeloldásos változatok – hosts bejegyzések

A névfeloldásos szkriptek (`init-dev.sh`, `release.sh`, `init-dev.bat`, `release.bat`) az alábbi bejegyzéseket adják hozzá a hosts fájlhoz:

```
127.0.1.1       onlyfix.local
127.0.1.2       db.onlyfix.local
127.0.1.3       mailpit.onlyfix.local
127.0.1.4       node.onlyfix.local
127.0.1.5       phpmyadmin.onlyfix.local
```

Ezen kívül loopback IP aliasokat is beállítanak (`127.0.1.1`–`127.0.1.5`).

## Visszaállítás – hosts bejegyzések eltávolítása

### Unix

Nyisd meg a `/etc/hosts` fájlt szerkesztővel és töröld az `# OnlyFix Project - Docker Services` sor alatti bejegyzéseket:

```bash
sudo nano /etc/hosts
```

Töröld a következő sorokat:

```
# OnlyFix Project - Docker Services
127.0.1.1       onlyfix.local
127.0.1.2       db.onlyfix.local
127.0.1.3       mailpit.onlyfix.local
127.0.1.4       node.onlyfix.local
127.0.1.5       phpmyadmin.onlyfix.local
```

### Windows

Nyisd meg rendszergazdaként a `C:\Windows\System32\drivers\etc\hosts` fájlt Jegyzettömbbel és töröld az `# OnlyFix Project - Docker Services` sor alatti bejegyzéseket.

## Loopback IP-k eltávolítása

### macOS

```bash
sudo ifconfig lo0 -alias 127.0.1.1
sudo ifconfig lo0 -alias 127.0.1.2
sudo ifconfig lo0 -alias 127.0.1.3
sudo ifconfig lo0 -alias 127.0.1.4
sudo ifconfig lo0 -alias 127.0.1.5
```

### Linux

```bash
sudo ip addr del 127.0.1.1/8 dev lo
sudo ip addr del 127.0.1.2/8 dev lo
sudo ip addr del 127.0.1.3/8 dev lo
sudo ip addr del 127.0.1.4/8 dev lo
sudo ip addr del 127.0.1.5/8 dev lo
```

### Windows

```bat
netsh interface ip delete address "Loopback" 127.0.1.1
netsh interface ip delete address "Loopback" 127.0.1.2
netsh interface ip delete address "Loopback" 127.0.1.3
netsh interface ip delete address "Loopback" 127.0.1.4
netsh interface ip delete address "Loopback" 127.0.1.5
```
