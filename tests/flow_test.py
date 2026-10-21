"""End-to-end purchase test for the Dane store. Prints PASS/FAIL per check and saves screenshots."""
import os
import asyncio
import sys
from urllib.parse import quote
from playwright.async_api import async_playwright

# Browser: on Windows use the installed Microsoft Edge (Playwright's own browser download is blocked in some regions).
# Override with PW_CHANNEL=chrome, or PW_CHANNEL=chromium for Playwright's downloaded browser.
_PW_REQ = os.environ.get("PW_CHANNEL") or ("msedge" if sys.platform == "win32" else "chromium")
PW_CHANNEL = None if _PW_REQ == "chromium" else _PW_REQ

BASE = os.environ.get("DANE_BASE", "http://dane.local").rstrip("/")
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "screenshots", "flow-")
os.makedirs(os.path.dirname(OUT), exist_ok=True)
VP = {"d": {"width": 1366, "height": 900}, "m": {"width": 390, "height": 844}}[sys.argv[1] if len(sys.argv) > 1 else "d"]
TAG = sys.argv[1] if len(sys.argv) > 1 else "d"
results = []


def check(name, ok, info=""):
    results.append((name, ok))
    print(("PASS " if ok else "FAIL ") + name + (f"  [{info}]" if info and not ok else ""))


async def no_overflow(pg, name):
    ov = await pg.evaluate("document.documentElement.scrollWidth - document.documentElement.clientWidth")
    check(f"no horizontal scroll: {name}", ov <= 0, f"{ov}px")


async def notices(pg):
    return " | ".join([t.strip() for t in await pg.locator(".woocommerce-error li, .woocommerce-message, .wc-block-components-notice-banner__content").all_inner_texts()])


