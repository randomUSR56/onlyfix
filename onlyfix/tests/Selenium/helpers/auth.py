"""
Autentikációs segédfüggvények Selenium tesztekhez.

Bejelentkezés, kijelentkezés és várakozási stratégiák az Inertia.js SPA-hoz.
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


# ── Várakozási idők ───────────────────────────────────────────────────────────

DEFAULT_WAIT = 10  # másodperc -- Inertia navigáció + szerver válasz


# ── Bejelentkezés ─────────────────────────────────────────────────────────────


def login(driver, base_url, email, password):
    """
    Bejelentkezés az alkalmazásba.

    A login form Fortify-alapú, a submit gomb data-test="login-button"
    attribútummal rendelkezik. Sikeres login után /dashboard URL-re navigál.
    """
    driver.get(f"{base_url}/login")

    wait = WebDriverWait(driver, DEFAULT_WAIT)
    email_field = wait.until(
        EC.presence_of_element_located((By.CSS_SELECTOR, "#email"))
    )
    password_field = driver.find_element(By.CSS_SELECTOR, "#password")

    email_field.clear()
    email_field.send_keys(email)
    password_field.clear()
    password_field.send_keys(password)

    submit_btn = driver.find_element(
        By.CSS_SELECTOR, '[data-test="login-button"]'
    )
    submit_btn.click()

    # Várjuk meg a dashboard betöltését
    wait.until(EC.url_contains("/dashboard"))


def logout(driver, base_url):
    """
    Kijelentkezés az alkalmazásból.

    A logout a sidebar user menüjéből érhető el (data-test="sidebar-menu-button").
    A kijelentkezés után /login oldalra irányít.
    """
    wait = WebDriverWait(driver, DEFAULT_WAIT)

    try:
        # A sidebar footer-ben lévő user menü megnyitása
        user_menu_trigger = wait.until(
            EC.element_to_be_clickable(
                (By.CSS_SELECTOR, "[data-test='sidebar-menu-button']")
            )
        )
        user_menu_trigger.click()

        # Logout gomb megnyomása
        logout_btn = wait.until(
            EC.element_to_be_clickable(
                (By.CSS_SELECTOR, "[data-test='logout-button']")
            )
        )
        logout_btn.click()
    except Exception:
        # Fallback: POST /logout JS-sel (Fortify)
        driver.execute_script(
            """
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = arguments[0] + '/logout';
            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrf.getAttribute('content');
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
            """,
            base_url,
        )

    # Várjuk a login oldalra visszakerülést
    wait.until(EC.url_contains("/login"))


# ── Várakozási segédfüggvények ────────────────────────────────────────────────


def wait_for_inertia_load(driver, timeout=DEFAULT_WAIT):
    """
    Várakozás az Inertia.js oldalbetöltés befejezésére.

    Inertia nem csinál teljes page reload-ot -- a DOM-ban kell figyelni
    a tartalom megjelenését.
    """
    wait = WebDriverWait(driver, timeout)
    wait.until(
        lambda d: len(d.find_elements(By.CSS_SELECTOR, "#app > *")) > 0
    )


def wait_for_element(driver, by, value, timeout=DEFAULT_WAIT):
    """Várakozás egy elem megjelenésére."""
    wait = WebDriverWait(driver, timeout)
    return wait.until(EC.presence_of_element_located((by, value)))


def wait_for_element_clickable(driver, by, value, timeout=DEFAULT_WAIT):
    """Várakozás egy kattintható elem megjelenésére."""
    wait = WebDriverWait(driver, timeout)
    return wait.until(EC.element_to_be_clickable((by, value)))


def wait_for_element_disappear(driver, by, value, timeout=DEFAULT_WAIT):
    """Várakozás egy elem eltűnésére."""
    wait = WebDriverWait(driver, timeout)
    return wait.until(EC.invisibility_of_element_located((by, value)))


def wait_for_url_contains(driver, url_part, timeout=DEFAULT_WAIT):
    """Várakozás amíg az URL tartalmazza a megadott részt."""
    wait = WebDriverWait(driver, timeout)
    wait.until(EC.url_contains(url_part))


def wait_for_text_present(driver, by, value, text, timeout=DEFAULT_WAIT):
    """Várakozás amíg egy elem tartalmazza a megadott szöveget."""
    wait = WebDriverWait(driver, timeout)
    return wait.until(EC.text_to_be_present_in_element((by, value), text))
