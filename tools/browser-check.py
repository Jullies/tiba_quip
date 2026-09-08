from playwright.sync_api import sync_playwright
from pathlib import Path
import re,json
base='http://localhost/tiba_quip/'
results=[]
with sync_playwright() as p:
 browser=p.chromium.launch(headless=True)
 context=browser.new_context()
 context.route('**/tiba_wordpress/**',lambda route:route.abort())
 page=context.new_page();errors=[];bad=[]
 page.on('pageerror',lambda e:errors.append(str(e)))
 page.on('response',lambda r:bad.append((r.status,r.url)) if r.status>=400 else None)
 page.goto(base,wait_until='networkidle')
 product=page.locator('a[href*="shop-single.php?slug="]').first.get_attribute('href')
 blog=page.locator('a[href*="blog-single.php?slug="]').first.get_attribute('href')
 for width in [390,768,1440]:
  page.set_viewport_size({'width':width,'height':900})
  for route in ['', 'shop-grid.php',product,blog,'shop-cart.php','login.php','contact.php','about.php']:
   before=len(errors);start=len(bad)
   response=page.goto(base+route if not route.startswith('/') else 'http://localhost'+route,wait_until='networkidle')
   overflow=page.evaluate('document.documentElement.scrollWidth > innerWidth + 1')
   results.append({'route':route,'width':width,'status':response.status,'overflow':overflow,'errors':errors[before:],'bad':bad[start:]})
   if route=='' or (route=='shop-grid.php' and width==390):page.screenshot(path=f'storage/layout-{width}-{route or "home"}.png',full_page=True)
  if width==390:
   page.goto(base);page.get_by_role('button',name='Toggle navigation').click();page.wait_for_timeout(400)
   results.append({'mobile_menu_visible':page.locator('#offcanvasNavbar').evaluate('(e)=>e.classList.contains("show")')})
 access=Path('storage/admin-access.txt').read_text()
 page.goto(base+'admin/login.php')
 page.locator('#email').fill(re.search(r'Email: (.+)',access)[1]);page.locator('#password').fill(re.search(r'Password: (.+)',access)[1]);page.get_by_role('button',name='Login',exact=True).click();page.wait_for_url('**/admin/index.php')
 for width in [390,768,1440]:
  page.set_viewport_size({'width':width,'height':900})
  for route in ['admin/index.php','admin/manage.php?resource=products','admin/manage.php?resource=posts&edit=1','admin/settings.php','admin/analytics.php']:
   before=len(errors);start=len(bad);r=page.goto(base+route,wait_until='networkidle')
   results.append({'route':route,'width':width,'status':r.status,'complete':page.locator('.bottom-page').count()>0,'overflow':page.evaluate('document.documentElement.scrollWidth > innerWidth + 1'),'errors':errors[before:],'bad':bad[start:]})
   if route=='admin/index.php':page.screenshot(path=f'storage/admin-{width}.png',full_page=True)
 browser.close()
Path('storage/browser-report.json').write_text(json.dumps(results,indent=2))
print(json.dumps({'checks':len(results),'issues':[r for r in results if r.get('status',200)!=200 or r.get('overflow') or r.get('errors') or r.get('bad')],'mobile_menu':[r for r in results if 'mobile_menu_visible' in r]},indent=2))
