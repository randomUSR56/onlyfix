"""
test_auth.py -- Autentikációs és navigációs tesztek.

Tesztelt funkciók:
- Sikeres bejelentkezés minden szerepkörrel (user, mechanic, admin)
- Sikertelen bejelentkezés (hibás jelszó, nem létező email)
- Kijelentkezés
- Bejelentkezés nélküli hozzáférés blokkolása
- Regisztráció
- Szerepkör alapú sidebar navigáció
- Jogosulatlan oldalelérés
"""

import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from tests.Selenium.helpers.auth import (
    login,
    logout,
    wait_for_element,
    wait_for_url_contains,
    DEFAULT_WAIT,
)
from tests.Selenium.helpers.factories import generate_unique_email
from tests.Selenium.conftest import TEST_USERS, BASE_URL


# ══════════════════════════════════════════════════════════════════════════════
# Sikeres bejelentkezés tesztek
# ══════════════════════════════════════════════════════════════════════════════


class TestLoginSuccess:
    """Sikeres bejelentkezés minden szerepkörrel."""

    def test_login_success_user(self, driver, base_url):
        """A1: User szerepkörrel sikeres bejelentkezés, User Dashboard jelenik meg."""
        login(driver, base_url, TEST_USERS["user"]["email"], TEST_USERS["user"]["password"])

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        assert "/dashboard" in driver.current_url

        # User Dashboard renderelődik -- h1 heading megjelenése
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))

    def test_login_success_mechanic(self, driver, base_url):
        """A2: Mechanic szerepkörrel sikeres bejelentkezés, Mechanic Dashboard jelenik meg."""
        login(driver, base_url, TEST_USERS["mechanic"]["email"], TEST_USERS["mechanic"]["password"])

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        assert "/dashboard" in driver.current_url

        # Mechanic Dashboard: stat kártyák megjelenése
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))

    def test_login_success_admin(self, driver, base_url):
        """A3: Admin szerepkörrel sikeres bejelentkezés, Admin Dashboard jelenik meg."""
        login(driver, base_url, TEST_USERS["admin"]["email"], TEST_USERS["admin"]["password"])

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        assert "/dashboard" in driver.current_url

        # Admin Dashboard: legalább 4 stat kártya a grid-ben
        stat_cards = wait.until(
            EC.presence_of_all_elements_located(
                (By.CSS_SELECTOR, ".grid.gap-4 > div")
            )
        )
        assert len(stat_cards) >= 4, "Admin Dashboard-on legalább 4 stat kártyának kell lennie"


# ══════════════════════════════════════════════════════════════════════════════
# Sikertelen bejelentkezés tesztek
# ══════════════════════════════════════════════════════════════════════════════


class TestLoginFailure:
    """Sikertelen bejelentkezés tesztek."""

    def test_login_failure_wrong_password(self, driver, base_url):
        """A4: Hibás jelszóval a login oldalon marad, hibaüzenet jelenik meg."""
        driver.get(f"{base_url}/login")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        email_field = wait.until(EC.presence_of_element_located((By.ID, "email")))
        password_field = driver.find_element(By.ID, "password")

        email_field.send_keys(TEST_USERS["user"]["email"])
        password_field.send_keys("wrongpassword123")

        submit_btn = driver.find_element(By.CSS_SELECTOR, '[data-test="login-button"]')
        submit_btn.click()

        # Hibaüzenet megjelenésére várunk (InputError komponens)
        error = wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "p.text-red-600, p.text-red-500"))
        )
        assert error.text.strip() != "", "Hibaüzenetnek meg kell jelennie"
        assert "/login" in driver.current_url, "A login oldalon kell maradnunk"

    def test_login_failure_nonexistent_email(self, driver, base_url):
        """A5: Nem létező email-lel bejelentkezési kísérlet, hibaüzenet jelenik meg."""
        driver.get(f"{base_url}/login")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        email_field = wait.until(EC.presence_of_element_located((By.ID, "email")))
        password_field = driver.find_element(By.ID, "password")

        email_field.send_keys("nonexistent@example.com")
        password_field.send_keys("password")

        submit_btn = driver.find_element(By.CSS_SELECTOR, '[data-test="login-button"]')
        submit_btn.click()

        error = wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "p.text-red-600, p.text-red-500"))
        )
        assert error.text.strip() != "", "Hibaüzenetnek meg kell jelennie nem létező email-nél"


# ══════════════════════════════════════════════════════════════════════════════
# Kijelentkezés és hozzáférés-védelem
# ══════════════════════════════════════════════════════════════════════════════


class TestLogoutAndAccess:
    """Kijelentkezés és bejelentkezés nélküli hozzáférés tesztek."""

    def test_logout(self, logged_in_user, base_url):
        """A6: Kijelentkezés után a login oldalra kerülünk."""
        driver = logged_in_user
        logout(driver, base_url)
        assert "/login" in driver.current_url, "Kijelentkezés után a login oldalra kell kerülni"

    def test_unauthenticated_redirect(self, driver, base_url):
        """A7: Bejelentkezés nélkül a dashboard átirányít a login oldalra."""
        driver.get(f"{base_url}/dashboard")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.url_contains("/login"))
        assert "/login" in driver.current_url


