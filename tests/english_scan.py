import sys
"""List visible English words (incl. screen-reader text) on storefront pages, with a cart filled."""
import os
import asyncio, re
from urllib.parse import quote
from playwright.async_api import async_playwright

# Browser: on Windows use the installed Microsoft Edge (Playwright's own browser download is blocked in some regions).
# Override with PW_CHANNEL=chrome, or PW_CHANNEL=chromium for Playwright's downloaded browser.
_PW_REQ = os.environ.get("PW_CHANNEL") or ("msedge" if sys.platform == "win32" else "chromium")
PW_CHANNEL = None if _PW_REQ == "chromium" else _PW_REQ
B=os.environ.get("DANE_BASE", "http://dane.local").rstrip("/")
async def main():
    async with async_playwright() as p:
        b=await p.chromium.launch(channel=PW_CHANNEL); pg=await b.new_page()
        await pg.goto(B+"/product-category/brew-gear/", wait_until="networkidle")
        await pg.locator("a.add_to_cart_button").first.click(); await pg.wait_for_selector("a.added_to_cart")
        await pg.goto(B+"/cart/", wait_until="networkidle"); await pg.fill("#coupon_code","WELCOME10"); await pg.click("button[name=apply_coupon]"); await pg.wait_for_timeout(2500)
        found={}
        for path in ["/","/shop/","/product-category/coffee-beans/","/product/"+quote("اتیوپی-یرگاچفه")+"/","/product/"+quote("کنیا-aa")+"/","/cart/","/checkout/","/my-account/","/faq/"]:
            await pg.goto(B+path, wait_until="networkidle")
            if path=="/checkout/":
                await pg.click("#place_order"); await pg.wait_for_timeout(3000)
            txt = await pg.evaluate("() => document.body.textContent")
            for w in set(re.findall(r"[A-Za-z][A-Za-z' ]{2,}[A-Za-z]", txt)):
                found.setdefault(w.strip(), set()).add(path.split('/')[1] or 'home')
        ignore = {"WELCOME","AA","welcome","V60"}
        for w,ps in sorted(found.items()):
            if w not in ignore and not w.startswith("var ") : print(f"{w!r:60} {sorted(ps)}")
        await b.close()
asyncio.run(main())
