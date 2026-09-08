import requests,re,json,time
from pathlib import Path
from urllib.parse import urlparse,parse_qs
base='http://localhost/tiba_quip/'
admin=requests.Session();customer=requests.Session();outsider=requests.Session();checks=[];fixture={'products':[],'categories':[],'posts':[],'orders':[],'emails':[],'uploads':[],'cookies':{}}
def check(condition,label):
 if not condition:raise AssertionError(label)
 checks.append(label)
def get(s,path):
 r=s.get(base+path);check(r.status_code==200 and '</html>' in r.text,'GET '+re.sub(r'token=[^&]+','token=[redacted]',path));return r
def token(r):return re.search(r'name="csrf" value="([^"]+)"',r.text)[1]
def post(s,path,data,files=None):
 r=get(s,'user-dashboard.php' if path=='logout.php' else path);data['csrf']=token(r);return s.post(base+path,data=data,files=files)
def saved_id(r):
 check(r.status_code==200 and 'Saved successfully.' in r.text,'Admin save')
 return int(parse_qs(urlparse(r.url).query)['id'][0])
try:
 access=Path('storage/admin-access.txt').read_text();r=post(admin,'admin/login.php',{'email':re.search(r'Email: (.+)',access)[1],'password':re.search(r'Password: (.+)',access)[1]});check('/admin/index.php' in r.url,'Admin authentication')
 r=outsider.get(base+'admin/manage.php');check('/admin/login.php' in r.url,'Admin guard redirects anonymous requests')
 r=admin.post(base+'admin/manage.php',data={'action':'save'});check(r.status_code==403,'CSRF rejects missing token')
 cat=saved_id(post(admin,'admin/manage.php?resource=categories&edit=1',{'action':'save','name':'__verification category','slug':'verification-category','sort_order':'0','active':'1'}));fixture['categories'].append(cat)
 fields={'action':'save','name':'__verification product','slug':'verification-product','sku':'TIBA-VERIFY-001','description':'<p>Verified content</p><script>alert(1)</script><img src=x onerror=alert(2)>','short_description':'Test fixture','regular_price':'100','sale_price':'80','stock':'2','manage_stock':'1','stock_status':'instock','status':'publish','type':'simple','featured':'0','attributes':'[]','dimensions':'[]','variants':'[]','categories[]':str(cat)}
 with open('assets/img/logo/favicon.png','rb') as image:
  pid=saved_id(post(admin,'admin/manage.php?resource=products&edit=1',fields.copy(),{'image':('verification.png',image,'image/png')}))
 fixture['products'].append(pid)
 r=get(admin,f'admin/manage.php?resource=products&id={pid}&edit=1');match=re.search(r'src="(/tiba_quip/uploads/media/[^"]+)"',r.text)
 if match:fixture['uploads'].append(match[1].replace('/tiba_quip/',''))
 r=get(customer,'shop-single.php?slug=verification-product');check('<script>alert(1)' not in r.text and 'onerror=' not in r.text,'HTML sanitizer rejects executable content')
 check('KES 80.00' in r.text,'Product sale price rendered')
 r=get(customer,'shop-grid.php?category=verification-category&q=verification&min=70&max=90&sort=price_asc');check('__verification product' in r.text,'Category, search and price filters')
 r=get(customer,'shop-grid.php?category=verification-category&max=70');check('No products match' in r.text,'Price filtering excludes products')
 r=post(customer,'shop-single.php?slug=verification-product',{'action':'cart_add','product_id':pid,'quantity':3});check('requested quantity is unavailable' in r.text,'Stock limit enforced when adding')
 r=post(customer,'shop-single.php?slug=verification-product',{'action':'cart_add','product_id':pid,'quantity':1});check('Added to your cart.' in r.text,'Add to cart')
 itemid=re.search(r'name="quantity\[(\d+)\]"',r.text)[1]
 r=post(customer,'shop-cart.php',{'action':'cart_update',f'quantity[{itemid}]':2});check('KES 160.00' in r.text,'Cart quantity update recalculates totals')
 r=post(customer,'shop-cart.php',{'action':'cart_update',f'quantity[{itemid}]':0});check('Your cart is empty.' in r.text,'Remove cart item')
 r=post(customer,'shop-single.php?slug=verification-product',{'action':'wishlist','product_id':pid});check('__verification product' in r.text,'Wishlist save')
 r=post(customer,'wishlist.php',{'action':'wishlist','product_id':pid,'remove':1});check('Your wishlist is empty.' in r.text,'Wishlist remove')
 email='verification-'+str(int(time.time()))+'@example.invalid';fixture['emails'].append(email);pw='Verification-'+str(time.time())+'-password'
 r=post(customer,'register.php',{'action':'register','name':'__verification customer','email':email,'password':pw});check('Welcome, __verification customer' in r.text,'Customer registration and login')
 r=post(customer,'add-address.php',{'action':'address','name':'__verification customer','phone':'0700000000','address':'Verification address','city':'Nairobi','country':'Kenya'});check('Verification address' in r.text,'Customer address save')
 r=post(customer,'shop-single.php?slug=verification-product',{'action':'cart_add','product_id':pid,'quantity':1});check('Added to your cart.' in r.text,'Authenticated add to cart')
 r=get(customer,'shop-checkout.php');key=re.search(r'name="checkout_key" value="([^"]+)"',r.text)[1];csrf=token(r)
 orderdata={'action':'checkout','checkout_key':key,'name':'__verification customer','email':email,'phone':'0700000000','address':'Verification address','city':'Nairobi','country':'Kenya','payment_method':'cod','csrf':csrf}
 r=customer.post(base+'shop-checkout.php',data=orderdata);check('Order confirmation' in r.text,'Checkout creates order');oid=int(parse_qs(urlparse(r.url).query)['id'][0]);fixture['orders'].append(oid)
 r=customer.post(base+'shop-checkout.php',data=orderdata);check(int(parse_qs(urlparse(r.url).query)['id'][0])==oid,'Duplicate checkout submission is idempotent')
 r=outsider.get(base+f'order-detail.php?id={oid}');check(r.status_code==404,'Order IDOR rejected')
 r=get(customer,f'order-detail.php?id={oid}');check('__verification product' in r.text,'Customer order ownership and historical item')
 r=get(customer,'shop-single.php?slug=verification-product');check('1 available' in r.text,'Checkout decrements managed stock')
 r=post(admin,f'admin/orders.php?id={oid}',{'action':'order','id':oid,'status':'cancelled','payment_status':'unpaid','notes':'Verification','note':'Verification cancellation'});check('Order updated.' in r.text,'Admin order status update')
 r=get(customer,'shop-single.php?slug=verification-product');check('2 available' in r.text,'Cancellation restores stock')
 r=post(admin,f'admin/orders.php?id={oid}',{'action':'order','id':oid,'status':'cancelled','payment_status':'unpaid','notes':'Verification','note':'Verification repeat'});r=get(customer,'shop-single.php?slug=verification-product');check('2 available' in r.text,'Repeated cancellation does not restore stock twice')
 r=post(admin,f'admin/manage.php?resource=categories',{'action':'archive','id':cat});check('Reassign products' in r.text,'Category deletion protected while referenced')
 postdata={'action':'save','title':'__verification article','slug':'verification-article','excerpt':'Verification excerpt','body':'<p>Verification article body.</p>','status':'publish','published_at':'2026-09-08T12:00','author':'Verification','tags':'','seo_title':'','meta_description':''}
 bid=saved_id(post(admin,'admin/manage.php?resource=posts&edit=1',postdata));fixture['posts'].append(bid)
 r=get(customer,'blog-single.php?slug=verification-article');check('Verification article body.' in r.text,'Blog publishing and details')
 postdata.update({'id':bid,'title':'__verification article updated'});saved_id(post(admin,f'admin/manage.php?resource=posts&id={bid}&edit=1',postdata));r=get(customer,'blog-single.php?slug=verification-article');check('__verification article updated' in r.text,'Blog update')
 r=post(admin,'admin/manage.php?resource=posts',{'action':'archive','id':bid});r=customer.get(base+'blog-single.php?slug=verification-article');check(r.status_code==404,'Archived blog hidden')
 # A script uploaded as an image must fail before insertion.
 r=post(admin,f'admin/manage.php?resource=products&id={pid}&edit=1',dict(fields,id=pid),{'image':('bad.php',b'<?php echo 1;','image/png')});check('Upload a valid' in r.text,'Upload MIME validation')
 r=post(customer,'logout.php',{});check('/login.php' in r.url,'Customer logout')
 r=post(customer,'login.php',{'action':'login','email':email,'password':pw});check('Welcome, __verification customer' in r.text,'Customer password login')
 r=post(customer,'user-setting.php',{'action':'password','current_password':pw,'password':pw+'new'});check('Password updated.' in r.text,'Customer password change')
 # Exercise native variants without changing imported catalog records.
 fields.update({'id':pid,'type':'variable','variants':json.dumps([{'sku':'TIBA-VERIFY-V1','attributes':{'Size':'Large'},'price':90,'regular_price':100,'stock':1,'manage_stock':1}])})
 saved_id(post(admin,f'admin/manage.php?resource=products&id={pid}&edit=1',fields.copy()))
 r=get(customer,'shop-single.php?slug=verification-product');vid=re.search(r'<option value="(\d+)">[^<]*Large',r.text)[1]
 r=post(customer,'shop-single.php?slug=verification-product',{'action':'cart_add','product_id':pid,'quantity':1});check('Choose an available variant' in r.text,'Variable product requires an option')
 r=post(customer,'shop-single.php?slug=verification-product',{'action':'cart_add','product_id':pid,'variant_id':vid,'quantity':1});check('KES 90.00' in r.text,'Variant price used in cart')
 r=post(customer,'shop-single.php?slug=verification-product',{'action':'cart_add','product_id':pid,'variant_id':vid,'quantity':1});check('requested quantity is unavailable' in r.text,'Variant stock enforced')
 # Keep the current settings unchanged while exercising validation and persistence.
 from bs4 import BeautifulSoup
 form=BeautifulSoup(get(admin,'admin/settings.php').text,'html.parser').select_one('form.admin-form');settings={'action':'settings'}
 for element in form.select('input[name],textarea[name]'):
  kind=element.get('type','');name=element['name']
  if kind=='file' or (kind=='checkbox' and not element.has_attr('checked')):continue
  settings[name]=element.get('value','1') if element.name=='input' else element.text
 r=post(admin,'admin/settings.php',settings);check('Settings saved.' in r.text,'Store settings round trip')
 # Reset links are delivered to the protected local outbox, never to HTTP responses.
 r=post(outsider,'forgot-password.php',{'action':'forgot','email':email});check('If an active account matches' in r.text,'Password reset generic response')
 resetfiles=[f for f in Path('storage').glob('reset-*.txt') if 'To: '+email in f.read_text()]
 check(bool(resetfiles),'Password reset local delivery')
 resetfile=resetfiles[-1];reset=re.search(r'token=([a-f0-9]+)',resetfile.read_text())[1]
 r=post(outsider,'reset-password.php?token='+reset,{'action':'reset','token':reset,'password':pw+'reset'});check('Password reset. You can now log in.' in r.text,'Password reset token accepted')
 r=customer.get(base+'user-dashboard.php');check('/login.php' in r.url,'Password reset invalidates existing sessions')
 r=post(outsider,'reset-password.php?token='+reset,{'action':'reset','token':reset,'password':pw+'reused'});check('expired or has already been used' in r.text,'Password reset is single-use')
 r=post(customer,'login.php',{'action':'login','email':email,'password':pw+'reset'});check('Welcome, __verification customer' in r.text,'Login with reset password')
 resetfile.unlink()
 r=get(admin,'admin/analytics.php');check('verification' in r.text.lower() or 'Product views' in r.text,'Analytics reports render native events')
 for protected in ['storage/admin-access.txt','app/config.local.php','tools/import-wordpress.php','database/001_schema.sql','control/core/database/connect.php']:
  check(outsider.get(base+protected).status_code==403,'Protected path '+protected)
 for route in ['shop','product/verification-product','blog','sitemap.xml']:
  check(customer.get(base+route).status_code==200,'Clean URL '+route)
 print(json.dumps({'passed':len(checks),'checks':checks},indent=2))
finally:
 fixture['cookies']={k:v for k,v in customer.cookies.items() if k in ['tiba_cart','tiba_visitor']}
 Path('storage/test-fixtures.json').write_text(json.dumps(fixture))
 Path('storage/integration-report.json').write_text(json.dumps({'passed':len(checks),'checks':checks},indent=2))