# ══════════════════════════════════════════════════════════════════════════════
# Regisztráció
# ══════════════════════════════════════════════════════════════════════════════


class TestRegistration:
    """Regisztrációs tesztek."""

    def test_register_new_user(self, driver, base_url):
        """A8: Új felhasználó regisztrációja sikeres, dashboard-ra irányít."""
        driver.get(f"{base_url}/register")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        name_field = wait.until(EC.presence_of_element_located((By.ID, "name")))
        email_field = driver.find_element(By.ID, "email")
        password_field = driver.find_element(By.ID, "password")
        password_confirm_field = driver.find_element(By.ID, "password_confirmation")

        unique_email = generate_unique_email()

        name_field.send_keys("Selenium Test User")
        email_field.send_keys(unique_email)
        password_field.send_keys("SecurePassword123!")
        password_confirm_field.send_keys("SecurePassword123!")

        submit_btn = driver.find_element(By.CSS_SELECTOR, '[data-test="register-user-button"]')
        submit_btn.click()

        wait.until(EC.url_contains("/dashboard"))
        assert "/dashboard" in driver.current_url


# ══════════════════════════════════════════════════════════════════════════════
# Szerepkör alapú navigáció
# ══════════════════════════════════════════════════════════════════════════════


class TestRoleBasedNavigation:
    """Sidebar menü és jogosultsági tesztek."""

    def test_user_sidebar_items(self, logged_in_user, base_url):
        """N1: User sidebar tartalmazza: Dashboard, My Tickets, My Cars, Help."""
        driver = logged_in_user
        wait = WebDriverWait(driver, DEFAULT_WAIT)

        sidebar = wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "aside, [data-slot='sidebar']"))
        )

        links = sidebar.find_elements(By.CSS_SELECTOR, "a")
        hrefs = [link.get_attribute("href") for link in links if link.get_attribute("href")]

        assert any("/dashboard" in h for h in hrefs), "Dashboard link kell legyen"
        assert any("/tickets" in h for h in hrefs), "Tickets link kell legyen"
        assert any("/cars" in h for h in hrefs), "Cars link kell legyen"
        assert any("/help" in h for h in hrefs), "Help link kell legyen"
        # Users link NEM jelenhet meg user-nek
        assert not any(h.rstrip("/").endswith("/users") for h in hrefs), "Users link NEM jelenhet meg user-nek"

    def test_mechanic_sidebar_items(self, logged_in_mechanic, base_url):
        """N2: Mechanic sidebar: Dashboard, All Tickets, Help (Cars NEM)."""
        driver = logged_in_mechanic
        wait = WebDriverWait(driver, DEFAULT_WAIT)

        sidebar = wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "aside, [data-slot='sidebar']"))
        )

        links = sidebar.find_elements(By.CSS_SELECTOR, "a")
        hrefs = [link.get_attribute("href") for link in links if link.get_attribute("href")]

        assert any("/dashboard" in h for h in hrefs), "Dashboard link kell legyen"
        assert any("/tickets" in h for h in hrefs), "Tickets link kell legyen"

        # /cars nem lehet a fő nav linkek között mechanic-nak
        car_links = [h for h in hrefs if "/cars" in h]
        assert len(car_links) == 0, "Cars link NEM jelenhet meg mechanic sidebar-ban"

    def test_admin_sidebar_items(self, logged_in_admin, base_url):
        """N3: Admin sidebar: Dashboard, All Tickets, Users, Help."""
        driver = logged_in_admin
        wait = WebDriverWait(driver, DEFAULT_WAIT)

        sidebar = wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "aside, [data-slot='sidebar']"))
        )

        links = sidebar.find_elements(By.CSS_SELECTOR, "a")
        hrefs = [link.get_attribute("href") for link in links if link.get_attribute("href")]

        assert any("/dashboard" in h for h in hrefs), "Dashboard link kell legyen"
        assert any("/tickets" in h for h in hrefs), "Tickets link kell legyen"
        assert any("/users" in h for h in hrefs), "Users link kell legyen admin-nak"

    def test_user_cannot_access_users_page(self, logged_in_user, base_url):
        """N4: User nem érheti el a /users oldalt."""
        driver = logged_in_user
        driver.get(f"{base_url}/users")

        time.sleep(2)  # Inertia válasz
        page_source = driver.page_source.lower()
        is_forbidden = "403" in page_source or "forbidden" in page_source
        is_redirected = "/users" not in driver.current_url

        assert is_forbidden or is_redirected, "User nem férhet hozzá a /users oldalhoz"

    def test_mechanic_cannot_access_users_page(self, logged_in_mechanic, base_url):
        """N5: Mechanic nem érheti el a /users oldalt."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/users")

        time.sleep(2)
        page_source = driver.page_source.lower()
        is_forbidden = "403" in page_source or "forbidden" in page_source
        is_redirected = "/users" not in driver.current_url

        assert is_forbidden or is_redirected, "Mechanic nem férhet hozzá a /users oldalhoz"