async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(channel=PW_CHANNEL)
        ctx = await b.new_context(viewport=VP)
        pg = await ctx.new_page()
        errs = []
        pg.on("pageerror", lambda e: errs.append(str(e)))

        # 1. Variable product page
        await pg.goto(BASE + "/product/" + quote("اتیوپی-یرگاچفه") + "/", wait_until="networkidle")
        check("product page loads", await pg.locator("h1.product_title").count() == 1)
        check("coffee profile box shown", await pg.locator(".dane-profile").count() == 1)
        check("weight preselected 250g", await pg.locator("#pa_weight").input_value() == "250g")
        btn = pg.locator(".single_add_to_cart_button")
        check("add-to-cart disabled until grind chosen", "disabled" in (await btn.get_attribute("class") or ""))
        await pg.screenshot(path=f"{OUT}{TAG}-1-product.png", full_page=True)
        await no_overflow(pg, "product")
        await btn.click(force=True)
        await pg.wait_for_timeout(400)
        alert_text = ""
        pg.once("dialog", lambda d: asyncio.ensure_future(d.dismiss()))
        await pg.select_option("#pa_weight", "500g")
        await pg.select_option("#pa_grind", "espresso")
        await pg.wait_for_selector(".woocommerce-variation-price .price", state="visible")
        vprice = await pg.locator(".woocommerce-variation-price .price").inner_text()
        check("500g variation price shown", "۷۹۸٬۰۰۰" in vprice, vprice)
        await btn.click()
        await pg.wait_for_load_state("networkidle")
        msg = await notices(pg)
        check("added to cart notice (Persian)", "به سبد خرید اضافه شد" in msg, msg)
        check("header cart count = 1", (await pg.locator(".dane-cart-link__count").inner_text()).strip() == "۱")

        # 2. Out of stock product
        await pg.goto(BASE + "/product/" + quote("کنیا-aa") + "/", wait_until="networkidle")
        body = await pg.locator(".summary").inner_text()
        check("out-of-stock message shown", "ناموجود" in body, body[:120])
        check("out-of-stock has no add-to-cart", await pg.locator(".single_add_to_cart_button").count() == 0)

        # 3. Simple product via AJAX from shop
        await pg.goto(BASE + "/product-category/brew-gear/", wait_until="networkidle")
        await pg.screenshot(path=f"{OUT}{TAG}-2-category.png", full_page=True)
        await no_overflow(pg, "category")
        await pg.locator("a.add_to_cart_button").first.click()
        await pg.wait_for_selector("a.added_to_cart", timeout=8000)
        check("ajax add simple product", True)
        check("header cart count = 2", (await pg.locator(".dane-cart-link__count").inner_text()).strip() == "۲")

        # 4. Cart + coupon
        await pg.goto(BASE + "/cart/", wait_until="networkidle")
        check("cart has 2 lines", await pg.locator("tr.cart_item").count() == 2)
        await pg.fill("#coupon_code", "WELCOME10")
        await pg.click("button[name=apply_coupon]")
        await pg.wait_for_selector(".wc-block-components-notice-banner, .woocommerce-message, .woocommerce-error", timeout=8000)
        await pg.wait_for_load_state("networkidle")
        msg = await notices(pg)
        check("coupon applied", "اعمال شد" in msg or await pg.locator("tr.cart-discount").count() == 1, msg)
        await pg.screenshot(path=f"{OUT}{TAG}-3-cart.png", full_page=True)
        await no_overflow(pg, "cart")

        # 5. Checkout: validation
        await pg.goto(BASE + "/checkout/", wait_until="networkidle")
        await pg.screenshot(path=f"{OUT}{TAG}-4-checkout.png", full_page=True)
        await no_overflow(pg, "checkout")
        order = await pg.evaluate("""() => [...document.querySelectorAll('.woocommerce-billing-fields__field-wrapper > .form-row')].filter(e=>e.offsetParent).map(e=>e.id.replace('_field','').replace('billing_',''))""")
        check("field order Iranian", order == ["first_name", "last_name", "state", "city", "address_1", "postcode", "phone", "email"], str(order))
        check("no company / address_2", await pg.locator("#billing_company, #billing_address_2").count() == 0)
        await pg.click("#place_order")
        await pg.wait_for_selector(".woocommerce-error, .wc-block-components-notice-banner.is-error", timeout=10000)
        msg = await notices(pg)
        check("empty form → Persian required errors", "را وارد کنید" in msg and "Billing" not in msg, msg[:200])

        await pg.fill("#billing_first_name", "سارا")
        await pg.fill("#billing_last_name", "محمدی")
        await pg.select_option("#billing_state", "ESF")
        await pg.fill("#billing_city", "اصفهان")
        await pg.fill("#billing_address_1", "خیابان نمونه، کوچه ۳، پلاک ۱۲")
        await pg.fill("#billing_postcode", "۱۲۳۴۵")
        await pg.fill("#billing_phone", "۰۹۱۲۳۴۵")
        await pg.locator("#billing_city").dispatch_event("change")
        try:
            await pg.wait_for_function("() => (document.querySelector('#shipping_method')||{}).innerText?.includes('پست')", timeout=15000)
        except Exception:
            pass
        ship = await pg.locator("#shipping_method, .woocommerce-shipping-totals").first.inner_text()
        check("Isfahan → post shipping (free over limit)", "ارسال رایگان با پست" in ship and "پیک" not in ship, ship)
        check("Isfahan → no cash on delivery", await pg.locator("#payment_method_cod").count() == 0)
        if await pg.locator("#terms").count():
            await pg.check("#terms")
        await pg.click("#place_order")
        await pg.wait_for_selector(".woocommerce-error, .wc-block-components-notice-banner.is-error", timeout=10000)
        await pg.wait_for_timeout(300)
        msg = await notices(pg)
        check("short postcode rejected", "۱۰ رقم" in msg, msg[:200])
        check("short phone rejected", "۱۱ رقم" in msg, msg[:200])

        # 6. Tehran → courier + COD; Persian digits accepted
        await pg.select_option("#billing_state", "THR")
        await pg.fill("#billing_city", "تهران")
        await pg.fill("#billing_postcode", "۱۲۳۴۵ ۶۷۸۹۰")
        await pg.fill("#billing_phone", "۰۹۱۲-۳۴۵-۶۷۸۹")
        try:
            await pg.wait_for_function("() => (document.querySelector('#shipping_method')||{}).innerText?.includes('پیک')", timeout=15000)
            await pg.wait_for_selector("#payment_method_cod", timeout=10000)
        except Exception:
            pass
        ship = await pg.locator(".woocommerce-shipping-totals").first.inner_text()
        check("Tehran → free courier, paid courier hidden", "ارسال رایگان با پیک" in ship and "۸۰٬۰۰۰" not in ship, ship)
        check("Tehran → cash on delivery offered", await pg.locator("#payment_method_cod").count() == 1)
        await pg.check("#payment_method_cod")
        if await pg.locator("#terms").count():
            await pg.check("#terms")
        await pg.wait_for_timeout(900)
        await pg.screenshot(path=f"{OUT}{TAG}-5-checkout-filled.png", full_page=True)
        await pg.click("#place_order")
        await pg.wait_for_url("**/order-received/**", timeout=20000)
        await pg.wait_for_load_state("networkidle")
        txt = await pg.locator("main").inner_text()
        check("order received page", "سفارش شما ثبت شد" in txt, txt[:150])
        await pg.screenshot(path=f"{OUT}{TAG}-6-thankyou.png", full_page=True)
        await no_overflow(pg, "order received")
        check("no JS errors", not errs, "; ".join(errs[:3]))
        await b.close()
    failed = [n for n, ok in results if not ok]
    print(f"\n{len(results) - len(failed)}/{len(results)} passed" + (f"; failed: {failed}" if failed else ""))


asyncio.run(main())
