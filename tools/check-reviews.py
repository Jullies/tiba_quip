from playwright.sync_api import sync_playwright
from pathlib import Path
import requests,re,json,subprocess

base='http://localhost/tiba_quip/'
name='__review_verification__'
checks=[]
cleanup="require 'app/bootstrap.php'; db('DELETE FROM product_reviews WHERE author_name=? AND source_id IS NULL',['__review_verification__']);"
try:
    with sync_playwright() as p:
        browser=p.chromium.launch(headless=True)
        context=browser.new_context(viewport={'width':1440,'height':950})
        page=context.new_page();errors=[]
        page.on('pageerror',lambda e:errors.append(str(e)))
        page.goto(base+'shop-grid.php',wait_until='networkidle')
        page.locator('.tq-product-rating').first.click()
        page.locator('#product-reviews-content').wait_for()
        page.locator('label[for="review-star-4"]').click()
        page.locator('#review-name').fill(name)
        page.locator('#review-email').fill('private-review@example.invalid')
        page.locator('#review-body').fill('Review verification <script>alert(1)</script>')
        page.get_by_role('button',name='Submit rating',exact=True).click()
        page.wait_for_load_state('networkidle')
        assert 'Your rating has been saved' in page.locator('.alert-info').inner_text()
        assert page.locator('.tq-review:has-text("'+name+'")').count()==1
        assert page.locator('.tq-review-summary strong').inner_text()=='4.0 / 5'
        checks.append('Guest submits a real 4-star review; average and count update')
        public_url=page.url
        public=requests.get(public_url).text
        assert 'private-review@example.invalid' not in public
        assert '&lt;script&gt;alert(1)&lt;/script&gt;' in public
        checks.append('Public reviews escape HTML and never reveal email addresses')
        page.locator('label[for="review-star-2"]').click()
        page.get_by_role('button',name='Update rating',exact=True).click()
        page.wait_for_load_state('networkidle')
        assert page.locator('.tq-review-summary strong').inner_text()=='2.0 / 5'
        assert page.locator('.tq-review:has-text("'+name+'")').count()==1
        checks.append('Updating a review changes the rating without creating a duplicate')
        page.goto(base+'shop-grid.php',wait_until='networkidle')
        assert '2.0 out of 5 from 1 reviews' in page.locator('.tq-product-rating').first.get_attribute('aria-label')
        checks.append('Shop card reflects the saved rating and review count')
        for width in [390,1440]:
            page.set_viewport_size({'width':width,'height':900})
            page.screenshot(path=f'storage/reviews-shop-{width}.png',full_page=False)
            assert not page.evaluate('document.documentElement.scrollWidth>innerWidth+1')
        assert not errors,errors
        browser.close()
    s=requests.Session();r=s.get(public_url)
    csrf=re.search(r'name="csrf" value="([^"]+)"',r.text)[1]
    pid=re.search(r'name="product_id" value="(\d+)"',r.text)[1]
    r=s.post(public_url,data={'csrf':csrf,'action':'review','product_id':pid,'name':name,'rating':6})
    assert 'Choose a rating from 1 to 5 stars' in r.text
    assert s.post(public_url,data={'action':'review','product_id':pid,'name':name,'rating':5}).status_code==403
    checks.append('Server rejects out-of-range ratings and missing CSRF tokens')
    admin=requests.Session();access=Path('storage/admin-access.txt').read_text();r=admin.get(base+'admin/login.php')
    admin.post(base+'admin/login.php',data={'csrf':re.search(r'name="csrf" value="([^"]+)"',r.text)[1],'email':re.search(r'Email: (.+)',access)[1],'password':re.search(r'Password: (.+)',access)[1]})
    from bs4 import BeautifulSoup
    r=admin.get(base+'admin/reviews.php');doc=BeautifulSoup(r.text,'html.parser');form=next(tr for tr in doc.select('tr') if name in tr.text).select_one('form')
    data={i['name']:i.get('value','') for i in form.select('input[name]')}
    r=admin.post(base+'admin/reviews.php',data=data);assert 'Review updated.' in r.text
    public=requests.get(public_url).text;assert name not in public
    checks.append('Admin can hide a review and it disappears from public ratings')
    print(json.dumps({'checks':checks,'passed':len(checks)},indent=2))
    Path('storage/review-check-report.json').write_text(json.dumps({'checks':checks,'passed':len(checks)},indent=2))
finally:
    subprocess.run(['C:/xampp/php/php.exe','-r',cleanup],check=True,capture_output=True)
