"""
OnlyFix Selenium tesztek -- pytest konfiguráció és fixture-ök.

Konfiguráció:
- Base URL: http://localhost (vagy ONLYFIX_BASE_URL env változó)
- Headless Chrome mód
- Implicit wait: nincs (explicit wait-et használunk mindenhol)
- Screenshot mentés sikertelen teszteknél
"""

import os
import pytest
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

# ── Konfiguráció ──────────────────────────────────────────────────────────────

BASE_URL = os.environ.get("ONLYFIX_BASE_URL", "http://localhost")

# Teszt felhasználók -- a DatabaseSeeder-ből származó adatok
TEST_USERS = {
    "admin": {
        "email": "admin@example.com",
        "password": "password",
        "name": "Admin User",
    },
    "mechanic": {
        "email": "mechanic@example.com",
        "password": "password",
        "name": "Mechanic User",
    },
    "user": {
        "email": "test@example.com",
        "password": "password",
        "name": "Test User",
    },
}


# ── Fixtures ──────────────────────────────────────────────────────────────────


def _create_driver():
    """Chrome WebDriver létrehozása headless módban."""
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-infobars")

    driver = webdriver.Chrome(options=options)
    driver.implicitly_wait(0)  # Explicit wait-et használunk
    return driver


@pytest.fixture(scope="function")
def driver(request):
    """Új böngészőpéldány minden teszthez."""
    d = _create_driver()
    yield d
    # Screenshot mentés sikertelen tesztnél
    if hasattr(request.node, "rep_call") and request.node.rep_call.failed:
        screenshot_dir = os.path.join(os.path.dirname(__file__), "reports")
        os.makedirs(screenshot_dir, exist_ok=True)
        screenshot_path = os.path.join(
            screenshot_dir, f"{request.node.name}.png"
        )
        d.save_screenshot(screenshot_path)
    d.quit()


@pytest.hookimpl(tryfirst=True, hookwrapper=True)
def pytest_runtest_makereport(item, call):
    """Teszt eredmény hozzárendelése a request node-hoz screenshot-hoz."""
    outcome = yield
    rep = outcome.get_result()
    setattr(item, f"rep_{rep.when}", rep)


@pytest.fixture
def base_url():
    """Az alkalmazás base URL-je."""
    return BASE_URL


@pytest.fixture
def user_credentials():
    """Teszt felhasználó adatok elérése."""
    return TEST_USERS


@pytest.fixture
def logged_in_user(driver, base_url):
    """Bejelentkezett user szerepkörű böngésző."""
    from tests.Selenium.helpers.auth import login

    login(driver, base_url, TEST_USERS["user"]["email"], TEST_USERS["user"]["password"])
    return driver


@pytest.fixture
def logged_in_mechanic(driver, base_url):
    """Bejelentkezett mechanic szerepkörű böngésző."""
    from tests.Selenium.helpers.auth import login

    login(driver, base_url, TEST_USERS["mechanic"]["email"], TEST_USERS["mechanic"]["password"])
    return driver


@pytest.fixture
def logged_in_admin(driver, base_url):
    """Bejelentkezett admin szerepkörű böngésző."""
    from tests.Selenium.helpers.auth import login

    login(driver, base_url, TEST_USERS["admin"]["email"], TEST_USERS["admin"]["password"])
    return driver
