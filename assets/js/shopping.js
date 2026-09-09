(() => {
  const drawer = document.getElementById('cart-drawer');
  if (!drawer || !window.bootstrap) return;
  const panel = bootstrap.Offcanvas.getOrCreateInstance(drawer);
  const modalElement = document.getElementById('add-cart-modal');
  const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  const quantity = document.getElementById('add-cart-quantity');
  const confirm = document.getElementById('add-cart-confirm');
  const modalError = document.getElementById('add-cart-error');
  const drawerError = document.getElementById('cart-error');
  const toast = document.getElementById('cart-toast');
  let pending = null, opener = null, toastTimer, cartBusy = false, cartChanged = false;
  function element(tag, text, className) { const el=document.createElement(tag);if(text!==undefined)el.textContent=text;if(className)el.className=className;return el; }
  async function request(data) {
    const options = {credentials:'same-origin',cache:'no-store'};
    if(data){options.method='POST';options.body=new URLSearchParams({...data,csrf:drawer.dataset.csrf});}
    const response=await fetch(drawer.dataset.api,options);
    let result;try{result=await response.json();}catch{throw new Error('Unable to reach the cart. Please try again.');}
    if(!response.ok||!result.ok)throw new Error(result.message||'Unable to update your cart.');
    if(data)cartChanged=true;
    return result;
  }
  function renderCart(data) {
    document.querySelectorAll('[data-cart-count]').forEach(el=>el.textContent=data.count);
    document.getElementById('cart-drawer-subtotal').textContent=data.subtotal;
    const list=document.getElementById('cart-drawer-items');list.replaceChildren();
    if(!data.items.length)list.append(element('p','Your cart is empty.','tq-cart-empty'));
    for(const item of data.items){
      const row=element('div',undefined,'tq-cart-item');row.dataset.id=item.id;
      const imageLink=element('a');imageLink.href=item.link;const image=element('img');image.src=item.image;image.alt=item.name;imageLink.append(image);
      const details=element('div',undefined,'tq-cart-item-details');const title=element('a',item.name);title.href=item.link;details.append(title);
      if(item.options)details.append(element('small',Object.entries(item.options).map(([k,v])=>`${k}: ${v}`).join(' / ')));
      details.append(element('p',`${item.quantity} × ${item.price} · ${item.total}`));
      const controls=element('div',undefined,'tq-quantity');
      for(const [action,label,aria] of [['minus','−','Decrease quantity'],['input','',''],['plus','+','Increase quantity'],['remove','×','Remove '+item.name]]){
        if(action==='input'){const input=element('input');input.type='number';input.min=0;input.max=99;input.step=1;input.value=item.quantity;input.setAttribute('aria-label','Quantity for '+item.name);controls.append(input);}
        else{const button=element('button',label);button.type='button';button.dataset.cartAction=action;button.setAttribute('aria-label',aria);controls.append(button);}
      }
      details.append(controls);row.append(imageLink,details);list.append(row);
    }
  }
  async function openCart(trigger) {
    toast.hidden=true;
    const mobile=document.getElementById('offcanvasNavbar');
    if(mobile.classList.contains('show')){mobile.addEventListener('hidden.bs.offcanvas',()=>openCart(trigger),{once:true});bootstrap.Offcanvas.getOrCreateInstance(mobile).hide();return;}
    opener=trigger;drawerError.hidden=true;panel.show();
    document.getElementById('cart-drawer-items').textContent='Loading your cart…';
    try{renderCart(await request());}catch(e){drawerError.textContent=e.message;drawerError.hidden=false;}
  }
  drawer.addEventListener('hidden.bs.offcanvas',()=>{opener?.focus();if(cartChanged&&/\/(shop-cart\.php|shop-checkout\.php|cart\/?|checkout\/?)$/.test(location.pathname))location.reload();});
  document.addEventListener('click',event=>{
    const toggle=event.target.closest('[data-cart-open]');if(toggle){event.preventDefault();openCart(toggle);return;}
    if(event.target.closest('[data-toast-close]'))toast.hidden=true;
    const step=event.target.closest('[data-add-step]');if(step)quantity.value=Math.min(99,Math.max(1,(Number(quantity.value)||1)+Number(step.dataset.addStep)));
  });
  document.addEventListener('submit',event=>{
    const form=event.target;
    if(form.querySelector('[name="action"]')?.value!=='cart_add')return;
    event.preventDefault();
    const card=form.closest('.product-item');
    const name=card?.querySelector('.product-title')?.textContent.trim()||document.querySelector('.shop-single-title')?.textContent.trim()||'Product';
    const image=card?.querySelector('.product-img img')||document.querySelector('.store-gallery img');
    pending={product_id:form.querySelector('[name="product_id"]').value,variant_id:form.querySelector('[name="variant_id"]')?.value||0,name};
    opener=event.submitter;
    document.getElementById('add-cart-name').textContent=name;
    document.getElementById('add-cart-image').src=image?.src||'';
    document.getElementById('add-cart-image').alt=name;
    quantity.value=form.querySelector('[name="quantity"]')?.value||1;
    modalError.hidden=true;modal.show();
  });
  modalElement.addEventListener('shown.bs.modal',()=>quantity.focus());
  modalElement.addEventListener('hidden.bs.modal',()=>{opener?.focus();});
  document.getElementById('add-cart-form').addEventListener('submit',async event=>{
    event.preventDefault();if(!pending||confirm.disabled)return;
    confirm.disabled=true;modalError.hidden=true;
    try{
      const result=await request({action:'add',product_id:pending.product_id,variant_id:pending.variant_id,quantity:quantity.value});
      renderCart(result);modal.hide();toast.querySelector('span').textContent=`Added “${pending.name}” to your cart.`;toast.hidden=false;clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.hidden=true,7000);
    }catch(e){modalError.textContent=e.message;modalError.hidden=false;}
    finally{confirm.disabled=false;}
  });
  async function updateItem(row,value) {
    if(cartBusy)return;cartBusy=true;drawerError.hidden=true;
    drawer.querySelectorAll('.tq-quantity button,.tq-quantity input').forEach(el=>el.disabled=true);
    try{renderCart(await request({action:'update',id:row.dataset.id,quantity:value}));}
    catch(e){drawerError.textContent=e.message;drawerError.hidden=false;try{renderCart(await request());}catch{}}
    finally{cartBusy=false;drawer.querySelectorAll('.tq-quantity button,.tq-quantity input').forEach(el=>el.disabled=false);}
  }
  drawer.addEventListener('click',event=>{const button=event.target.closest('[data-cart-action]');if(!button)return;const row=button.closest('.tq-cart-item'),input=row.querySelector('input');const action=button.dataset.cartAction;updateItem(row,action==='remove'?0:Math.max(0,Number(input.value)+(action==='plus'?1:-1)));});
  drawer.addEventListener('change',event=>{if(event.target.matches('.tq-quantity input'))updateItem(event.target.closest('.tq-cart-item'),event.target.value);});
  const categoryButton=document.querySelector('.tq-category-toggle');
  categoryButton?.addEventListener('click',()=>{const expanded=categoryButton.getAttribute('aria-expanded')!=='true';categoryButton.setAttribute('aria-expanded',expanded);categoryButton.closest('li').classList.toggle('is-open',expanded);});
  document.addEventListener('click',event=>{if(!event.target.closest('.tq-products-menu')){categoryButton?.setAttribute('aria-expanded','false');categoryButton?.closest('li').classList.remove('is-open');}});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){categoryButton?.setAttribute('aria-expanded','false');categoryButton?.closest('li').classList.remove('is-open');}});

  const grid=document.getElementById('shop-products');if(!grid)return;
  const filters=document.getElementById('shop-filters');
  document.querySelectorAll('[data-shop-submit]').forEach(select=>{if(window.jQuery)jQuery(select).on('change',()=>filters.requestSubmit());else select.addEventListener('change',()=>filters.requestSubmit());});
  document.getElementById('price-range')?.addEventListener('input',event=>document.getElementById('max').value=event.target.value);
  function layout(value){value=value==='list'?'list':'grid';grid.classList.toggle('is-list',value==='list');document.querySelectorAll('[data-shop-layout]').forEach(button=>{button.classList.toggle('active',button.dataset.shopLayout===value);button.setAttribute('aria-pressed',button.dataset.shopLayout===value);});try{localStorage.setItem('tiba-shop-layout',value);}catch{}}
  let saved='grid';try{saved=localStorage.getItem('tiba-shop-layout')||'grid';}catch{}layout(saved);
  document.querySelectorAll('[data-shop-layout]').forEach(button=>button.addEventListener('click',()=>layout(button.dataset.shopLayout)));
  const sentinel=document.getElementById('shop-load-sentinel'),status=document.getElementById('shop-load-status'),more=document.getElementById('shop-load-more');
  let current=Number(grid.dataset.page),pages=Number(grid.dataset.pages),loading=false,failed=false;
  const seen=new Set([...grid.querySelectorAll('[data-product-id]')].map(el=>el.dataset.productId));
  document.getElementById('shop-pagination').hidden=true;
  let observer;
  function finished(){more.hidden=true;status.textContent=seen.size?'You’ve reached the end of these products.':'';observer?.disconnect();}
  async function loadMore(){
    if(loading||current>=pages)return;loading=true;failed=false;more.disabled=true;status.textContent='Loading more products…';
    const next=new URL(location.href);next.searchParams.set('page',current+1);
    try{
      const response=await fetch(next,{credentials:'same-origin',headers:{'X-Tiba-Load':'products'}});if(!response.ok)throw new Error();
      const doc=new DOMParser().parseFromString(await response.text(),'text/html'),nextGrid=doc.getElementById('shop-products');if(!nextGrid)throw new Error();
      const items=[...nextGrid.querySelectorAll('[data-product-id]')].filter(el=>!seen.has(el.dataset.productId));
      items.forEach(el=>{seen.add(el.dataset.productId);grid.append(el);});current++;pages=Number(nextGrid.dataset.pages);grid.dataset.page=current;
      document.getElementById('shop-visible-count').textContent=(Number(grid.dataset.resultStart||1)-1)+seen.size;
      if(!items.length||current>=pages){current=pages;finished();}else{status.textContent='';more.textContent='Load more products';}
    }catch{failed=true;status.textContent='Unable to load more products. Please try again.';more.textContent='Retry loading';more.hidden=false;}
    finally{loading=false;more.disabled=false;}
  }
  more.addEventListener('click',loadMore);
  if(current<pages){more.hidden=false;if('IntersectionObserver' in window){observer=new IntersectionObserver(entries=>{if(entries.some(e=>e.isIntersecting)&&!failed)loadMore();},{rootMargin:'300px 0px'});observer.observe(sentinel);}}else finished();
})();
