# Súgó – Adminisztrátori útmutató

Az adminisztrátori szerepkör teljes hozzáférést biztosít a rendszer összes funkciójához. Ez az útmutató bemutatja az admin-specifikus lehetőségeket és felelősségeket.

---

## Irányítópult

Az admin irányítópult a rendszer teljes állapotát mutatja:

- **Összes felhasználó** – regisztrált ügyfelek száma
- **Összes szerelő** – aktív szerelők száma
- **Összes hibajegy** – a rendszerben lévő jegyek összege
- **Nyitott / Folyamatban / Befejezett** – jegyek státusz szerinti megoszlása

Az oldal jeleníti a legutóbbi hibajegyeket és a legújabban regisztrált felhasználókat is.

---

## Felhasználók kezelése

### Felhasználók listázása

1. Navigálj a **Felhasználók** menüpontra.
2. Látod az összes regisztrált felhasználót névvel, e-mail-lel, szerepkörrel és jegyszámmal.
3. Szűrés és keresés lehetséges név és e-mail szerint.

### Új felhasználó létrehozása

1. Kattints a **Felhasználó hozzáadása** gombra.
2. Add meg a nevet, e-mail-t, jelszót és szerepkört (felhasználó / szerelő / admin).
3. Kattints a **Létrehozás** gombra.

### Felhasználó szerkesztése

1. Kattints a felhasználó sorára.
2. Válaszd a **Szerkesztés** lehetőséget.
3. Módosítsd az adatokat – beleértve a szerepkört is.
4. Mentsd el a változtatásokat.

### Felhasználó törlése

A törlés végleges. A felhasználóhoz tartozó adatok (autók, hibajegyek) megmaradnak, de a fiók megszűnik. A törlés előtt erősítsd meg a szándékot a megjelenő megerősítő párbeszédablakban.

---

## Hibajegyek kezelése

Adminisztrátorként minden hibajegyet kezelhetsz:

- **Elfogad / Munka megkezdése / Befejezés** – ugyanazok az akciók, mint szerelőknél
- **Szerelő hozzárendelése** – egy jegy szerkesztésekor manuálisan rendelhetsz hozzá szerelőt
- **Jegy törlése** – véglegesen eltávolít egy jegyet a rendszerből

### Szerelő hozzárendelése jegyhez

1. Nyisd meg a hibajegy részletes nézetét.
2. Kattints a **Szerkesztés** gombra.
3. Az **Ügyfél és szerelő** szekcióban válaszd ki a kívánt szerelőt.
4. Mentsd el.

---

## Problémakatalógus

Az adminisztrátornak teljes jogosultsága van a problémák létrehozásához, szerkesztéséhez és **törléséhez** is.

### Probléma törlése

1. Nyisd meg a problémák listáját.
2. Kattints a törölni kívánt elemre.
3. Válaszd a **Törlés** lehetőséget és erősítsd meg.

> **Figyelem:** Ha egy problémát törölsz, az eltűnik a jövőbeli hibajegyekből, de a meglévő jegyeken megmarad.

---

## Statisztikák és jelentések

A **Statisztikák** oldalon részletes elemzések érhetők el:

- **Jegyek statisztikái**: státusz, prioritás, időbeli trendk
- **Problémák statisztikái**: leggyakrabban előforduló hibák, kategória szerinti bontás

Ezek az adatok segítenek azonosítani a visszatérő problémákat és az erőforrásigényt.

---

## Szerelők listája

A **Szerelők** menüpont alatt megtekintheted az összes aktív szerelőt és azok munkaterhistét.

---

## Tippek és gyakori kérdések

**Hogyan osztok ki jegyet egy adott szerelőnek?**
A hibajegy szerkesztése oldalon válaszd ki a szerelőt a legördülő listából, majd mentsd el.

**Lehet-e visszavonni egy törölt felhasználót?**
Nem. A törlés végleges – ha szükséges, hozz létre új fiókot.

**Miért nem tudok törölni egy problémát?**
Ha a probléma aktív hibajegyekhez van rendelve, a törlés sikertelen lehet. Előbb zárd le az érintett jegyeket.

**Hogyan ellenőrzöm a rendszer általános teljesítményét?**
Az irányítópult és a Statisztikák oldal együttesen adnak teljes képet a rendszer állapotáról.
