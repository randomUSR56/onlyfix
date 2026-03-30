"""
Tesztadat segédfüggvények.

Adatbázis újra-seedelése és tesztadatok előkészítése.
"""

import subprocess
import os
import time


# ── Az alkalmazás gyökérkönyvtára ─────────────────────────────────────────────

APP_ROOT = os.path.abspath(
    os.path.join(os.path.dirname(__file__), "..", "..", "..")
)


def reset_database():
    """
    Adatbázis frissítése és újra-seedelése tesztelés előtt.

    Futtatja: php artisan migrate:fresh --seed --force
    Ez ismert állapotba hozza az adatbázist minden tesztciklus előtt.
    """
    result = subprocess.run(
        ["php", "artisan", "migrate:fresh", "--seed", "--force"],
        cwd=APP_ROOT,
        capture_output=True,
        text=True,
        timeout=120,
    )
    if result.returncode != 0:
        raise RuntimeError(
            f"Adatbázis reset sikertelen:\nSTDOUT: {result.stdout}\nSTDERR: {result.stderr}"
        )
    return True


def get_seeded_car_data():
    """A seeder által létrehozott autó adatok (CarSeeder alapján)."""
    return {
        "test_user_cars": [
            {
                "make": "Toyota",
                "model": "Camry",
                "year": 2020,
                "license_plate": "ABC-1234",
                "color": "Silver",
            },
            {
                "make": "Honda",
                "model": "Civic",
                "year": 2019,
                "license_plate": "XYZ-5678",
                "color": "Blue",
            },
        ],
    }


def generate_unique_email():
    """Egyedi email cím generálása regisztrációs tesztekhez."""
    timestamp = int(time.time() * 1000)
    return f"testuser{timestamp}@example.com"


def generate_unique_license_plate():
    """Egyedi rendszám generálása autó tesztekhez."""
    timestamp = int(time.time()) % 10000
    return f"TST-{timestamp:04d}"
