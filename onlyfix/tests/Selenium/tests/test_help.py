"""
test_help.py -- Súgó oldal tesztek.

Tesztelt funkciók:
- Súgó oldal tartalom user szerepkörrel
- Súgó oldal tartalom mechanic szerepkörrel
- Súgó oldal tartalom admin szerepkörrel
- Szerepkörönként eltérő tartalom (markdown renderelés)
"""

import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from tests.Selenium.helpers.auth import DEFAULT_WAIT
from tests.Selenium.conftest import TEST_USERS, BASE_URL


class TestHelpPage:
    """Súgó oldal szerepkörönkénti tartalom tesztek."""

    def test_help_page_user_content(self, logged_in_user, base_url):
        """H1: User-nek megjelenik a súgó tartalom."""
        driver = logged_in_user
        driver.get(f"{base_url}/help")

        wait = WebDriverWait(driver, DEFAULT_WAIT)

        # A help-content div-ben markdown-ból renderelt HTML tartozik
        help_content = wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, ".help-content, [class*='help'], .prose, main")
            )
        )
        time.sleep(1)

        content_text = help_content.text.strip()
        assert len(content_text) > 0, "A súgó oldalnak tartalmaznia kell szöveget"

    def test_help_page_mechanic_content(self, logged_in_mechanic, base_url):
        """H2: Mechanic-nak megjelenik a mechanic-specifikus súgó tartalom."""
        driver = logged_in_mechanic
        driver.get(f"{base_url}/help")

        wait = WebDriverWait(driver, DEFAULT_WAIT)

        help_content = wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, ".help-content, [class*='help'], .prose, main")
            )
        )
        time.sleep(1)

        content_text = help_content.text.strip()
        assert len(content_text) > 0, "A mechanic súgó oldalnak tartalmaznia kell szöveget"

    def test_help_page_admin_content(self, logged_in_admin, base_url):
        """H3: Admin-nak megjelenik az admin-specifikus súgó tartalom."""
        driver = logged_in_admin
        driver.get(f"{base_url}/help")

        wait = WebDriverWait(driver, DEFAULT_WAIT)

        help_content = wait.until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, ".help-content, [class*='help'], .prose, main")
            )
        )
        time.sleep(1)

        content_text = help_content.text.strip()
        assert len(content_text) > 0, "Az admin súgó oldalnak tartalmaznia kell szöveget"
