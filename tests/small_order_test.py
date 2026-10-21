import sys
"""Small order (below free-shipping limit): paid shipping per province, COD only in Tehran."""
import os
import asyncio
from playwright.async_api import async_playwright

# Browser: on Windows use the installed Microsoft Edge (Playwright's own browser download is blocked in some regions).
# Override with PW_CHANNEL=chrome, or PW_CHANNEL=chromium for Playwright's downloaded browser.
_PW_REQ = os.environ.get("PW_CHANNEL") or ("msedge" if sys.platform == "win32" else "chromium")
PW_CHANNEL = None if _PW_REQ == "chromium" else _PW_REQ
B = os.environ.get("DANE_BASE", "http://dane.local").rstrip("/")
async def main():
    ok = True
    async with async_playwright() as p:
        b = await p.chromium.launch(channel=PW_CHANNEL); pg = await b.new_page(viewport={"width": 1366, "height": 900})
        await pg.goto(B + "/product-category/brew-gear/", wait_until="networkidle")
        await pg.locator("li.product", has_text="فیلتر کاغذی").locator("a.add_to_cart_button").click()
        await pg.wait_for_selector("a.added_to_cart")
        await pg.goto(B + "/checkout/", wait_until="networkidle")
        for f, v in [("#billing_first_name", "علی"), ("#billing_last_name", "رضایی"), ("#billing_city", "شیراز"), ("#billing_address_1", "نشانی نمونه"), ("#billing_postcode", "1234567890"), ("#billing_phone", "09121234567")]:
            await pg.fill(f, v)
        for state, expect, cod in [("FRS", "پست پیشتاز", 0), ("THR", "پیک تهران", 1)]:
            await pg.select_option("#billing_state", state)
            await pg.wait_for_function(f"() => (document.querySelector('#shipping_method')||{{}}).innerText?.includes('{expect}')", timeout=15000)
            await pg.wait_for_timeout(500)
            ship = await pg.locator("#shipping_method").inner_text()
            n = await pg.locator("#payment_method_cod").count()
            good = expect in ship and "رایگان" not in ship and n == cod
            ok &= good
            print(("PASS" if good else "FAIL"), state, ship.replace("\n", " | "), "COD:", n)
        await b.close()
    print("small order:", "all passed" if ok else "FAILED")
asyncio.run(main())
