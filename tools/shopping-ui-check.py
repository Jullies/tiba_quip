from playwright.sync_api import sync_playwright
from pathlib import Path
import json

base='http://localhost/tiba_quip/'
checks=[];errors=[];bad=[]
with sync_playwright() as p:
    browser=p.chromium.launch(headless=True)
    context=browser.new_context(viewport={'width':1440,'height':950})
    page=context.new_page()
    page.on('pageerror',lambda e:errors.append(str(e)))
    page.on('response',lambda r:bad.append([r.status,r.url]) if r.status>=400 else None)
    page.goto(base+'shop-grid.php',wait_until='networkidle')
    assert page.locator('#shop-products [data-product-id]').count()==12
    checks.append('Initial page shows 12 products')
    initial_url=page.url
    page.locator('#shop-products form:has(input[value="cart_add"]) button').first.click()
    page.locator('#add-cart-modal.show').wait_for()
    page.locator('#add-cart-quantity').fill('2')
    page.locator('#add-cart-confirm').click()
    page.locator('#cart-toast:not([hidden])').wait_for()
    assert page.url==initial_url
    assert page.locator('.tq-header-actions [data-cart-count]').inner_text()=='2'
    checks.append('Quantity dialog adds two items without navigation; badge updates')
    page.locator('.tq-header-actions [data-cart-open]').click()
    page.locator('#cart-drawer .tq-cart-item').wait_for()
    assert page.locator('#cart-drawer .tq-quantity input').input_value()=='2'
    page.locator('#cart-drawer [data-cart-action="plus"]').click()
    page.wait_for_function("document.querySelector('#cart-drawer .tq-quantity input').value==='3'")
    page.locator('#cart-drawer [data-cart-action="minus"]').click()
    page.wait_for_function("document.querySelector('#cart-drawer .tq-quantity input').value==='2'")
    page.screenshot(path='storage/cart-drawer-new.png',full_page=False)
    checks.append('Cart drawer plus/minus updates quantities and subtotal')
    page.reload(wait_until='networkidle')
    assert page.locator('.tq-header-actions [data-cart-count]').inner_text()=='2'
    page.locator('.tq-header-actions [data-cart-open]').click()
    page.locator('#cart-drawer [data-cart-action="remove"]').click()
    page.locator('#cart-drawer .tq-cart-empty').wait_for()
    assert page.locator('.tq-header-actions [data-cart-count]').inner_text()=='0'
    page.get_by_role('button',name='Close cart',exact=True).click()
    checks.append('Cart persists after reload and supports removal')
    for expected in [24,36]:
        page.locator('#shop-load-sentinel').scroll_into_view_if_needed()
        page.wait_for_function('(n)=>document.querySelectorAll("#shop-products [data-product-id]").length>=n',arg=expected)
    ids=page.locator('#shop-products [data-product-id]').evaluate_all('(nodes)=>nodes.map(n=>n.dataset.productId)')
    assert len(ids)==len(set(ids))
    checks.append('Scrolling loads additional batches without duplicate products')
    page.get_by_role('button',name='List view',exact=True).click()
    assert 'is-list' in page.locator('#shop-products').get_attribute('class')
    page.get_by_role('button',name='Grid view',exact=True).click()
    checks.append('Grid/list switch works')
    page.goto(base+'shop-grid.php?min=100&max=10000&sort=price_asc&count=24',wait_until='networkidle')
    assert page.locator('#shop-products [data-product-id]').count()==24
    page.locator('#shop-load-sentinel').scroll_into_view_if_needed()
    page.wait_for_function('document.querySelectorAll("#shop-products [data-product-id]").length>=48')
    prices=page.locator('#shop-products .product-price span').all_text_contents()
    numbers=[float(x.replace('KES','').replace(',','').strip()) for x in prices]
    assert numbers==sorted(numbers) and all(100<=x<=10000 for x in numbers)
    checks.append('Infinite loading preserves price filters, sorting and page size')
    page.goto(base+'shop-grid.php',wait_until='networkidle')
    blocked=[False]
    def fail_once(route):
        if not blocked[0]:
            blocked[0]=True
            route.abort()
        else:
            route.continue_()
    page.route('**/shop-grid.php?page=2',fail_once)
    page.locator('#shop-load-sentinel').scroll_into_view_if_needed()
    page.get_by_role('button',name='Retry loading').wait_for()
    page.get_by_role('button',name='Retry loading').click()
    page.wait_for_function('document.querySelectorAll("#shop-products [data-product-id]").length>=24')
    page.unroute('**/shop-grid.php?page=2',fail_once)
    checks.append('Failed scroll request can be retried without duplicate products')
    page.goto(base+'shop-grid.php?max=0',wait_until='networkidle')
    assert page.locator('#shop-load-more').is_hidden()
    checks.append('Empty result stops automatic loading')
    for width in [390,768,1440]:
        page.set_viewport_size({'width':width,'height':900})
        page.goto(base+'shop-grid.php',wait_until='networkidle')
        assert not page.evaluate('document.documentElement.scrollWidth>innerWidth+1')
        if width==390:
            page.get_by_role('button',name='Open navigation menu').click()
            page.locator('#offcanvasNavbar.show').wait_for()
            assert page.locator('#offcanvasNavbar').evaluate('(e)=>getComputedStyle(e).visibility')=='visible'
            page.get_by_role('button',name='Close navigation',exact=True).click()
            page.get_by_role('button',name='Filters',exact=True).click()
            page.locator('#shop-filter-sidebar.show').wait_for()
            page.get_by_role('button',name='Close filters',exact=True).click()
            page.locator('#shop-filter-sidebar').wait_for(state='hidden')
            checks.append('Mobile navigation and filter drawers open and close')
        page.screenshot(path=f'storage/shop-new-{width}.png',full_page=False)
        checks.append(f'No horizontal overflow at {width}px')
    page.goto(base,wait_until='networkidle')
    page.screenshot(path='storage/header-new-desktop.png',full_page=False)
    assert not errors,errors
    assert not bad,bad
    browser.close()
report={'checks':checks,'errors':errors,'failed_resources':bad}
Path('storage/shopping-ui-report.json').write_text(json.dumps(report,indent=2))
print(json.dumps(report,indent=2))
