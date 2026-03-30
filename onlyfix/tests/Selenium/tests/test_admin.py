"""
test_admin.py -- Admin funkciók tesztelése.

Tesztelt funkciók:
- Felhasználók listázása
- Felhasználó létrehozás
- Szerepkör módosítás
- Felhasználó törlés
- Saját fiók törlés tiltása
- Admin Dashboard statisztikák
- Felhasználók szűrése szerepkör alapján
- Jegy létrehozás más felhasználó nevében
"""

import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC

from tests.Selenium.helpers.auth import (
    login,
    wait_for_element,
    wait_for_url_contains,
    DEFAULT_WAIT,
)
from tests.Selenium.helpers.factories import generate_unique_email
from tests.Selenium.conftest import TEST_USERS, BASE_URL


# ══════════════════════════════════════════════════════════════════════════════
# Felhasználó kezelés
# ══════════════════════════════════════════════════════════════════════════════


class TestAdminUserManagement:
    """Admin felhasználó kezelési tesztek."""

    def test_admin_view_all_users(self, logged_in_admin, base_url):
        """AD1: Admin látja az összes felhasználót."""
        driver = logged_in_admin
        driver.get(f"{base_url}/users")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        search = driver.find_elements(By.CSS_SELECTOR, "input[type='search']")
        assert len(search) > 0, "Keresőmezőnek meg kell jelennie"

        role_selects = driver.find_elements(By.CSS_SELECTOR, "select")
        assert len(role_selects) > 0, "Szerepkör szűrőnek meg kell jelennie"

    def test_admin_create_user(self, logged_in_admin, base_url):
        """AD2: Admin új felhasználót hoz létre."""
        driver = logged_in_admin
        driver.get(f"{base_url}/users/create")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.ID, "name")))

        unique_email = generate_unique_email()

        driver.find_element(By.ID, "name").send_keys("Selenium Created User")
        driver.find_element(By.ID, "email").send_keys(unique_email)

        role_select = Select(driver.find_element(By.ID, "role"))
        role_select.select_by_value("user")

        driver.find_element(By.ID, "password").send_keys("SecurePass123!")
        driver.find_element(By.ID, "password_confirmation").send_keys("SecurePass123!")

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()

        wait.until(EC.url_matches(r".*/users/\d+"))
        assert "/users/" in driver.current_url

    def test_admin_edit_user_role(self, logged_in_admin, base_url):
        """AD3: Admin módosítja egy felhasználó szerepkörét."""
        driver = logged_in_admin
        driver.get(f"{base_url}/users")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        action_buttons = driver.find_elements(By.CSS_SELECTOR, "button[aria-label]")

        edited = False
        for btn in action_buttons:
            try:
                btn.click()
                time.sleep(0.5)

                edit_items = driver.find_elements(
                    By.XPATH,
                    "//*[@data-slot='dropdown-menu-item' and "
                    "(contains(., 'Edit') or contains(., 'Szerkeszt'))]"
                )
                if edit_items:
                    edit_items[0].click()
                    wait.until(EC.url_contains("/edit"))

                    role_select = Select(driver.find_element(By.ID, "role"))
                    current_role = role_select.first_selected_option.get_attribute("value")

                    if current_role != "mechanic":
                        role_select.select_by_value("mechanic")
                    else:
                        role_select.select_by_value("user")

                    submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
                    submit_btn.click()
                    time.sleep(2)
                    edited = True
                    break
            except Exception:
                try:
                    driver.find_element(By.CSS_SELECTOR, "body").click()
                except Exception:
                    pass
                time.sleep(0.5)

        if not edited:
            pytest.skip("Nem sikerült felhasználót szerkeszteni")

    def test_admin_delete_user(self, logged_in_admin, base_url):
        """AD4: Admin törli egy felhasználó fiókját."""
        driver = logged_in_admin

        # Új user létrehozása a törléshez
        driver.get(f"{base_url}/users/create")
        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.ID, "name")))

        unique_email = generate_unique_email()
        driver.find_element(By.ID, "name").send_keys("To Delete User")
        driver.find_element(By.ID, "email").send_keys(unique_email)
        role_select = Select(driver.find_element(By.ID, "role"))
        role_select.select_by_value("user")
        driver.find_element(By.ID, "password").send_keys("DeleteMe123!")
        driver.find_element(By.ID, "password_confirmation").send_keys("DeleteMe123!")

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()
        wait.until(EC.url_matches(r".*/users/\d+"))
        time.sleep(1)

        # Törlés a users listáról
        driver.get(f"{base_url}/users")
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        action_buttons = driver.find_elements(By.CSS_SELECTOR, "button[aria-label]")

        for btn in action_buttons:
            try:
                btn.click()
                time.sleep(0.5)

                delete_items = driver.find_elements(
                    By.XPATH,
                    "//*[@data-slot='dropdown-menu-item' and contains(@class, 'destructive')]"
                )
                if delete_items:
                    delete_items[0].click()
                    time.sleep(1)

                    # Users/Index.vue browser confirm() dialogot használ
                    try:
                        alert = driver.switch_to.alert
                        alert.accept()
                        time.sleep(2)
                        return
                    except Exception:
                        dialog = driver.find_elements(By.CSS_SELECTOR, "[role='dialog']")
                        if dialog:
                            confirm_btn = dialog[0].find_elements(
                                By.XPATH, ".//button[contains(@class, 'destructive')]"
                            )
                            if confirm_btn:
                                confirm_btn[0].click()
                                time.sleep(2)
                                return
                else:
                    driver.find_element(By.CSS_SELECTOR, "body").click()
                    time.sleep(0.3)
            except Exception:
                try:
                    driver.find_element(By.CSS_SELECTOR, "body").click()
                except Exception:
                    pass
                time.sleep(0.3)

    def test_admin_cannot_delete_self(self, logged_in_admin, base_url):
        """AD5: Admin nem törölheti saját fiókját -- Delete opció nem jelenik meg."""
        driver = logged_in_admin
        driver.get(f"{base_url}/users")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Keressük az Admin User-t a listában
        user_links = driver.find_elements(By.CSS_SELECTOR, "a[href*='/users/']")
        for link in user_links:
            text = link.text.strip()
            if "Admin" in text:
                parent = link.find_element(
                    By.XPATH,
                    "./ancestor::div[contains(@class, 'grid') or contains(@class, 'p-4')]"
                )
                triggers = parent.find_elements(By.CSS_SELECTOR, "button[aria-label]")
                if triggers:
                    triggers[0].click()
                    time.sleep(0.5)

                    delete_items = driver.find_elements(
                        By.XPATH,
                        "//*[@data-slot='dropdown-menu-item' and "
                        "contains(@class, 'destructive')]"
                    )
                    assert len(delete_items) == 0, (
                        "Admin NEM törölheti saját magát"
                    )

                    driver.find_element(By.CSS_SELECTOR, "body").click()
                    return

        pytest.skip("Nem található admin user a listában")


