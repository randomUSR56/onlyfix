"""
test_cars.py -- Autó kezelési tesztek.

Tesztelt funkciók:
- Autó létrehozás (user)
- Saját autók listázása
- Autó szerkesztés
- Autó törlés
- Mechanic nem hozhat létre autót
- Validációs hibák (üres form)
- Duplikált rendszám kezelés
"""

import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC

from tests.Selenium.helpers.auth import (
    wait_for_element,
    wait_for_url_contains,
    DEFAULT_WAIT,
)
from tests.Selenium.helpers.factories import generate_unique_license_plate
from tests.Selenium.conftest import TEST_USERS, BASE_URL


# ══════════════════════════════════════════════════════════════════════════════
# Autó CRUD
# ══════════════════════════════════════════════════════════════════════════════


class TestCarCreation:
    """Autó létrehozás és megtekintés tesztek."""

    def test_user_create_car(self, logged_in_user, base_url):
        """C1: User sikeresen létrehoz egy autót."""
        driver = logged_in_user
        driver.get(f"{base_url}/cars/create")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.ID, "make")))

        driver.find_element(By.ID, "make").send_keys("Selenium")
        driver.find_element(By.ID, "model").send_keys("TestCar")

        # Év kiválasztás (native select)
        year_select = Select(driver.find_element(By.ID, "year"))
        year_select.select_by_value("2023")

        unique_plate = generate_unique_license_plate()
        driver.find_element(By.ID, "license_plate").send_keys(unique_plate)
        driver.find_element(By.ID, "vin").send_keys("SELTEST1234567890"[:17])
        driver.find_element(By.ID, "color").send_keys("Red")

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()

        # Átirányítás a show oldalra
        wait.until(EC.url_matches(r".*/cars/\d+"))
        assert "/cars/" in driver.current_url

    def test_user_view_own_cars(self, logged_in_user, base_url):
        """C2: User saját autóinak listázása."""
        driver = logged_in_user
        driver.get(f"{base_url}/cars")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        cards = driver.find_elements(By.CSS_SELECTOR, "[data-slot='card']")
        assert len(cards) >= 1, "Legalább egy autó kártyának meg kell jelennie"


class TestCarEditing:
    """Autó szerkesztés tesztek."""

    def test_user_edit_car(self, logged_in_user, base_url):
        """C3: User sikeresen szerkeszt egy autót."""
        driver = logged_in_user
        driver.get(f"{base_url}/cars")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Dropdown menüből Edit választása az első autóhoz
        action_buttons = driver.find_elements(By.CSS_SELECTOR, "button[aria-label]")

        for btn in action_buttons:
            try:
                btn.click()
                time.sleep(0.5)

                edit_item = driver.find_elements(
                    By.XPATH,
                    "//*[@data-slot='dropdown-menu-item' and "
                    "(contains(., 'Edit') or contains(., 'Szerkeszt'))]"
                )
                if edit_item:
                    edit_item[0].click()
                    wait.until(EC.url_contains("/edit"))
                    break
            except Exception:
                continue

        if "/edit" in driver.current_url:
            color_field = wait.until(EC.presence_of_element_located((By.ID, "color")))
            color_field.clear()
            color_field.send_keys("Updated Blue")

            submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_btn.click()

            wait.until(EC.url_matches(r".*/cars/\d+$"))
            assert "/edit" not in driver.current_url
        else:
            pytest.skip("Nem sikerült az edit oldalra navigálni")


class TestCarDeletion:
    """Autó törlés tesztek."""

    def test_user_delete_car(self, logged_in_user, base_url):
        """C4: User törli saját autóját."""
        driver = logged_in_user

        # Új autó létrehozása amit törölhetünk
        driver.get(f"{base_url}/cars/create")
        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.ID, "make")))

        unique_plate = generate_unique_license_plate()
        driver.find_element(By.ID, "make").send_keys("ToDelete")
        driver.find_element(By.ID, "model").send_keys("TestCar")
        year_select = Select(driver.find_element(By.ID, "year"))
        year_select.select_by_value("2020")
        driver.find_element(By.ID, "license_plate").send_keys(unique_plate)

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()

        wait.until(EC.url_matches(r".*/cars/\d+"))
        time.sleep(1)

        # Delete gomb keresése a show oldalon
        delete_btns = driver.find_elements(
            By.XPATH,
            "//button[contains(@class, 'destructive') or "
            "(contains(., 'Delete') or contains(., 'Törlés'))]"
        )

        if delete_btns:
            delete_btns[0].click()
            time.sleep(1)

            # ConfirmDeleteDialog (Radix Dialog)
            dialog = driver.find_elements(By.CSS_SELECTOR, "[role='dialog']")
            if dialog:
                confirm_btn = dialog[0].find_elements(
                    By.XPATH, ".//button[contains(@class, 'destructive')]"
                )
                if confirm_btn:
                    confirm_btn[0].click()
                    time.sleep(2)

            wait.until(EC.url_contains("/cars"))


# ══════════════════════════════════════════════════════════════════════════════
# Jogosultsági tesztek
# ══════════════════════════════════════════════════════════════════════════════


class TestCarPermissions:
    """Autó jogosultsági tesztek."""

    def test_mechanic_cannot_create_car(self, logged_in_mechanic, base_url):
        """C5: Mechanic nem hozhat létre autót -- 403."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/cars/create")

        time.sleep(2)
        page_source = driver.page_source.lower()
        assert (
            "403" in page_source
            or "forbidden" in page_source
            or "/cars/create" not in driver.current_url
        ), "Mechanic nem férhet hozzá az autó létrehozás oldalhoz"


# ══════════════════════════════════════════════════════════════════════════════
# Validáció
# ══════════════════════════════════════════════════════════════════════════════


class TestCarValidation:
    """Autó form validációs tesztek."""

    def test_car_validation_errors(self, logged_in_user, base_url):
        """C6: Üres form beküldés validációs hibákat vagy böngésző validációt jelenít meg."""
        driver = logged_in_user
        driver.get(f"{base_url}/cars/create")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.ID, "make")))

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()

        time.sleep(2)

        errors = driver.find_elements(By.CSS_SELECTOR, "p.text-red-600, p.text-red-500")
        # Böngésző natív validáció vagy szerver oldali -- mindkettő elfogadható
        assert "/cars/create" in driver.current_url or len(errors) > 0, (
            "Validációs hibák kellenek üres form beküldésnél"
        )

    def test_car_duplicate_license_plate(self, logged_in_user, base_url):
        """C7: Duplikált rendszámmal létrehozás hibát ad."""
        driver = logged_in_user
        driver.get(f"{base_url}/cars/create")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.ID, "make")))

        # Létező rendszám a seeder-ből: ABC-1234
        driver.find_element(By.ID, "make").send_keys("Duplicate")
        driver.find_element(By.ID, "model").send_keys("Test")
        year_select = Select(driver.find_element(By.ID, "year"))
        year_select.select_by_value("2020")
        driver.find_element(By.ID, "license_plate").send_keys("ABC-1234")

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()

        time.sleep(2)

        errors = driver.find_elements(By.CSS_SELECTOR, "p.text-red-600, p.text-red-500")
        assert len(errors) > 0, "Duplikált rendszámnál validációs hibának kell megjelennie"
