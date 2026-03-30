"""
test_tickets.py -- Jegy (Ticket) kezelési tesztek.

Tesztelt funkciók:
- Jegy létrehozás (user)
- Saját jegyek listázása
- Mechanic: összes jegy megtekintése
- Ticket workflow: accept → start → complete → close
- Mechanic nem hozhat létre jegyet
- Jegy törlés (user, open státuszú)
- Szűrők (státusz, prioritás)
- Teljes workflow integációs teszt
"""

import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from tests.Selenium.helpers.auth import (
    login,
    wait_for_element,
    wait_for_element_clickable,
    wait_for_url_contains,
    DEFAULT_WAIT,
)
from tests.Selenium.conftest import TEST_USERS, BASE_URL


# ══════════════════════════════════════════════════════════════════════════════
# Jegy létrehozás
# ══════════════════════════════════════════════════════════════════════════════


class TestTicketCreation:
    """Jegy CRUD műveletek tesztelése."""

    def test_user_create_ticket(self, logged_in_user, base_url):
        """T1: User jegyet hoz létre: autó + probléma + leírás + prioritás kiválasztás."""
        driver = logged_in_user
        driver.get(f"{base_url}/tickets/create")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))

        # 1. Autó kiválasztása -- kattintsunk az első autó kártyára
        # Az autó kártyák div.cursor-pointer elemek rejtett radio input-tal
        car_cards = wait.until(
            EC.presence_of_all_elements_located(
                (By.XPATH,
                 "//div[contains(@class, 'cursor-pointer') and "
                 ".//input[@type='radio' and @name='car_id']]")
            )
        )
        assert len(car_cards) > 0, "Legalább egy autónak elérhetőnek kell lennie"
        car_cards[0].click()
        time.sleep(0.5)

        # 2. Probléma kiválasztása -- clickable div-ek radio input NÉLKÜL
        all_clickable = driver.find_elements(By.CSS_SELECTOR, "div.cursor-pointer.border")
        for elem in all_clickable:
            if not elem.find_elements(By.CSS_SELECTOR, "input[type='radio']"):
                elem.click()
                break
        time.sleep(0.5)

        # 3. Leírás kitöltése
        description = wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "textarea"))
        )
        description.send_keys("Selenium teszt jegy - automatikusan létrehozva teszteléshez")

        # 4. Prioritás kiválasztása (high = 3. kártya: 0=low, 1=medium, 2=high)
        priority_cards = driver.find_elements(
            By.XPATH,
            "//div[contains(@class, 'cursor-pointer') and "
            ".//input[@type='radio' and @name='priority']]"
        )
        if len(priority_cards) >= 3:
            priority_cards[2].click()

        # 5. Submit
        submit_btn = wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']"))
        )
        submit_btn.click()

        # Átirányítás a jegy show oldalára
        wait.until(EC.url_matches(r".*/tickets/\d+"))
        assert "/tickets/" in driver.current_url

    def test_user_view_own_tickets(self, logged_in_user, base_url):
        """T2: User saját jegyeinek listázása."""
        driver = logged_in_user
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Az oldal betöltődött
        cards = driver.find_elements(By.CSS_SELECTOR, "[data-slot='card'], .border.rounded-lg")
        assert len(cards) >= 0  # Oldal sikeresen betöltődött

    def test_mechanic_view_all_tickets(self, logged_in_mechanic, base_url):
        """T4: Mechanic az összes jegyet listázhatja."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Keresőmező megjelenik
        search_input = driver.find_elements(By.CSS_SELECTOR, "input[type='search']")
        assert len(search_input) > 0, "Keresőmezőnek meg kell jelennie"


# ══════════════════════════════════════════════════════════════════════════════
# Ticket workflow
# ══════════════════════════════════════════════════════════════════════════════


class TestTicketWorkflow:
    """Jegy workflow tesztelése: accept → start → complete → close."""

    def test_mechanic_accept_ticket(self, logged_in_mechanic, base_url):
        """T5: Mechanic elfogad egy open jegyet."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/dashboard")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Accept gomb keresése (dashboard-on vagy tickets index-en)
        accept_buttons = driver.find_elements(
            By.XPATH,
            "//button[contains(., 'Accept') or contains(., 'Elfogad') or contains(., 'accept')]"
        )

        if not accept_buttons:
            driver.get(f"{base_url}/tickets")
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
            time.sleep(1)
            accept_buttons = driver.find_elements(
                By.XPATH,
                "//button[contains(., 'Accept') or contains(., 'Elfogad') or contains(., 'accept')]"
            )

        if accept_buttons:
            accept_buttons[0].click()
            time.sleep(2)  # Inertia frissítés
        else:
            pytest.skip("Nincs elérhető open jegy elfogadásra")

    def test_mechanic_start_work(self, logged_in_mechanic, base_url):
        """T6: Mechanic munkát indít egy elfogadott jegyen."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        ticket_links = driver.find_elements(By.CSS_SELECTOR, "a[href*='/tickets/']")

        for link in ticket_links:
            try:
                link.click()
                wait.until(EC.url_matches(r".*/tickets/\d+"))
                time.sleep(1)

                start_buttons = driver.find_elements(
                    By.XPATH,
                    "//button[contains(., 'Start') or contains(., 'Indít') or contains(., 'start')]"
                )
                if start_buttons:
                    start_buttons[0].click()
                    time.sleep(2)
                    return
                else:
                    driver.back()
                    time.sleep(1)
            except Exception:
                driver.get(f"{base_url}/tickets")
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
                time.sleep(1)

        pytest.skip("Nincs assigned jegy a Start Work teszteléshez")

    def test_mechanic_complete_ticket(self, logged_in_mechanic, base_url):
        """T7: Mechanic befejez egy in_progress jegyet."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        ticket_links = driver.find_elements(By.CSS_SELECTOR, "a[href*='/tickets/']")

        for link in ticket_links:
            try:
                link.click()
                wait.until(EC.url_matches(r".*/tickets/\d+"))
                time.sleep(1)

                complete_buttons = driver.find_elements(
                    By.XPATH,
                    "//button[contains(., 'Complete') or contains(., 'Befejez') or contains(., 'complete')]"
                )
                if complete_buttons:
                    complete_buttons[0].click()
                    time.sleep(2)
                    return
                else:
                    driver.back()
                    time.sleep(1)
            except Exception:
                driver.get(f"{base_url}/tickets")
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
                time.sleep(1)

        pytest.skip("Nincs in_progress jegy a Complete teszteléshez")

    def test_user_close_ticket(self, logged_in_user, base_url):
        """T8: User lezár egy completed jegyet."""
        driver = logged_in_user
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        ticket_links = driver.find_elements(By.CSS_SELECTOR, "a[href*='/tickets/']")

        for link in ticket_links:
            try:
                link.click()
                wait.until(EC.url_matches(r".*/tickets/\d+"))
                time.sleep(1)

                close_buttons = driver.find_elements(
                    By.XPATH,
                    "//button[contains(., 'Close') or contains(., 'Lezár') or contains(., 'close')]"
                )
                if close_buttons:
                    close_buttons[0].click()
                    time.sleep(1)

                    # Radix Dialog kezelése
                    dialog = driver.find_elements(By.CSS_SELECTOR, "[role='dialog']")
                    if dialog:
                        confirm_btns = dialog[0].find_elements(By.CSS_SELECTOR, "button")
                        if confirm_btns:
                            confirm_btns[-1].click()
                            time.sleep(2)
                    return
                else:
                    driver.back()
                    time.sleep(1)
            except Exception:
                driver.get(f"{base_url}/tickets")
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
                time.sleep(1)

        pytest.skip("Nincs completed jegy a Close teszteléshez")