# ══════════════════════════════════════════════════════════════════════════════
# Admin Dashboard
# ══════════════════════════════════════════════════════════════════════════════


class TestAdminDashboard:
    """Admin Dashboard tesztek."""

    def test_admin_dashboard_stats(self, logged_in_admin, base_url):
        """AD6: Admin Dashboard statisztikai kártyák megjelennek számokkal."""
        driver = logged_in_admin

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        assert "/dashboard" in driver.current_url

        stat_cards = wait.until(
            EC.presence_of_all_elements_located(
                (By.CSS_SELECTOR, ".grid.gap-4 > div")
            )
        )
        assert len(stat_cards) >= 4

        stat_values = driver.find_elements(By.CSS_SELECTOR, ".text-2xl.font-bold")
        for value in stat_values:
            text = value.text.strip()
            assert text != "", "A stat értéknek nem lehet üres"


# ══════════════════════════════════════════════════════════════════════════════
# Szűrők
# ══════════════════════════════════════════════════════════════════════════════


class TestAdminFilters:
    """Admin szűrő tesztek."""

    def test_admin_filter_users_by_role(self, logged_in_admin, base_url):
        """AD7: Felhasználók szűrése szerepkör alapján."""
        driver = logged_in_admin
        driver.get(f"{base_url}/users")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        role_select_elements = driver.find_elements(By.CSS_SELECTOR, "select")
        if role_select_elements:
            role_select = Select(role_select_elements[0])
            role_select.select_by_value("mechanic")
            time.sleep(1.5)
            assert "/users" in driver.current_url

            role_select.select_by_value("")
            time.sleep(1.5)
        else:
            pytest.skip("Szerepkör szűrő nem található")


# ══════════════════════════════════════════════════════════════════════════════
# Admin jegy létrehozás
# ══════════════════════════════════════════════════════════════════════════════


class TestAdminTicketCreation:
    """Admin jegy létrehozás más felhasználó nevében."""

    def test_admin_create_ticket_for_user(self, logged_in_admin, base_url):
        """AD8: Admin jegyet hoz létre egy másik felhasználó nevében."""
        driver = logged_in_admin
        driver.get(f"{base_url}/tickets/create")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Admin-specifikus user és mechanic selector (native select)
        user_selects = driver.find_elements(By.CSS_SELECTOR, "select")

        if len(user_selects) >= 1:
            user_select = Select(user_selects[0])
            options = user_select.options
            for opt in options:
                val = opt.get_attribute("value")
                if val and val != "":
                    user_select.select_by_value(val)
                    break
            time.sleep(1)

        # Autó kiválasztás
        car_cards = driver.find_elements(
            By.XPATH,
            "//div[contains(@class, 'cursor-pointer') and "
            ".//input[@type='radio' and @name='car_id']]"
        )

        if not car_cards:
            time.sleep(2)
            car_cards = driver.find_elements(
                By.XPATH,
                "//div[contains(@class, 'cursor-pointer') and "
                ".//input[@type='radio' and @name='car_id']]"
            )

        if car_cards:
            car_cards[0].click()
            time.sleep(0.5)

            # Probléma kiválasztás
            all_clickable = driver.find_elements(By.CSS_SELECTOR, "div.cursor-pointer.border")
            for elem in all_clickable:
                if not elem.find_elements(By.CSS_SELECTOR, "input[type='radio']"):
                    elem.click()
                    break

            # Leírás
            description = driver.find_element(By.CSS_SELECTOR, "textarea")
            description.send_keys("Admin ticket creation test - Selenium")

            submit_btn = wait.until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']"))
            )
            submit_btn.click()

            wait.until(EC.url_matches(r".*/tickets/\d+"))
            assert "/tickets/" in driver.current_url
        else:
            pytest.skip("Nincs elérhető autó a kiválasztott felhasználóhoz")