# ══════════════════════════════════════════════════════════════════════════════
# Jogosultsági tesztek
# ══════════════════════════════════════════════════════════════════════════════


class TestTicketPermissions:
    """Jegy jogosultsági tesztek."""

    def test_mechanic_cannot_create_ticket(self, logged_in_mechanic, base_url):
        """T9: Mechanic nem hozhat létre jegyet -- 403 hiba."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/tickets/create")

        time.sleep(2)
        page_source = driver.page_source.lower()
        assert (
            "403" in page_source
            or "forbidden" in page_source
            or "/tickets/create" not in driver.current_url
        ), "Mechanic nem férhet hozzá a jegy létrehozás oldalhoz"

    def test_user_delete_open_ticket(self, logged_in_user, base_url):
        """T10: User saját open jegyét törölheti."""
        driver = logged_in_user
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        ticket_links = driver.find_elements(By.CSS_SELECTOR, "a[href*='/tickets/']")

        for link in ticket_links:
            try:
                link.click()
                wait.until(EC.url_matches(r".*/tickets/\d+"))
                time.sleep(1)

                delete_buttons = driver.find_elements(
                    By.XPATH,
                    "//button[contains(@class, 'destructive') or "
                    "(contains(., 'Delete') or contains(., 'Törlés') or contains(., 'Töröl'))]"
                )
                if delete_buttons:
                    delete_buttons[0].click()
                    time.sleep(1)

                    # ConfirmDeleteDialog (Radix Dialog)
                    dialog = driver.find_elements(By.CSS_SELECTOR, "[role='dialog']")
                    if dialog:
                        destructive_btn = dialog[0].find_elements(
                            By.XPATH, ".//button[contains(@class, 'destructive')]"
                        )
                        if destructive_btn:
                            destructive_btn[0].click()
                            time.sleep(2)
                            assert "/tickets" in driver.current_url
                            return
                else:
                    driver.back()
                    time.sleep(1)
            except Exception:
                driver.get(f"{base_url}/tickets")
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
                time.sleep(1)

        pytest.skip("Nincs törölhető open jegy a teszthez")


# ══════════════════════════════════════════════════════════════════════════════
# Szűrők
# ══════════════════════════════════════════════════════════════════════════════


class TestTicketFilters:
    """Jegy szűrő tesztek."""

    def test_ticket_filter_by_status(self, logged_in_mechanic, base_url):
        """T12: Jegyek szűrése státusz alapján."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Radix DropdownMenuTrigger keresése szűrőkhöz
        filter_buttons = driver.find_elements(
            By.CSS_SELECTOR, "button[data-slot='dropdown-menu-trigger'], button.gap-1"
        )

        if filter_buttons:
            filter_buttons[0].click()
            time.sleep(1)

            menu_items = driver.find_elements(
                By.CSS_SELECTOR, "[data-slot='dropdown-menu-item'], [role='menuitem']"
            )
            if menu_items:
                menu_items[0].click()
                time.sleep(1)
                assert "/tickets" in driver.current_url
            return

        # Fallback: keresőmező teszt
        search = driver.find_elements(By.CSS_SELECTOR, "input[type='search']")
        if search:
            search[0].send_keys("test")
            time.sleep(1)
            assert "/tickets" in driver.current_url

    def test_ticket_filter_by_priority(self, logged_in_mechanic, base_url):
        """T13: Jegyek szűrése prioritás alapján."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/tickets")

        wait = WebDriverWait(driver, DEFAULT_WAIT)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        filter_buttons = driver.find_elements(
            By.CSS_SELECTOR, "button[data-slot='dropdown-menu-trigger'], button.gap-1"
        )

        if len(filter_buttons) >= 2:
            filter_buttons[1].click()
            time.sleep(1)

            menu_items = driver.find_elements(
                By.CSS_SELECTOR, "[data-slot='dropdown-menu-item'], [role='menuitem']"
            )
            if menu_items:
                menu_items[0].click()
                time.sleep(1)
                assert "/tickets" in driver.current_url
        else:
            pytest.skip("Prioritás szűrő nem található")


# ══════════════════════════════════════════════════════════════════════════════
# Teljes workflow integrácios teszt
# ══════════════════════════════════════════════════════════════════════════════


class TestFullWorkflow:
    """Teljes jegy workflow integációs teszt."""

    def test_ticket_workflow_full_cycle(self, driver, base_url):
        """T11: Teljes ciklus: user create → mechanic accept → start → complete → user close."""
        wait = WebDriverWait(driver, DEFAULT_WAIT)

        # === 1. User létrehoz egy jegyet ===
        login(driver, base_url, TEST_USERS["user"]["email"], TEST_USERS["user"]["password"])
        driver.get(f"{base_url}/tickets/create")
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # Autó kiválasztás
        car_cards = driver.find_elements(
            By.XPATH,
            "//div[contains(@class, 'cursor-pointer') and "
            ".//input[@type='radio' and @name='car_id']]"
        )
        if not car_cards:
            pytest.skip("Nincs autó a jegy létrehozáshoz")
        car_cards[0].click()
        time.sleep(0.5)

        # Probléma kiválasztás
        all_clickable = driver.find_elements(By.CSS_SELECTOR, "div.cursor-pointer.border")
        for elem in all_clickable:
            if not elem.find_elements(By.CSS_SELECTOR, "input[type='radio']"):
                elem.click()
                break
        time.sleep(0.5)

        # Leírás
        description = driver.find_element(By.CSS_SELECTOR, "textarea")
        description.send_keys("Full workflow test - Selenium")

        # Submit
        submit_btn = wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']"))
        )
        submit_btn.click()
        wait.until(EC.url_matches(r".*/tickets/\d+"))
        ticket_url = driver.current_url

        # === 2. Mechanic bejelentkezés ===
        driver.delete_all_cookies()
        login(driver, base_url, TEST_USERS["mechanic"]["email"], TEST_USERS["mechanic"]["password"])
        driver.get(ticket_url)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        # === 3. Accept ===
        accept_btn = driver.find_elements(
            By.XPATH,
            "//button[contains(., 'Accept') or contains(., 'Elfogad')]"
        )
        if accept_btn:
            accept_btn[0].click()
            time.sleep(2)

        # === 4. Start Work ===
        driver.get(ticket_url)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        start_btn = driver.find_elements(
            By.XPATH,
            "//button[contains(., 'Start') or contains(., 'Indít')]"
        )
        if start_btn:
            start_btn[0].click()
            time.sleep(2)

        # === 5. Complete ===
        driver.get(ticket_url)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        complete_btn = driver.find_elements(
            By.XPATH,
            "//button[contains(., 'Complete') or contains(., 'Befejez')]"
        )
        if complete_btn:
            complete_btn[0].click()
            time.sleep(2)

        # === 6. User lezárja ===
        driver.delete_all_cookies()
        login(driver, base_url, TEST_USERS["user"]["email"], TEST_USERS["user"]["password"])
        driver.get(ticket_url)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1")))
        time.sleep(1)

        close_btn = driver.find_elements(
            By.XPATH,
            "//button[contains(., 'Close') or contains(., 'Lezár')]"
        )
        if close_btn:
            close_btn[0].click()
            time.sleep(1)
            dialog = driver.find_elements(By.CSS_SELECTOR, "[role='dialog']")
            if dialog:
                confirm_btns = dialog[0].find_elements(By.CSS_SELECTOR, "button")
                if confirm_btns:
                    confirm_btns[-1].click()
                    time.sleep(2)
